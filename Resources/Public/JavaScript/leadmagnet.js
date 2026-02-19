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

            var newsletterInput = form.querySelector('input[name="newsletter"]');
            var formData = new FormData();
            formData.append('tx_leadmagnet_show[controller]', 'Leadmagnet');
            formData.append('tx_leadmagnet_show[action]', 'submit');
            formData.append('tx_leadmagnet_show[email]', emailInput.value);
            formData.append('tx_leadmagnet_show[contentElementUid]', uid);
            formData.append('tx_leadmagnet_show[newsletter]', newsletterInput && newsletterInput.checked ? '1' : '0');

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
                    successMsg.textContent = data.code === 'already_registered'
                        ? el.dataset.msgAlreadyRegistered
                        : el.dataset.msgSuccess;
                    successMsg.style.display = 'block';
                } else {
                    showError(data.code || 'unknown');
                    form.style.display = 'block';
                }
            })
            .catch(function () {
                spinner.style.display = 'none';
                showError('network');
                form.style.display = 'block';
            });
        });

        function showError(code) {
            var texts = {
                'invalid_email': el.dataset.errInvalidEmail,
                'mail_failed':   el.dataset.errMailFailed,
                'network':       el.dataset.errNetwork,
                'unknown':       el.dataset.errUnknown
            };
            errorTitle.textContent = el.dataset.msgError;
            errorText.textContent = texts[code] || texts['unknown'];
            errorMsg.style.display = 'block';
        }
    });
});
