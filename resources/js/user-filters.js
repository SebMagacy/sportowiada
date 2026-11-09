const filtersPanel = document.getElementById('users-filters-panel');
const filtersToggle = document.getElementById('users-filters-toggle');

if (filtersPanel && filtersToggle) {
    filtersPanel.hidden = true;
    filtersToggle.setAttribute('aria-expanded', 'false');

    filtersToggle.addEventListener('click', () => {
        filtersPanel.hidden = !filtersPanel.hidden;
        filtersToggle.setAttribute('aria-expanded', String(!filtersPanel.hidden));
    });
}