import './bootstrap';

// Minimal scroll-triggered animations
// This defers Tailwind animation classes until the element enters the viewport
// Supported classes: animate-fade-in, animate-fade-in-up, animate-fade-in-right
// No markup changes required.
document.addEventListener('DOMContentLoaded', () => {
  // Sanitize stray non-printable characters that may appear in headings
  const mainTitle = document.querySelector('h1');
  if (mainTitle && mainTitle.textContent) {
    const raw = mainTitle.textContent;
    const m = raw.match(/^\s*Welcome back,\s*(.+?)!/);
    if (m) {
      const name = m[1].replace(/[\u0000-\u001F\u007F`<>]/g, '').trim();
      mainTitle.textContent = `Welcome back, ${name}!`;
    } else {
      // Fallback: generic cleanup
      mainTitle.textContent = raw
        .replace(/[\u0000-\u001F\u007F`<>]/g, '')
        .replace(/\s{2,}/g, ' ')
        .trim();
    }
  }

  const animateClasses = ['animate-fade-in', 'animate-fade-in-up', 'animate-fade-in-right'];
  const selector = animateClasses.map(c => `.${c}`).join(',');
  const candidates = Array.from(document.querySelectorAll(selector));

  if (!('IntersectionObserver' in window) || candidates.length === 0) return;

  const obs = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const restore = el.dataset.animateClass;
        if (restore) {
          restore.split(' ').forEach(c => el.classList.add(c));
          el.removeAttribute('data-animate-class');
        }
        el.classList.remove('opacity-0', 'translate-y-5', '-translate-x-5', 'will-animate');
        obs.unobserve(el);
      });
    },
    { threshold: 0.2 }
  );

  const isInView = (el) => {
    const r = el.getBoundingClientRect();
    return r.top < window.innerHeight * 0.8 && r.bottom > 0;
  };

  candidates.forEach(el => {
    // Collect animation classes present on the element
    const present = animateClasses.filter(c => el.classList.contains(c));
    if (present.length === 0) return;

    // If already in view on load, let it animate immediately
    if (isInView(el)) return;

    // Defer the animation until it scrolls into view
    present.forEach(c => el.classList.remove(c));
    el.dataset.animateClass = present.join(' ');

    // Provide subtle initial hidden state
    const initial = ['opacity-0', 'will-animate'];
    if (present.includes('animate-fade-in-up')) initial.push('translate-y-5');
    if (present.includes('animate-fade-in-right')) initial.push('-translate-x-5');
    el.classList.add(...initial);

    obs.observe(el);
  });
});
