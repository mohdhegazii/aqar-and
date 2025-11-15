jQuery(document).ready(function($) {
    // --- CONFLICT RESOLUTION: FINAL & DEFINITIVE FIX ---
    // The old script.js uses a delegated event handler. We must attach our own handler
    // and immediately stop propagation to prevent the old, conflicting handler from running.

    let currentForm;
    const modal = $('#confirmation-modal');
    const responseBox = $('.responsebox');
    const responseHere = $('.responsehere');
    const confirmSendButton = $('#confirm-send-button');

    function isRtl() {
        return $('html').attr('dir') === 'rtl';
    }

    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    function validatePhone(phone) {
        const re = /^[0-9]{11,17}$/;
        return re.test(String(phone));
    }

    // We attach our new handler to the document to ensure it fires.
    $(document).on('submit', 'form.siteform', function(e) {
        // --- THE DEFINITIVE FIX ---
        // Stop the default action AND prevent any other handlers from running.
        e.preventDefault();
        e.stopImmediatePropagation();

        currentForm = $(this);
        const formElement = currentForm.get(0);

        responseBox.removeClass('active');

        if (!formElement.checkValidity()) {
            formElement.reportValidity();
            return;
        }

        const phone = currentForm.find('input[name="phone"]').val();
        const email = currentForm.find('input[name="email"]').val();

        if (!validatePhone(phone)) {
            responseHere.text(isRtl() ? 'برجاء التأكد من ادخال رقم هاتف صحيح.' : 'Please make sure to enter a valid phone number.');
            responseBox.addClass('active');
            return;
        }

        if (email.trim() && !validateEmail(email)) {
            responseHere.text(isRtl() ? 'برجاء التأكد من ادخال بريد الكتروني صحيح.' : 'Please make sure to enter a valid email.');
            responseBox.addClass('active');
            return;
        }

        const name = currentForm.find('input[name="name"]').val();
        const message = currentForm.find('textarea[name="special_request"]').val();

        $('#confirm-name').text(name);
        $('#confirm-phone').text(phone);
        $('#confirm-email').text(email || (isRtl() ? 'لا يوجد' : 'Not provided'));
        $('#confirm-message').text(message);

        modal.fadeIn();
    });

    confirmSendButton.on('click', function() {
        if (currentForm) {
            const button = $(this);
            const originalButtonText = button.text();

            button.prop('disabled', true).text(isRtl() ? 'جار الإرسال...' : 'Sending...');

            $.ajax({
                type: 'POST',
                url: global.ajax,
                data: currentForm.serialize(),
                dataType: 'json',
                success: function(response) {
                    modal.fadeOut();
                    if (response.success) {
                        window.location.href = response.data.redirect;
                    } else {
                        responseHere.text(response.data.message);
                        responseBox.addClass('active');
                    }
                },
                error: function() {
                    modal.fadeOut();
                    responseHere.text(isRtl() ? 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.' : 'An unexpected error occurred. Please try again.');
                    responseBox.addClass('active');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalButtonText);
                    currentForm = null;
                }
            });
        }
    });

    $('#edit-button').on('click', function() {
        modal.fadeOut();
        currentForm = null;
    });

    $('.confirmation-modal-close').on('click', function() {
        modal.fadeOut();
        currentForm = null;
    });

    $('.responsecolse').on('click', function() {
        responseBox.removeClass('active');
    });

    $(window).on('click', function(e) {
        if ($(e.target).is(modal)) {
            modal.fadeOut();
            currentForm = null;
        }
    });

    $('.imp-links').on('click', '.more-less-button', function() {
        const button = $(this);
        const listId = button.attr('aria-controls');
        if (!listId) {
            return;
        }

        const escapeSelector = $.escapeSelector || function(sel) {
            return sel.replace(/([ #;?%&,.+*~\'":^$\[\]()=>|\/])/g, '\\$1');
        };

        const listSelector = '#' + escapeSelector(listId);
        const linksList = $(listSelector);

        if (!linksList.length) {
            return;
        }

        const isExpanded = button.attr('aria-expanded') === 'true';
        const moreText = button.data('more-text') || button.text();
        const lessText = button.data('less-text') || button.text();

        if (isExpanded) {
            linksList.slideUp(200, function() {
                linksList.attr('aria-hidden', 'true');
            });
            button.attr('aria-expanded', 'false').text(moreText);
        } else {
            linksList.slideDown(200, function() {
                linksList.attr('aria-hidden', 'false');
            });
            button.attr('aria-expanded', 'true').text(lessText);
        }
    });
});
