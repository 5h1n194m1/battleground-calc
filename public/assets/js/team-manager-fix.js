
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.js-team-manager-toggle');
    const workspace = document.querySelector('.score-page-workspace');

    if(toggle && workspace){
        toggle.addEventListener('click', () => {
            setTimeout(() => {
                workspace.classList.toggle(
                    'is-manager-open',
                    toggle.getAttribute('aria-expanded') === 'true'
                );
            }, 10);
        });
    }
});
