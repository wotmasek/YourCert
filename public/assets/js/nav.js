window.addEventListener('load', updateNavHeight);
window.addEventListener('resize', updateNavHeight);

function updateNavHeight() {
    const nav    = document.getElementById('nav');
    const footer = document.getElementById('footer');
    if (nav) {
      document.documentElement.style.setProperty('--nav-height', `${nav.offsetHeight}px`);
    }
    if (footer) {
      document.documentElement.style.setProperty('--footer-height', `${footer.offsetHeight}px`);
    }
}  

;(function(){
    const stored     = localStorage.getItem('bs-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initial    = stored || (prefersDark ? 'dark' : 'light');

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('bs-theme', theme);
        const icon = document.getElementById('theme-icon');
        if (icon) {
        icon.classList.toggle('bi-moon', theme === 'light');
        icon.classList.toggle('bi-sun',  theme === 'dark');
        }
    }

    applyTheme(initial);

    const btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-bs-theme');
        applyTheme(current === 'light' ? 'dark' : 'light');
        });
    }
})();
