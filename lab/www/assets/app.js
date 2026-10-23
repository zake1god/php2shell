(() => {
    const clock = document.querySelector('#utc-clock');
    const menuButton = document.querySelector('.menu-button');
    const navigation = document.querySelector('#site-nav');

    const updateClock = () => {
        if (clock) {
            clock.textContent = new Date().toISOString().replace('T', ' ').replace(/\.\d{3}Z$/, ' UTC');
        }
    };

    updateClock();
    window.setInterval(updateClock, 1000);

    if (menuButton && navigation) {
        menuButton.addEventListener('click', () => {
            const open = navigation.classList.toggle('open');
            menuButton.setAttribute('aria-expanded', String(open));
        });

        navigation.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                navigation.classList.remove('open');
                menuButton.setAttribute('aria-expanded', 'false');
            });
        });
    }
})();
