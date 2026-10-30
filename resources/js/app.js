import { createApp } from 'vue';
import App from './App.vue';

const vueRoot = document.getElementById('app');

if (vueRoot) {
    createApp(App).mount(vueRoot);
}

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