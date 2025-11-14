(function ($) {
  'use strict';

  var STYLE_ID = 'aqarand-property-type-tree-style';

  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) {
      return;
    }

    var css = '' +
      '.aqarand-type-tree{margin-top:12px;display:grid;gap:16px;}' +
      '.aqarand-type-tree.is-empty{padding:16px;border:1px solid #dcdcde;border-radius:4px;background:#fff;font-style:italic;color:#646970;text-align:center;}' +
      '.aqarand-type-actions{display:flex;justify-content:flex-end;gap:8px;}' +
      '.aqarand-type-category{border:1px solid #dcdcde;border-radius:6px;padding:12px;background:#f6f7f7;transition:border-color .2s ease,box-shadow .2s ease,background-color .2s ease;}' +
      '.aqarand-type-category.is-active{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1;background:#fff;}' +
      '.aqarand-type-category__title{font-weight:600;margin:0 0 10px;font-size:14px;color:#1d2327;}' +
      '.aqarand-type-category__items{display:grid;gap:6px;}' +
      '.aqarand-type-category__empty{margin:0;font-style:italic;color:#646970;}' +
      '.aqarand-type-item{display:flex;align-items:center;gap:8px;font-size:13px;}' +
      '.aqarand-type-item input{margin:0;}' +
      '.aqarand-type-item label{cursor:pointer;}' +
      '.aqarand-type-clear{margin-bottom:4px;}';

    $('<style>', { id: STYLE_ID, text: css }).appendTo('head');
  }

  function uniqueStrings(values) {
    var list = Array.isArray(values) ? values : [];
    var seen = Object.create(null);
    var result = [];

    for (var i = 0; i < list.length; i += 1) {
      var value = list[i];
      var key = String(value);

      if (!seen[key]) {
        seen[key] = true;
        result.push(key);
      }
    }

    return result;
  }

  function normalizeTree(tree) {
    if (!Array.isArray(tree)) {
      return [];
    }

    var sanitized = [];

    for (var i = 0; i < tree.length; i += 1) {
      var category = tree[i];

      if (!category || typeof category !== 'object') {
        continue;
      }

      var id = category.id !== undefined && category.id !== null ? String(category.id) : '';
      var name = category.name ? String(category.name) : '';
      var rawTypes = Array.isArray(category.types) ? category.types : [];
      var types = [];

      for (var j = 0; j < rawTypes.length; j += 1) {
        var type = rawTypes[j];

        if (!type || typeof type !== 'object' || type.id === undefined || type.id === null) {
          continue;
        }

        var typeId = String(type.id);
        var typeName = type.name ? String(type.name) : typeId;

        types.push({
          id: typeId,
          name: typeName
        });
      }

      sanitized.push({
        id: id,
        name: name,
        types: types
      });
    }

    return sanitized;
  }

  function formatCategoryLabel(label, template, identifier) {
    if (label) {
      return label;
    }

    var value = identifier || '';

    if (template && template.indexOf('%s') !== -1) {
      return template.replace('%s', value);
    }

    return 'Category #' + value;
  }

  function buildTypeChooser($select, tree, strings, options) {
    if (!$select || !$select.length) {
      return null;
    }

    ensureStyles();

    var settings = options || {};
    var isMultiple = !!settings.isMultiple;
    var fallbackTemplate = settings.fallbackTemplate ? String(settings.fallbackTemplate) : '';
    var selectedValues = [];

    if (isMultiple) {
      selectedValues = uniqueStrings(settings.selected || $select.val());
    } else {
      var single = settings.selected && settings.selected.length ? settings.selected[0] : $select.val();

      if (single !== undefined && single !== null && single !== '') {
        selectedValues = [String(single)];
      }
    }

    var selectedLookup = Object.create(null);
    for (var i = 0; i < selectedValues.length; i += 1) {
      selectedLookup[String(selectedValues[i])] = true;
    }

    var container = $('<div/>', { 'class': 'aqarand-type-tree' });
    var categories = Array.isArray(tree) ? tree : [];
    var clearLabel = strings.clear_selection || 'Clear selection';
    var noCategoriesLabel = strings.no_categories || 'No categories available.';
    var noTypesLabel = strings.no_types || 'No property types available for this category.';

    $select.addClass('aqarand-type-select--hidden').hide().after(container);

    if (!categories.length) {
      container.addClass('is-empty').append(
        $('<p/>', { 'class': 'aqarand-type-tree__empty', text: noCategoriesLabel })
      );
    }

    if (!isMultiple) {
      container.append(
        $('<div/>', { 'class': 'aqarand-type-actions' }).append(
          $('<button/>', {
            type: 'button',
            'class': 'button button-secondary aqarand-type-clear',
            text: clearLabel
          }).on('click', function (event) {
            event.preventDefault();
            container.find('input[type="radio"]').prop('checked', false);
            syncSelection();
          })
        )
      );
    }

    for (var index = 0; index < categories.length; index += 1) {
      var category = categories[index];
      var categoryId = category.id ? String(category.id) : '';
      var categoryLabel = formatCategoryLabel(category.name, fallbackTemplate, categoryId || String(index + 1));
      var categoryWrapper = $('<div/>', {
        'class': 'aqarand-type-category',
        'data-category-id': categoryId
      });
      var itemsWrapper = $('<div/>', { 'class': 'aqarand-type-category__items' });
      var types = Array.isArray(category.types) ? category.types : [];

      categoryWrapper.append(
        $('<div/>', { 'class': 'aqarand-type-category__title', text: categoryLabel })
      );

      if (!types.length) {
        itemsWrapper.append(
          $('<p/>', { 'class': 'aqarand-type-category__empty', text: noTypesLabel })
        );
      } else {
        for (var t = 0; t < types.length; t += 1) {
          var type = types[t];
          var typeId = type.id ? String(type.id) : '';

          if (!typeId) {
            continue;
          }

          var typeLabel = type.name ? String(type.name) : typeId;
          var inputId = 'aqarand-type-' + (categoryId || ('cat-' + index)) + '-' + typeId + '-' + t;
          inputId = inputId.replace(/[^A-Za-z0-9_-]+/g, '-');

          var input = $('<input/>', {
            type: isMultiple ? 'checkbox' : 'radio',
            id: inputId,
            value: typeId
          });

          if (isMultiple && selectedLookup[typeId]) {
            input.prop('checked', true);
          } else if (!isMultiple && selectedValues.length && selectedValues[0] === typeId) {
            input.prop('checked', true);
          }

          var label = $('<label/>', {
            'for': inputId,
            text: typeLabel
          });

          itemsWrapper.append(
            $('<div/>', { 'class': 'aqarand-type-item' }).append(input, label)
          );
        }
      }

      categoryWrapper.append(itemsWrapper);
      container.append(categoryWrapper);
    }

    function syncSelection() {
      if (isMultiple) {
        var values = [];

        container.find('input[type="checkbox"]:checked').each(function () {
          values.push($(this).val());
        });

        var unique = uniqueStrings(values);
        $select.val(unique).trigger('change');
      } else {
        var value = container.find('input[type="radio"]:checked').val() || '';
        $select.val(value).trigger('change');
      }
    }

    container.on('change', 'input[type="checkbox"], input[type="radio"]', function () {
      syncSelection();
    });

    syncSelection();

    return {
      highlight: function (categoryId) {
        var key = String(categoryId || '');

        container.find('.aqarand-type-category').each(function () {
          var $category = $(this);
          var matches = key && $category.attr('data-category-id') === key;
          $category.toggleClass('is-active', matches);
        });
      }
    };
  }

  $(function () {
    if (typeof AqarProjectMeta === 'undefined') {
      return;
    }

    var config = AqarProjectMeta;
    var strings = (config.strings && typeof config.strings === 'object') ? config.strings : {};
    var fallbackTemplate = strings.fallback_category ? String(strings.fallback_category) : '';
    var tree = normalizeTree(config.property_type_tree);

    var $main = $('select[name="carbon_fields_compact_input[jawda_main_category_id]"]');
    var $multi = $('select[name="carbon_fields_compact_input[jawda_property_type_ids][]"], select[name="carbon_fields_compact_input[jawda_property_type_ids]"]').first();
    var $single = $('select[name="carbon_fields_compact_input[jawda_property_type_id]"]').first();

    var multiChooser = null;
    var singleChooser = null;

    if ($multi.length) {
      var initialMulti = uniqueStrings($multi.val());

      if (!initialMulti.length && Array.isArray(config.selected_property_types)) {
        initialMulti = uniqueStrings(config.selected_property_types);
      }

      multiChooser = buildTypeChooser($multi, tree, strings, {
        isMultiple: true,
        selected: initialMulti,
        fallbackTemplate: fallbackTemplate
      });
    }

    if ($single.length) {
      var rawSingle = $single.val();
      var selectedSingle = '';

      if (rawSingle !== undefined && rawSingle !== null && rawSingle !== '') {
        selectedSingle = String(rawSingle);
      } else if (config.selected_property_type) {
        selectedSingle = String(config.selected_property_type);
      }

      singleChooser = buildTypeChooser($single, tree, strings, {
        isMultiple: false,
        selected: selectedSingle ? [selectedSingle] : [],
        fallbackTemplate: fallbackTemplate
      });
    }

    if ($main.length) {
      var refreshHighlight = function () {
        var value = String($main.val() || '');

        if (multiChooser && typeof multiChooser.highlight === 'function') {
          multiChooser.highlight(value);
        }

        if (singleChooser && typeof singleChooser.highlight === 'function') {
          singleChooser.highlight(value);
        }
      };

      $main.on('change.aqarandTypeTree', refreshHighlight);
      refreshHighlight();
    }
  });
})(jQuery);
