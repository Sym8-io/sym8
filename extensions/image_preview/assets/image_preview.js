/**
* Image Preview
*
* @author Deux Huit Huit
**/


// In a nut shell
(function ($, undefined) {

    "use strict";

    var optionsSelector = '.field-image_preview_settings';

    var selectors = '.field-upload .field-image_upload .field-uniqueupload .field-multilingual_upload_field .field-multilingual_upload .field-multilingual_image_upload .field-multiupload'.split(' ');

    var defaultValues = {
        width: 75,
        height: 0,
        resize: 1,
        position: 5,
        absolute: false,
        isDefault: true
    };

    var params = {
        table: $.extend({}, defaultValues),
        associations: $.extend({}, defaultValues),
        entry: $.extend({}, defaultValues, {width: 100}),
    };

    var WORKSPACE = 'workspace';
    var SVG = '.svg';

    var createUrl = function (imgSrc, params) {

        var newSrc = 'image/{resize}/{width}/{height}{position}';

        newSrc = newSrc.replace('{resize}', params.resize);
        newSrc = newSrc.replace('{width}', params.width);
        newSrc = newSrc.replace('{height}', params.height);
        newSrc = newSrc.replace('{position}', params.resize === 1 ? '' : '/' + params.position);

        if (!!~imgSrc.indexOf(SVG)) {
            newSrc = imgSrc;
        } else {
            if (!!~imgSrc.indexOf(WORKSPACE)) {
                newSrc = imgSrc.replace(WORKSPACE, newSrc);
            } else {
                newSrc =  '/' + newSrc + imgSrc;
            }
        }

        return newSrc;
    };

    var getParameters = function (classes, defaults) {
        var params = $.extend({}, defaults);

        $.each(classes, function _forEachClass(index, val) {
            var node = $(optionsSelector).find('*[data-field-classes*="' + val + '"]');

            if (!node.length) {
                // no param found, try a param valid for all
                node = $(optionsSelector).find('*[data-field-classes="*"]');
            }

            if (!!node.length) {

                var
                width = parseInt(node.attr('data-width'), 10),
                height = parseInt(node.attr('data-height'), 10),
                resize = parseInt(node.attr('data-resize'), 10),
                position = parseInt(node.attr('data-position'), 10),
                absolute = node.attr('data-absolute') == 'yes';

                params.width = width || (!!height ? 0 : params.width);
                params.height = height || (!!width ? 0 : params.height);
                params.resize = resize || params.resize;
                params.position = position || params.position;
                params.absolute = absolute || params.absolute;
                params.isDefault = false;

                return false; //exit for
            }
            return true;
        });

        return params;
    };

    function looksLikeImage(url) {
        return /\.(bmp|jpe?g|png|gif|webp|avif|svg)(\?.*)?$/i.test(url);
    }

    var addImage = function(t, defaults) {
        if (!t || !t.length) {
            return t;
        }

        return t.each(function _eachField() {
            var container = $(this);
            var a = container.find('a');

            a.each(function (i, a) {
                a = $(a);
                if (!!a.find('img').length) {
                    return;
                }
                var img = new Image();
                //var imgSrc = a.attr('data-path') || a.attr('href');
                var imgSrc = a.attr('href');
                if (!looksLikeImage(imgSrc)) {
                    return; // skip non-image files
                }

                var classes = (a.closest('td').attr('class') || a.closest('div[id]').attr('id')  || '').split(' ');
                var _imageLoaded = function  (e, p, src) {
                    var lcss = {
                        padding: 0,
                        display: 'inline-block',
                        maxWidth: '100%',
                        textDecoration: 'none',
                        border: 'none'
                    };
                    var i = $('<img />').attr('src', src);

                    if (!!p.absolute) {
                        i.css({position:'absolute'});
                    }
                    i.css({
                        display: 'block',
                        maxWidth: '100%'
                    });

                    a.css(lcss).empty().append(i);
                };

                if (!!imgSrc && !!classes.length) {
                    // check we have the full path
                    if (!~imgSrc.indexOf('.')) {
                        imgSrc += '/' + a.text();
                    }

                    var p = getParameters(classes, defaults);
                    var url = createUrl(imgSrc, p);

                    // bind load event
                    img.addEventListener('load', function (e) { _imageLoaded(e, p, this.src); });

                    // load the image
                    img.src = url;
                }
            });
        });
    };

    var init = function () {

        // hide field
        var fields = $(optionsSelector);

        // entry mode
        fields.filter('div').css({
            height: 1,
            minHeight: 0,
            margin: 0
        });

        // table mode
        fields.filter('th, td').css({display: 'none'});

        // show images
        $.each(selectors, function _eachSelector() {
            var sel = this;

            // list view
            addImage($('td' + sel), params.table);

            // detail view
            addImage($('div' + sel), params.entry);

            // association view
            addImage($('li' + sel), params.associations);
        });
    };

    $(init);

})(jQuery);
