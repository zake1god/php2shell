<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const TOKEN_FILE = '/out/shell.enabled';
const DEFAULT_CWD = '/var/www/html';
const MAX_COMMAND_BYTES = 4096;
const MAX_OUTPUT_BYTES = 1048576;

function deny(int $status, string $message): never
{
    http_response_code($status);
    echo $message, "\n";
    exit;
}

if (getenv('LAB_VARIANT') !== 'vulnerable') {
    deny(403, 'The target-backed shell is disabled on this control.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    deny(405, 'POST required.');
}

$expectedToken = @file_get_contents(TOKEN_FILE);
$providedToken = $_SERVER['HTTP_X_LAB_TOKEN'] ?? '';
if (!is_string($expectedToken) || trim($expectedToken) === '' ||
    !is_string($providedToken) ||
    !hash_equals(trim($expectedToken), trim($providedToken))) {
    deny(403, 'Shell locked: a successful local RCE proof is required.');
}

$command = $_POST['cmd'] ?? '';
if (!is_string($command) || trim($command) === '') {
    deny(400, 'A non-empty cmd field is required.');
}
if (strlen($command) > MAX_COMMAND_BYTES) {
    deny(413, 'Command exceeds the 4096-byte lab limit.');
}

$requestedCwd = $_POST['cwd'] ?? DEFAULT_CWD;
if (!is_string($requestedCwd) || $requestedCwd === '') {
    deny(400, 'Invalid working directory.');
}
$cwd = realpath($requestedCwd);
if ($cwd === false || !is_dir($cwd) || !is_readable($cwd)) {
    deny(400, 'Working directory is not accessible to the web worker.');
}

$descriptorSpec = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$process = proc_open(
    ['/usr/bin/timeout', '--signal=KILL', '10s', '/bin/sh', '-lc', $command],
    $descriptorSpec,
    $pipes,
    $cwd,
    null,
    ['bypass_shell' => true]
);
if (!is_resource($process)) {
    deny(500, 'Unable to start the lab command process.');
}

fclose($pipes[0]);
stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);
$output = '';
$truncated = false;

while (true) {
    $read = [];
    if (!feof($pipes[1])) {
        $read[] = $pipes[1];
    }
    if (!feof($pipes[2])) {
        $read[] = $pipes[2];
    }
    if ($read === []) {
        break;
    }

    $write = null;
    $except = null;
    $ready = stream_select($read, $write, $except, 1, 0);
    if ($ready === false) {
        break;
    }
    if ($ready === 0) {
        continue;
    }

    foreach ($read as $stream) {
        $remaining = MAX_OUTPUT_BYTES - strlen($output);
        if ($remaining <= 0) {
            $truncated = true;
            continue;
        }
        $chunk = fread($stream, min(8192, $remaining));
        if ($chunk !== false) {
            $output .= $chunk;
        }
    }
}

fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

header('X-Zarxec-Exit-Code: ' . (string) $exitCode);
header('X-Zarxec-Cwd: ' . rawurlencode($cwd));
if ($truncated) {
    $output .= "\n[output truncated at 1 MiB]\n";
}
echo $output;
