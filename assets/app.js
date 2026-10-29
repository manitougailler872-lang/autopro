import './stimulus_bootstrap.js';
/* LUXE CAR — minimal JS. All business logic stays server-side. */

(function () {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealables = document.querySelectorAll('.reveal');

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        revealables.forEach(el => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealables.forEach(el => observer.observe(el));
})();

/* Auto-dismiss flash messages after 4s */
setTimeout(() => {
    document.querySelectorAll('[role="status"]').forEach(el => {
        el.style.transition = 'opacity 0.4s ease-out';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    });
}, 4000);
