export function initTextToggle(containerSelector, {
  limitAttr = 'data-limit',
  btnMoreTxt = 'Show more',
  btnLessTxt = 'Show less'
} = {}) {
  document.querySelectorAll(containerSelector).forEach(wrapper => {
    const full = wrapper.querySelector('.full-text');
    const short = wrapper.querySelector('.short-text');
    const btn  = wrapper.querySelector('.toggle-text');

    const limit = parseInt(wrapper.getAttribute(limitAttr), 10) || 0;
    const text  = full.textContent.trim();
    if (limit <= 0 || text.length <= limit) {
      full.classList.remove('d-none');
      if (short) short.classList.add('d-none');
      if (btn)   btn.classList.add('d-none');
      return;
    }

    const snippet = text.slice(0, limit).replace(/\s+\S*$/, '') + '…';
    short.textContent = snippet;
    full.classList.add('d-none');
    short.classList.remove('d-none');
    btn.textContent = btnMoreTxt;
    btn.classList.remove('d-none');

    btn.addEventListener('click', ev => {
      ev.preventDefault();
      const expanded = full.classList.contains('d-none');
      if (expanded) {
        full.classList.remove('d-none');
        short.classList.add('d-none');
        btn.textContent = btnLessTxt;
      } else {
        full.classList.add('d-none');
        short.classList.remove('d-none');
        btn.textContent = btnMoreTxt;
      }
    });
  });
}

// Auto-inicjalizacja po załadowaniu strony
document.addEventListener('DOMContentLoaded', () => {
  initTextToggle('.post-toggle', {
    limitAttr: 'data-limit',
    btnMoreTxt: 'Show more',
    btnLessTxt: 'Show less'
  });
});
