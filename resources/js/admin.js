import './user-filters.js';
import { initializeSearchInputs } from './search-input.js';
import { initializePerPageSelects } from './per-page-select.js';
import { initializeRowSelections } from './row-selection.js';
import { initializeDisciplineForms } from './discipline-form.js';

initializeSearchInputs();
initializePerPageSelects();
initializeRowSelections();
initializeDisciplineForms();

const sidebar = document.getElementById('admin-sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');

if (sidebar && sidebarToggle) {
    const isInitiallyCollapsed = document.documentElement.classList.contains('sidebar-collapsed');

    sidebarToggle.setAttribute('aria-expanded', String(!isInitiallyCollapsed));
    sidebarToggle.setAttribute(
        'aria-label',
        isInitiallyCollapsed ? 'Rozwiń panel nawigacyjny' : 'Zwiń panel nawigacyjny',
    );

    sidebarToggle.addEventListener('click', () => {
        const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        localStorage.setItem('admin-sidebar-collapsed', String(isCollapsed));
        sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
        sidebarToggle.setAttribute(
            'aria-label',
            isCollapsed ? 'Rozwiń panel nawigacyjny' : 'Zwiń panel nawigacyjny',
        );
    });
}