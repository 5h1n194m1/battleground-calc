
document.addEventListener('DOMContentLoaded', () => {
    const toolbar = document.querySelector('.score-toolbar');
    if (!toolbar) return;

    // Prevent invisible overlay issues
    toolbar.querySelectorAll('button, .btn, a').forEach((el) => {
        el.style.pointerEvents = 'auto';
        el.style.position = 'relative';
        el.style.zIndex = '30';
    });

    // Keep hide controller aligned right if exists
    const hideBtn = [...toolbar.querySelectorAll('button, .btn')]
        .find(btn => (btn.textContent || '').toLowerCase().includes('hide controller'));

    if (hideBtn) {
        hideBtn.classList.add('btn-hide-controller');
    }

    // Wrap secondary row buttons if toolbar too crowded
    if (toolbar.children.length > 8 && !toolbar.querySelector('.toolbar-secondary')) {
        const secondary = document.createElement('div');
        secondary.className = 'toolbar-secondary';

        const items = [...toolbar.children];
        items.slice(6).forEach(el => secondary.appendChild(el));
        toolbar.appendChild(secondary);
    }
});
