document.addEventListener('carbon:fields.booted', function() {
    (function($) {
        // Initialize Select2 for the categories dropdown
        // Ensure the element exists before trying to initialize Select2 on it.
        if ($('.select2-multiple').length > 0) {
            $('.select2-multiple').select2({
                placeholder: "Select categories",
                allowClear: true
            });
        }
    })(jQuery);
});
