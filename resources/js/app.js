import { createApp } from 'vue';
import App from './App.vue';

const vueRoot = document.getElementById('app');

if (vueRoot) {
    createApp(App).mount(vueRoot);
}
