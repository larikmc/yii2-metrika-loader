/**
 * Yandex Metrika Admin Dashboard Interactive JS
 * Zero dependencies, pure vanilla JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // 1. Copy Buttons
        document.querySelectorAll('.ym-copy-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = btn.getAttribute('data-target');
                var codeElem = targetId ? document.getElementById(targetId) : btn.parentElement.querySelector('pre');
                if (!codeElem) return;

                var text = codeElem.innerText || codeElem.textContent;
                navigator.clipboard.writeText(text).then(function() {
                    showToast('Код скопирован в буфер обмена!');
                }).catch(function() {
                    // Fallback
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    showToast('Код скопирован в буфер обмена!');
                });
            });
        });

        // 2. Category Filter Tabs
        var filterBtns = document.querySelectorAll('.ym-nav__btn[data-category]');
        var cards = document.querySelectorAll('.ym-setting-card');

        filterBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                filterBtns.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');

                var cat = btn.getAttribute('data-category');
                cards.forEach(function(card) {
                    if (cat === 'all' || card.getAttribute('data-category') === cat) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // 3. Toast Helper
        function showToast(msg) {
            var toast = document.getElementById('ymToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'ymToast';
                toast.className = 'ym-toast';
                document.body.appendChild(toast);
            }
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(function() {
                toast.classList.remove('show');
            }, 2500);
        }
    });
})();
