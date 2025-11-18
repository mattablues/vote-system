import Alpine from "alpinejs";
import Collapse from "@alpinejs/collapse";
import Ajax from "@imacrayon/alpine-ajax";
import Ui from "@alpinejs/ui";
import Focus from "@alpinejs/focus";
import { addTableAria } from "./addTableAria";
import { handleCookiesBanner } from "./cookies";
import SearchUsers from './search-users';
import SearchDeletedUsers from './search-deleted-users';

window.Alpine = Alpine;
Alpine.plugin(Collapse);
Alpine.plugin(Ui);
Alpine.plugin(Focus);
Alpine.plugin(Ajax);

Alpine.start();

// Kör dina funktioner
addTableAria();
handleCookiesBanner();

document.addEventListener('DOMContentLoaded', () => {
    const tokenMeta = document.querySelector('meta[name="Authorization"]');
    const token = tokenMeta ? (tokenMeta.content || '') : '';
    const mainContent = document.querySelector('main');

    const searchUserInput = document.getElementById('search-users');
    const searchDeletedInput = document.getElementById('search-deleted-users');

    if (searchUserInput && mainContent) {
        new SearchUsers('search-users', 'main', token);
    }

    if (searchDeletedInput && mainContent) {
        new SearchDeletedUsers('search-deleted-users', 'main', token);
    }

    const btn = document.getElementById('search-toggle');
    const wrap = document.getElementById('search-wrap');
    if (!btn || !wrap) return;

    const open = () => {
        wrap.classList.remove('hidden');
        wrap.style.position = 'absolute';
        wrap.style.left = '0';
        wrap.style.right = '0';
        wrap.style.top = '100%';
        wrap.style.marginTop = '0.5rem';
        wrap.style.zIndex = '70';
        // ... existing code ...
        setTimeout(() => {
            if (searchUserInput) {
                searchUserInput.focus();
            } else if (searchDeletedInput) {
                searchDeletedInput.focus();
            }
        }, 0);
    };

    const close = () => {
        wrap.classList.add('hidden');
        wrap.removeAttribute('style');

        if (searchUserInput) searchUserInput.value = '';
        if (searchDeletedInput) searchDeletedInput.value = '';

        // Hitta och rensa första matchande dropdown som finns
        const dropdown =
            document.getElementById('search-dropdown');

        if (dropdown) {
            const resultContainer = dropdown.querySelector('.result-container');
            if (resultContainer) resultContainer.innerHTML = '';
            dropdown.classList.add('hidden');
        }
    };

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (wrap.classList.contains('hidden')) open(); else close();
    });

    document.addEventListener('click', (e) => {
        if (!wrap.contains(e.target) && !btn.contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
});

