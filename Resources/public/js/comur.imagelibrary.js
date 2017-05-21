$(function () {
    $('.fileinput-button').click(function (event) {
        if ($(event.target).is("span")) {
            $('#image_upload_file').click();
            event.stopPropagation();
            return false;
        }
    });

});

function initializeImageManager(id, options) {
    if ((typeof options.uploadConfig.library === 'undefined' || options.uploadConfig.library)
        && typeof options.uploadConfig.libraryDir !== 'undefined'
        && options.uploadConfig.libraryRoute !== 'undefined'
        && (options.uploadConfig.showLibrary === 'undefined'
        || options.uploadConfig.showLibrary)) {
        $('#select-existing').removeClass('hidden');
        $('#image_upload_tabs li:nth-child(2)').show();
        $('#existing-images .image-container').remove();
        $.ajax({
            url: Routing.generate(options.uploadConfig.libraryRoute),
            data: {dir: options.uploadConfig.libraryDir},
            success: function (response) {
                var files = response.files;
                for (var i = files.length - 1; i >= 0; i--) {
                    var now = new Date().getTime();
                    $('#existing-images').append('<div class="image-container" data-src="' + files[i] + '"><img src="/' + options.uploadConfig.webDir + '/' + response['thumbsDir'] + '/' + files[i] + '?' + now + '"/></div>');
                }

                $('.image-container').click(function () {
                    $('#selected_image').val($(this).attr('data-src'));
                    initJCrop(id, options);
                });

            },
            type: 'POST'
        });
    }
    else {
        $('#select-existing').addClass('hidden');
        $('#image_upload_tabs li:nth-child(2)').hide();
    }
    $('#image_upload_tabs li:nth-child(3)').hide();
    var url = Routing.generate(options.uploadConfig.uploadRoute);
    $('#image_upload_file').fileupload({
        url: url,
        dataType: 'json',
        formData: {'config': JSON.stringify(options)},
        dropZone: $('#image_upload_drop_zone'),
        done: function (e, data) {
            if (data.result['image_upload_file'][0].error) {
                $('#image_upload_widget_error').text(data.result['image_upload_file'][0].error);
                $('#image_upload_widget_error').parent().removeClass('hidden');
            }
            else {
                $('#image_upload_widget_error').text('');
                $('#image_upload_widget_error').parent().addClass('hidden');
                $('#selected_image').val(data.result['image_upload_file'][0].name);
                initJCrop(id, options);
            }

        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#image_file_upload_progress .progress-bar').css('width', progress + '%');
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');
    $('#image_crop_go_now').unbind('click');
    $('#image_crop_go_now').click(function () {
        cropImage(id, options)
    });
    $('#image_crop_cancel').click(function () {
        $('#selected_image').val('');
        $('#image_crop_go_now').addClass('hidden');
        $('#image_crop_cancel').addClass('hidden');
        $('#image_upload_tabs a:first').tab('show');
    });
}

function destroyImageManager() {
    $('#image_crop_go_now').addClass('hidden');
    $('#image_file_upload_progress .progress-bar').css('width', '0%');
    $('#image_upload_tabs a:first').tab('show');
    $('#image_upload_file').fileupload('destroy');
    destroyJCrop();
    $('#image_crop_go_now').unbind('click');
    $('#image_crop_cancel').addClass('hidden');
}

var api;
var c;

function updateCoords(coords) {
    c = coords;
}

function initJCrop(id, options) {
    if (api) {
        api.destroy();
    }
    var now = new Date().getTime();
    $('#image_preview img').remove();
    $('#image_preview').html('<img src="/' + options.uploadConfig.webDir + '/' + $('#selected_image').val() + '?' + now + '" id="image_preview_image"/>');
    $('#image_preview img').load(function () {
        $('#image_preview img').Jcrop({
            bgOpacity: 0.8,
            bgColor: 'white',
            addClass: 'jcrop-dark',
            aspectRatio: options.cropConfig.aspectRatio ? options.cropConfig.minWidth / options.cropConfig.minHeight : false,
            minSize: [options.cropConfig.minWidth, options.cropConfig.minHeight],
            boxWidth: 600,
            boxHeight: 400,
            onSelect: updateCoords
        }, function () {
            api = this;
            api.setOptions({bgFade: true});
            api.ui.selection.addClass('jcrop-selection');
        });

        if (($('#image_preview_image').width() / $('#image_preview_image').height()) >= (options.cropConfig.minWidth / options.cropConfig.minHeight)) {
            var selectionWidth = parseInt($('#image_preview_image').height() / (options.cropConfig.minHeight / options.cropConfig.minWidth));
            var selectionHeight = $('#image_preview_image').height();
        } else {
            var selectionWidth = $('#image_preview_image').width();
            var selectionHeight = parseInt($('#image_preview_image').width() / (options.cropConfig.minWidth / options.cropConfig.minHeight));
        }

        api.setSelect([
            parseInt(($('#image_preview_image').width() - selectionWidth) / 2),
            parseInt(($('#image_preview_image').height() - selectionHeight) / 2),
            selectionWidth,
            selectionHeight
        ]);
        $('#image_crop_go_now').removeClass('hidden');
        $('#image_crop_cancel').removeClass('hidden');
        $('#image_upload_tabs a:last').tab('show');
    });
}

function cropImage(id, options) {
    $.ajax({
        url: Routing.generate(options.cropConfig.cropRoute),
        type: 'POST',
        data: {
            'config': JSON.stringify(options),
            'imageName': $('#selected_image').val(),
            'x': c.x,
            'y': c.y,
            'w': c.w,
            'h': c.h
        },
        success: function (data) {
            var previewSrc = data.previewSrc;

            $('#' + id).val(previewSrc).change();
            $('#image_preview_image_' + id).html('<img src="' + previewSrc + '?' + new Date().getTime() + '" id="' + id + '_preview"/>');
            if (options.uploadConfig.saveOriginal) {
                $('#' + options.originalImageFieldId).val($('#selected_image').val());
                $('#image_preview_image_' + id + ' img').css('cursor: hand; cursor: pointer;');
                $('#image_preview_image_' + id + ' img').click(function (e) {
                    if ($(event.target).is("img")) {
                        $('<div class="modal hide fade"><img src="' + options.uploadConfig.webDir + '/' + $('#selected_image').val() + '"/></div>').modal();
                        return false;
                    }
                });
            }
            $('#image_preview_' + id).removeClass('hide-disabled');

            destroyJCrop(id);
            $('#selected_image').val('');
            $('#image_crop_go_now').addClass('hidden');
            $('#image_crop_cancel').addClass('hidden');
            $('#image_upload_tabs a:first').tab('show');
            $('#image_upload_modal').modal('hide');
        }
    });
}

function destroyJCrop() {
    if (!api) {
        return false;
    }
    api.destroy();
    $('#upload_image_crop_go').addClass('hidden');
}
