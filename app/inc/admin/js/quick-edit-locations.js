(function($) {
    'use strict';

    // A shared utility to populate a select element with options
    function populateSelect(select, options, placeholder) {
        select.empty().append($('<option>', { value: '', text: placeholder }));
        $.each(options, function(value, text) {
            if (value === '') {
                return;
            }
            var label = (typeof text === 'object' && text.hasOwnProperty('label')) ? text.label : text;
            select.append($('<option>', { value: value, text: label }));
        });
    }

    // --- Quick Edit Population ---
    if (typeof inlineEditPost !== 'undefined') {
        var originalEdit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            originalEdit.apply(this, arguments);

            var post_id = (typeof id === 'object') ? parseInt(this.getId(id), 10) : 0;
            if (post_id > 0) {
                var editRow = $('#edit-' + post_id);
                var postRow = $('#post-' + post_id);
                var locationData = $('.jawda-location-data', postRow);

                fetchAndSetInitialValues(editRow, {
                    gov: locationData.data('gov-id'),
                    city: locationData.data('city-id'),
                    district: locationData.data('district-id')
                });
            }
        };
    }

    function fetchAndSetInitialValues(container, ids) {
        var govSelect = $('.jawda-governorate-select', container);
        $.ajax({
            url: CF_DEP.ajax_url,
            data: { action: 'cf_dep_get_governorates', nonce: CF_DEP.nonce, lang: (CF_DEP.language || 'both') },
            success: function(response) {
                if (response.success) {
                    populateSelect(govSelect, response.data.options, CF_DEP.i18n.select_gov);
                    govSelect.val(ids.gov);
                    if (ids.gov) {
                        fetchCities(container, ids.city, ids.district);
                    }
                }
            }
        });
    }

    // --- Bulk Edit & Shared Logic ---
    function attachLocationListeners() {
        $('body').on('change', '.jawda-governorate-select', function() {
            var container = $(this).closest('.inline-edit-row, #bulk-edit');
            fetchCities(container);
        });
        $('body').on('change', '.jawda-city-select', function() {
            var container = $(this).closest('.inline-edit-row, #bulk-edit');
            fetchDistricts(container);
        });
    }

    function fetchCities(container, city_id_to_select, district_id_to_select) {
        var gov_id = $('.jawda-governorate-select', container).val();
        var citySelect = $('.jawda-city-select', container);
        var districtSelect = $('.jawda-district-select', container);

        citySelect.empty().html('<option value="">' + CF_DEP.i18n.loading + '</option>');
        districtSelect.empty().html('<option value="">' + CF_DEP.i18n.select_city_first + '</option>');

        if (!gov_id) {
            citySelect.empty().html('<option value="">' + CF_DEP.i18n.select_gov_first + '</option>');
            return;
        }

        $.ajax({
            url: CF_DEP.ajax_url,
            data: { action: 'cf_dep_get_cities', nonce: CF_DEP.nonce, gov_id: gov_id, lang: (CF_DEP.language || 'both') },
            success: function(response) {
                if (response.success) {
                    populateSelect(citySelect, response.data.options, CF_DEP.i18n.select_city);
                    if (city_id_to_select) {
                        citySelect.val(city_id_to_select);
                        if (district_id_to_select) {
                            fetchDistricts(container, district_id_to_select);
                        }
                    }
                }
            }
        });
    }

    function fetchDistricts(container, district_id_to_select) {
        var city_id = $('.jawda-city-select', container).val();
        var districtSelect = $('.jawda-district-select', container);

        districtSelect.empty().html('<option value="">' + CF_DEP.i18n.loading + '</option>');
        if (!city_id) {
            districtSelect.empty().html('<option value="">' + CF_DEP.i18n.select_city_first + '</option>');
            return;
        }

        $.ajax({
            url: CF_DEP.ajax_url,
            data: { action: 'cf_dep_get_districts', nonce: CF_DEP.nonce, city_id: city_id, lang: (CF_DEP.language || 'both') },
            success: function(response) {
                if (response.success) {
                    populateSelect(districtSelect, response.data.options, CF_DEP.i18n.select_district);
                    if (district_id_to_select) {
                        districtSelect.val(district_id_to_select);
                    }
                }
            }
        });
    }

    // --- Bulk Edit Save ---
    $('#bulk_edit').on('click', function() {
        var bulk_row = $('#bulk-edit');
        var post_ids = [];
        bulk_row.find('#bulk-titles').children().each(function() {
            post_ids.push($(this).attr('id').replace(/^(ttle)/i, ''));
        });

        var gov_id = bulk_row.find('.jawda-governorate-select').val();
        var city_id = bulk_row.find('.jawda-city-select').val();
        var district_id = bulk_row.find('.jawda-district-select').val();

        if (gov_id || city_id || district_id) {
            $.ajax({
                url: CF_DEP.ajax_url,
                type: 'POST',
                data: {
                    action: 'jawda_bulk_edit_save_locations',
                    nonce: $('#location_bulk_edit_nonce').val(),
                    post_ids: post_ids,
                    loc_governorate_id: gov_id,
                    loc_city_id: city_id,
                    loc_district_id: district_id,
                }
            });
        }
    });


    $(function() {
        attachLocationListeners();
        // Populate bulk edit governorates on page load
        $.ajax({
            url: CF_DEP.ajax_url,
            data: { action: 'cf_dep_get_governorates', nonce: CF_DEP.nonce, lang: (CF_DEP.language || 'both') },
            success: function(response) {
                if (response.success) {
                    var bulkGovSelect = $('#bulk-edit .jawda-governorate-select');
                    var noChangeOption = bulkGovSelect.find('option:first-child');
                    populateSelect(bulkGovSelect, response.data.options, CF_DEP.i18n.select_gov);
                    bulkGovSelect.prepend(noChangeOption);
                }
            }
        });
    });

})(jQuery);
