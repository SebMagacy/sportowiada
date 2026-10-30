import { createApp } from 'vue';
import App from './App.vue';

createApp(App).mount('#app');

const sidebar = document.getElementById('admin-sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');

if (sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        const isCollapsed = sidebar.classList.toggle('is-collapsed');

        sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
        sidebarToggle.setAttribute(
            'aria-label',
            isCollapsed ? 'Rozwiń panel nawigacyjny' : 'Zwiń panel nawigacyjny',
        );
    });
}