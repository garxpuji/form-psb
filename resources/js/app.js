import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import TomSelect from 'tom-select';

Alpine.plugin(focus);

window.TomSelect = TomSelect;
window.Alpine = Alpine;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.start();
    });
} else {
    Alpine.start();
}
