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

        // 2. Mode Radio Card selection sync
        var modeRadios = document.querySelectorAll('input[type="radio"][name="MetrikaSetting[mode]"]');
        modeRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.ym-mode-card').forEach(function(card) {
                    card.classList.remove('is-active');
                });
                var parentCard = radio.closest('.ym-mode-card');
                if (parentCard) {
                    parentCard.classList.add('is-active');
                }
            });
        });

        // 3. Enabled Switch label sync
        var enabledCheckbox = document.getElementById('ymEnabled');
        var enabledLabel = document.getElementById('ymEnabledLabel');
        if (enabledCheckbox && enabledLabel) {
            enabledCheckbox.addEventListener('change', function() {
                enabledLabel.textContent = enabledCheckbox.checked ? 'Включен на сайте' : 'Отключен';
            });
        }

        // 4. Toast Helper
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