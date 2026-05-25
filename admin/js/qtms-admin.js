(function ($) {
    'use strict';

    var QTMS = {
        init: function () {
            this.brandTitle();
            this.bindRowCount();
            this.bindRowToggle();
            this.bindDirection();
            this.bindRange();
            this.bindMediaUpload();
            this.bindAddItem();
            this.bindRemoveItem();
            this.bindSortable();
            this.bindCopyShortcode();
            this.initColorPickers();
        },

        brandTitle: function () {
            var el = document.querySelector('.wrap > .wp-heading-inline') || document.querySelector('.wrap > h1');
            if (el && el.textContent.indexOf('Qaiyo') === -1) {
                var span = document.createElement('span');
                span.className = 'qtms-brand';
                span.textContent = 'Qaiyo';
                el.insertBefore(document.createTextNode(' '), el.firstChild);
                el.insertBefore(span, el.firstChild);
            }
        },

        bindRowCount: function () {
            $('input[name="qtms_row_count"]').on('change', function () {
                var count = parseInt($(this).val(), 10);
                $('.qtms-radio-label').removeClass('active');
                $(this).closest('.qtms-radio-label').addClass('active');
                $('.qtms-row-panel').each(function () {
                    var row = parseInt($(this).data('row'), 10);
                    $(this).toggle(row < count);
                });
            });
        },

        bindRowToggle: function () {
            $(document).on('click', '.qtms-row-header', function () {
                $(this).closest('.qtms-row-panel').toggleClass('collapsed');
            });
        },

        bindDirection: function () {
            $(document).on('click', '.qtms-dir-btn', function (e) {
                e.preventDefault();
                var $group = $(this).closest('.qtms-direction-toggle');
                $group.find('.qtms-dir-btn').removeClass('active');
                $(this).addClass('active');
                $group.find('input[type="hidden"]').val($(this).data('dir'));
            });
        },

        bindRange: function () {
            $(document).on('input', '.qtms-range', function () {
                $(this).siblings('.qtms-range-val').text($(this).val());
            });
        },

        bindMediaUpload: function () {
            $(document).on('click', '.qtms-media-upload', function (e) {
                e.preventDefault();
                var $field = $(this).closest('.qtms-media-field');
                var frame = wp.media({
                    title: qtmsAdmin.selectImage,
                    button: { text: qtmsAdmin.useImage },
                    multiple: false,
                    library: {
                        type: ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml']
                    }
                });

                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var url = attachment.sizes && attachment.sizes.thumbnail
                        ? attachment.sizes.thumbnail.url
                        : attachment.url;
                    $field.find('.qtms-media-id').val(attachment.id);
                    $field.find('.qtms-media-preview img').attr('src', url);
                    $field.find('.qtms-media-preview').show();
                    $field.find('.qtms-media-upload').hide();
                });

                frame.open();
            });

            $(document).on('click', '.qtms-media-remove', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $field = $(this).closest('.qtms-media-field');
                $field.find('.qtms-media-id').val(0);
                $field.find('.qtms-media-preview').hide();
                $field.find('.qtms-media-upload').show();
            });
        },

        bindAddItem: function () {
            $(document).on('click', '.qtms-add-item', function (e) {
                e.preventDefault();
                var $panel = $(this).closest('.qtms-row-panel');
                var rowIdx = parseInt($panel.data('row'), 10);
                var $list = $panel.find('.qtms-items-list');
                var itemIdx = $list.children('.qtms-item-card').length;

                var html = $('#qtms-item-template').html();
                html = html.replace(/__ROW__/g, rowIdx);
                html = html.replace(/__IDX__/g, itemIdx);

                var $card = $(html);
                $list.append($card);

                $card.find('.qtms-color-picker').each(function () {
                    $(this).wpColorPicker();
                });

                QTMS.reindexItems($list, rowIdx);
            });
        },

        bindRemoveItem: function () {
            $(document).on('click', '.qtms-item-remove', function (e) {
                e.preventDefault();
                if (!confirm(qtmsAdmin.confirmDelete)) return;
                var $card = $(this).closest('.qtms-item-card');
                var $list = $card.closest('.qtms-items-list');
                var rowIdx = parseInt($list.data('row'), 10);
                $card.fadeOut(200, function () {
                    $(this).remove();
                    QTMS.reindexItems($list, rowIdx);
                });
            });
        },

        bindSortable: function () {
            $('.qtms-items-list').sortable({
                handle: '.qtms-item-drag-handle',
                placeholder: 'qtms-item-card ui-sortable-placeholder',
                tolerance: 'pointer',
                opacity: 0.9,
                update: function () {
                    var $list = $(this);
                    var rowIdx = parseInt($list.data('row'), 10);
                    QTMS.reindexItems($list, rowIdx);
                }
            });
        },

        reindexItems: function ($list, rowIdx) {
            $list.children('.qtms-item-card').each(function (i) {
                $(this).find('[name]').each(function () {
                    var name = $(this).attr('name');
                    name = name.replace(
                        /qtms_rows\[\d+\]\[items\]\[\d+\]/,
                        'qtms_rows[' + rowIdx + '][items][' + i + ']'
                    );
                    $(this).attr('name', name);
                });
            });
        },

        initColorPickers: function () {
            $('.qtms-color-picker').each(function () {
                $(this).wpColorPicker();
            });
        },

        bindCopyShortcode: function () {
            $(document).on('click', '.qtms-copy-btn', function (e) {
                e.preventDefault();
                var text = $(this).data('clipboard');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                } else {
                    var $temp = $('<input>');
                    $('body').append($temp);
                    $temp.val(text).select();
                    document.execCommand('copy');
                    $temp.remove();
                }
                var $btn = $(this);
                $btn.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
                setTimeout(function () {
                    $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
                }, 1500);
            });
        }
    };

    $(document).ready(function () {
        QTMS.init();
    });

})(jQuery);
