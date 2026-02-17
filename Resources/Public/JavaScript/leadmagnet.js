document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.leadmagnet-element').forEach(function (el) {
        var uid = el.dataset.leadmagnetUid;
        var typeNum = el.dataset.leadmagnetType;
        var toggleBtn = el.querySelector('.leadmagnet-toggle-btn');
        var collapseEl = el.querySelector('#leadmagnet-collapse-' + uid);
        var form = el.querySelector('.leadmagnet-form');
        var spinner = el.querySelector('.leadmagnet-spinner');
        var successMsg = el.querySelector('.leadmagnet-success');
        var errorMsg = el.querySelector('.leadmagnet-error');
        var errorTitle = el.querySelector('.leadmagnet-error-title');
        var errorText = el.querySelector('.leadmagnet-error-text');
        var emailInput = el.querySelector('input[name="email"]');

        // Store original button classes for restoring
        var btnClasses = toggleBtn.className;

        // Bootstrap 5 collapse events — style button as passive when open
        collapseEl.addEventListener('shown.bs.collapse', function () {
            toggleBtn.classList.forEach(function (cls) {
                if (cls.startsWith('btn-') && !cls.startsWith('btn-outline-') && cls !== 'btn-lg') {
                    toggleBtn.classList.remove(cls);
                    toggleBtn.classList.add(cls.replace('btn-', 'btn-outline-'));
                }
            });
            toggleBtn.setAttribute('disabled', '');
            emailInput.focus();
        });

        collapseEl.addEventListener('hidden.bs.collapse', function () {
            toggleBtn.className = btnClasses;
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Reset states
            errorMsg.style.display = 'none';
            emailInput.classList.remove('is-invalid');

            // Validate email
            if (!emailInput.value || !emailInput.validity.valid) {
                emailInput.classList.add('is-invalid');
                return;
            }

            // Honeypot
            var honeyField = form.querySelector('input[name="sweets"]');
            if (honeyField && honeyField.value !== '') {
                return;
            }

            // Show spinner, hide form
            form.style.display = 'none';
            spinner.style.display = 'block';

            var formData = new FormData();
            formData.append('tx_leadmagnet_show[email]', emailInput.value);
            formData.append('tx_leadmagnet_show[contentElementUid]', uid);

            fetch(window.location.pathname + '?type=' + typeNum, {
                method: 'POST',
                body: formData
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                spinner.style.display = 'none';
                if (data.success) {
                    successMsg.style.display = 'block';
                } else {
                    showError(data.code || 'unknown', data.message);
                    form.style.display = 'block';
                }
            })
            .catch(function (err) {
                spinner.style.display = 'none';
                showError('network', null, err);
                form.style.display = 'block';
            });
        });

        function showError(code, message, err) {
            var messages = {
                'invalid_email': {
                    title: 'Ungültige E-Mail',
                    text: 'Bitte überprüfen Sie Ihre E-Mail-Adresse und versuchen Sie es erneut.'
                },
                'mail_failed': {
                    title: 'E-Mail-Versand fehlgeschlagen',
                    text: 'Die E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es in einigen Minuten erneut.'
                },
                'network': {
                    title: 'Verbindungsfehler',
                    text: 'Der Server konnte nicht erreicht werden. Bitte prüfen Sie Ihre Internetverbindung und versuchen Sie es erneut.'
                },
                'unknown': {
                    title: 'Fehler',
                    text: 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
                }
            };

            var msg = messages[code] || messages['unknown'];
            errorTitle.textContent = msg.title;
            errorText.textContent = message || msg.text;
            errorMsg.style.display = 'block';
        }
    });
});
