jQuery(document).ready(function($) {
    function fetchPropertyTypes() {
        var mainCategorySelect = $('#jawda_main_category_id');
        var propertyTypeSelect;
        var selectedPropertyTypes = [];

        if ($('#jawda_property_type_ids').length) {
            propertyTypeSelect = $('#jawda_property_type_ids');
            selectedPropertyTypes = propertyTypeSelect.data('selected') || [];
        } else {
            propertyTypeSelect = $('#jawda_property_type_id');
            selectedPropertyTypes.push(propertyTypeSelect.data('selected'));
        }

        var mainCategoryId = mainCategorySelect.val();

        if (!mainCategoryId) {
            propertyTypeSelect.html('<option value="">— اختر التصنيف الرئيسي أولًا — / — Select Main Category First —</option>');
            return;
        }

        propertyTypeSelect.html('<option value="">جاري التحميل... / Loading...</option>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_category_property_types',
                main_category_id: mainCategoryId,
                nonce: $('#jawda_category_meta_nonce').val()
            },
            success: function(response) {
                propertyTypeSelect.empty();
                if (response.success && response.data.length) {
                    propertyTypeSelect.append('<option value="">— اختر نوع الوحدة — / — Select Property Type —</option>');
                    $.each(response.data, function(index, item) {
                        var selected = $.inArray(item.id.toString(), selectedPropertyTypes) !== -1 ? ' selected="selected"' : '';
                        propertyTypeSelect.append('<option value="' + item.id + '"' + selected + '>' + item.name + '</option>');
                    });
                } else {
                    propertyTypeSelect.append('<option value="">— لا توجد أنواع متاحة — / — No Property Types Found —</option>');
                }
            }
        });
    }

    // Trigger on page load if a main category is already selected
    if ($('#jawda_main_category_id').val()) {
        fetchPropertyTypes();
    }

    // Trigger on change
    $('#jawda_main_category_id').on('change', fetchPropertyTypes);
});
