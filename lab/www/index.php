<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self'; style-src 'self'; script-src 'self'; base-uri 'none'; form-action 'self'");

$year = gmdate('Y');
$requestId = substr(hash('sha256', (string) hrtime(true)), 0, 12);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Zarxec Lab — fictional security engineering company website used by the local PHP2Shell research environment.">
    <title>Zarxec Lab — Security Engineering</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="#top" aria-label="Zarxec Lab home">
            <span class="brand-mark">Z</span>
            <span><strong>ZARXEC</strong><small>LAB</small></span>
        </a>
        <button class="menu-button" type="button" aria-expanded="false" aria-controls="site-nav">Menu</button>
        <nav id="site-nav" class="site-nav" aria-label="Primary navigation">
            <a href="#capabilities">Capabilities</a>
            <a href="#research">Research</a>
            <a href="#trust">Trust Center</a>
            <a class="nav-cta" href="#contact">Contact</a>
        </nav>
    </header>

    <main id="top">
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <p class="eyebrow"><span class="pulse"></span> Security research environment online</p>
                <h1 id="hero-title">Engineering confidence<br>into critical systems.</h1>
                <p class="hero-copy">Zarxec Lab is a fictional security engineering company created for a controlled PHP runtime research environment. We turn complex failure modes into evidence teams can act on.</p>
                <div class="hero-actions">
                    <a class="button primary" href="#research">View our research</a>
                    <a class="button secondary" href="#trust">Open trust center</a>
                </div>
            </div>
            <aside class="hero-console" aria-label="Live lab status">
                <div class="console-bar"><span></span><span></span><span></span><b>zarxec-edge-01</b></div>
                <div class="console-body">
                    <p><em>$</em> zarxec status --public</p>
                    <p class="console-ok">✓ web gateway operational</p>
                    <p class="console-ok">✓ research sandbox isolated</p>
                    <p class="console-ok">✓ telemetry pipeline active</p>
                    <p><em>$</em> date --utc</p>
                    <p id="utc-clock">synchronizing…</p>
                </div>
            </aside>
        </section>

        <section class="signal-strip" aria-label="Company principles">
            <div><strong>Evidence first</strong><span>Runtime validation over assumptions</span></div>
            <div><strong>Scoped by design</strong><span>Isolation, controls and reproducibility</span></div>
            <div><strong>Built to explain</strong><span>Clear technical impact for decision makers</span></div>
        </section>

        <section id="capabilities" class="section">
            <div class="section-heading">
                <p class="eyebrow">What we do</p>
                <h2>Security work that survives scrutiny.</h2>
                <p>Our fictional team models how a modern security consultancy communicates technical findings without sacrificing precision.</p>
            </div>
            <div class="card-grid">
                <article class="service-card">
                    <span class="card-number">01</span>
                    <h3>Runtime Research</h3>
                    <p>Controlled experiments that separate affected versions, vulnerable preconditions and demonstrated execution.</p>
                    <a href="#research">Explore the lab <span>↗</span></a>
                </article>
                <article class="service-card">
                    <span class="card-number">02</span>
                    <h3>Exploit Validation</h3>
                    <p>Positive and negative controls, immutable artifacts and observed output designed for independent verification.</p>
                    <a href="#trust">Review our method <span>↗</span></a>
                </article>
                <article class="service-card">
                    <span class="card-number">03</span>
                    <h3>Patch Readiness</h3>
                    <p>Clear upgrade boundaries, temporary exposure reductions and operational guidance for response teams.</p>
                    <a href="#contact">Plan an update <span>↗</span></a>
                </article>
            </div>
        </section>

        <section id="research" class="research-section">
            <div>
                <p class="eyebrow">Current research</p>
                <h2>PHP request lifecycle security</h2>
                <p>This local fixture represents a conventional company website while the lab examines how malformed multipart requests interact with PHP memory handling and persistent workers.</p>
                <ul class="research-list">
                    <li><span>CVE-2026-109618</span><strong>OOM state corruption</strong></li>
                    <li><span>CVE-2026-109619</span><strong>Stale global state</strong></li>
                    <li><span>CONTROL MATRIX</span><strong>Vulnerable, patched and mitigated builds</strong></li>
                </ul>
            </div>
            <div class="research-panel">
                <p class="panel-label">Research posture</p>
                <div class="radar" aria-hidden="true"><span></span><i></i></div>
                <dl>
                    <div><dt>Environment</dt><dd>Local / isolated</dd></div>
                    <div><dt>Application</dt><dd>Zarxec corporate fixture</dd></div>
                    <div><dt>Method</dt><dd>Controlled comparison</dd></div>
                    <div><dt>Request ID</dt><dd><?= htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') ?></dd></div>
                </dl>
            </div>
        </section>

        <section id="trust" class="section trust-section">
            <div class="section-heading compact">
                <p class="eyebrow">Trust center</p>
                <h2>Responsible by construction.</h2>
            </div>
            <div class="trust-grid">
                <div><span>01</span><h3>Loopback only</h3><p>Every published Docker port is bound to 127.0.0.1.</p></div>
                <div><span>02</span><h3>Observable controls</h3><p>The patched and mitigation variants must remain negative.</p></div>
                <div><span>03</span><h3>Disposable state</h3><p>Runtime activation tokens are removed when the lab stops.</p></div>
                <div><span>04</span><h3>Actionable output</h3><p>Every claim is tied to an executed command and retained evidence.</p></div>
            </div>
        </section>

        <section id="contact" class="contact-section">
            <p class="eyebrow">Zarxec Lab</p>
            <h2>Make the patch window<br>smaller than the risk window.</h2>
            <p>This site is a fictional corporate fixture for local security education. No production service or customer data is connected.</p>
            <div class="contact-actions">
                <a class="button primary" href="mailto:zake@zarxec.com">zake@zarxec.com</a>
                <a class="button secondary" href="https://zarxec.com/" target="_blank" rel="noopener noreferrer">Visit Zarxec.com <span aria-hidden="true">↗</span></a>
            </div>
        </section>
    </main>

    <footer>
        <div class="brand footer-brand"><span class="brand-mark">Z</span><span><strong>ZARXEC</strong><small>LAB</small></span></div>
        <p>Local security research fixture · Website credit <a class="credit-link" href="https://zarxec.com/" target="_blank" rel="noopener noreferrer">Zake / Zarxec.com ↗</a></p>
        <p>© <?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?> Zarxec Lab</p>
    </footer>
</body>
</html>
