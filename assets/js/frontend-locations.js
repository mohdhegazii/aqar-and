jQuery(document).ready(function($) {
    'use strict';

    var ajax_url = CF_DEP.ajax_url;
    var nonce = CF_DEP.nonce;

    $('.jawda-governorate-select-frontend').on('change', function() {
        var gov_id = $(this).val();
        var city_select = $('.jawda-city-select-frontend');
        var district_select = $('.jawda-district-select-frontend');

        city_select.prop('disabled', true).empty().append('<option value="">' + CF_DEP.i18n.loading + '</option>');
        district_select.prop('disabled', true).empty().append('<option value="">' + CF_DEP.i18n.select_city_first + '</option>');

        if (!gov_id) {
            city_select.empty().append('<option value="">' + CF_DEP.i18n.select_gov_first + '</option>');
            return;
        }

        $.ajax({
            url: ajax_url,
            type: 'GET',
            data: {
                action: 'cf_dep_get_cities',
                nonce: nonce,
                gov_id: gov_id,
                lang: (CF_DEP.language || 'ar')
            },
            success: function(response) {
                if (response.success) {
                    city_select.prop('disabled', false).empty().append('<option value="">' + CF_DEP.i18n.select_city + '</option>');
                    $.each(response.data.options, function(value, details) {
                        if(value) { // Don't add the placeholder again
                            city_select.append($('<option>', { value: value, text: details.label }));
                        }
                    });
                }
            }
        });
    });

    $('.jawda-city-select-frontend').on('change', function() {
        var city_id = $(this).val();
        var district_select = $('.jawda-district-select-frontend');

        district_select.prop('disabled', true).empty().append('<option value="">' + CF_DEP.i18n.loading + '</option>');

        if (!city_id) {
            district_select.empty().append('<option value="">' + CF_DEP.i18n.select_city_first + '</option>');
            return;
        }

        $.ajax({
            url: ajax_url,
            type: 'GET',
            data: {
                action: 'cf_dep_get_districts',
                nonce: nonce,
                city_id: city_id,
                lang: (CF_DEP.language || 'ar')
            },
            success: function(response) {
                if (response.success) {
                    district_select.prop('disabled', false).empty().append('<option value="">' + CF_DEP.i18n.select_district + '</option>');
                    $.each(response.data.options, function(value, details) {
                         if(value) { // Don't add the placeholder again
                            district_select.append($('<option>', { value: value, text: details.label }));
                        }
                    });
                }
            }
        });
    });
});
