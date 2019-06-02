var galleries = {};

$(function(){
    $('.fileinput-button').click(function(event){ 
        if( $( event.target ).is( "span" ) )
        {
            // console.log('click');
            $('#image_upload_file').click();
            event.stopPropagation();
            return false;
        } 
    });

});

function initializeImageManager(id, options, cb){
    if (typeof cb === 'function') {
        options.callback = cb;
    }
    if((typeof options.uploadConfig.library == 'undefined' || options.uploadConfig.library) 
        && typeof options.uploadConfig.libraryDir != 'undefined' 
        && options.uploadConfig.libraryRoute != 'undefined'
        && (options.uploadConfig.showLibrary == 'undefined'
        || options.uploadConfig.showLibrary))
    {
        $('#select-existing').removeClass('hidden');
        $('#image_upload_tabs li:nth-child(2)').show();
        $('#existing-images .image-container').remove();
        $.ajax({
            url: Routing.generate(options.uploadConfig.libraryRoute),
            data: {dir: options.uploadConfig.libraryDir},
            success: function(response){
                var response = $.parseJSON(response);
                // console.log(response);
                var files = response.files;
                // console.log(files);
                for (var i = files.length - 1; i >= 0; i--) {
                    var now = new Date().getTime();
                    $('#existing-images').append('<div class="image-container" data-src="'+files[i]+'"><img src="/'+options.uploadConfig.webDir + '/'+response['thumbsDir']+'/'+files[i]+'?'+now+'"/></div>');
                };
                
                $('.image-container').click(function(){
                    $('#selected_image').val($(this).attr('data-src'));
                    initJCrop(id, options);
                });

            },
            type: 'POST'
        });
    }
    else{
        $('#select-existing').addClass('hidden');
        $('#image_upload_tabs li:nth-child(2)').hide();
    }
    $('#image_upload_tabs li:nth-child(3)').hide();
    // console.log('init');
    // console.log($('#image_upload_file'));
    var url = Routing.generate(options.uploadConfig.uploadRoute);
    // console.log(url);
    // console.log($('.fileinput-button'));
    //$('#image_upload_file').bind('change', function(){console.log('change')});
    $('#image_upload_file').fileupload({
        url: url,
        dataType: 'json',
        formData: {'config': JSON.stringify(options) },
        dropZone: $('#image_upload_drop_zone'),
        // maxFileSize: options.uploadConfig.maxFileSize,
        // acceptFileTypes: new RegExp(`(\.|\/)(${options.uploadConfig.fileExt.replace(/\*\./g, '').split(';').join('|')})$`, 'i'),
        add: function(e, data) {
            var uploadErrors = [];
            var acceptFileTypes = new RegExp(`(\.|\/)(${options.uploadConfig.fileExt.replace(/\*\./g, '').split(';').join('|')})$`, 'i');
            if(data.originalFiles[0]['type'].length && !acceptFileTypes.test(data.originalFiles[0]['type'])) {
                uploadErrors.push(comurImageTranslations['Filetype not allowed']);
            }
            if(data.originalFiles[0]['size'] && data.originalFiles[0]['size'] > options.uploadConfig.maxFileSize * 1024 * 1024 ) {
                uploadErrors.push(comurImageTranslations['File is too big']);
            }
            if(uploadErrors.length > 0) {
                $('#image_upload_widget_error').html(uploadErrors.join("<br/>"));
                $('#image_upload_widget_error').parent().show();
            } else {
                $('#image_upload_widget_error').html('');
                $('#image_upload_widget_error').parent().hide();
                data.submit();
            }
        },
        done: function (e, data) {
            // console.log('uploaded');
            if(data.result['image_upload_file'][0].error){
                $('#image_upload_widget_error').text(data.result['image_upload_file'][0].error);
                $('#image_upload_widget_error').parent().show();
            }
            else{
                $('#image_upload_widget_error').text('');
                $('#image_upload_widget_error').parent().hide();
                // console.log(data.result, data.result['image_upload_file']);
                // $('#image_preview img').remove();
                // $('#image_preview').html('<img src="/'+data.result['image_upload_file'][0].url+'" id="image_preview_image"/>');
                $('#selected_image').val(data.result['image_upload_file'][0].name); 
                if (options.cropConfig.disable) {
                    $('#'+id).val(data.result['image_upload_file'][0].name);
                    $('#image_preview_image_'+id).html('<img src="'+options.uploadConfig.webDir + '/' + data.result['image_upload_file'][0].name +'?'+ new Date().getTime()+'" id="'+id+'_preview"/>');
                    reinitModal();
                    cb({
                        previewSrc: '/' + options.uploadConfig.webDir + '/' + data.result['image_upload_file'][0].name +'?'+ new Date().getTime(),
                        filename: data.result['image_upload_file'][0].name
                    });
                } else {
                    initJCrop(id, options);
                }
            }
            
        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#image_file_upload_progress .progress-bar').css(
                'width',
                progress + '%'
            );
        },
        fail: function (e, data) {
            console.log(e, data);
            // data.errorThrown
            // data.textStatus;
            // data.jqXHR;
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');
    // $('#image_upload_file').bind('fileuploadadd', function (e, data) {console.log('add')});
    $('#image_crop_go_now').unbind('click');
    $('#image_crop_go_now').click(function(){ cropImage(id, options)});
    // $('#image_crop_cancel').click(function () {
    //     $('#selected_image').val('');
    //     $('#image_crop_go_now').addClass('hidden');
    //     $('#image_crop_cancel').addClass('hidden');
    //     $('#image_upload_tabs a:first').tab('show');
    // });
    // $('#'+id+'_image_crop span').click(initJCrop_{{id}});
    // $('#'+id+'_image_crop_go_cancel').click(destroyJCrop);
}

function destroyImageManager(){
    $('#image_upload_file').fileupload('destroy');
    destroyJCrop();
    $('#image_crop_go_now').unbind('click');
    $('#image_preview').html('<p>'+comurImageTranslations['Please select or upload an image']+'</p>');
    $('#image_file_upload_progress .progress-bar').css(
      'width',
      '0%'
    );
    // $('#image_crop_cancel').addClass('hidden');
    reinitModal();
}

var api;
var c;

function updateCoords(coords){
    c = coords;
}

function initJCrop(id, options){
    if(api){
        api.destroy();
    }
    // if(!options.cropConfig.disableCrop){
        var now = new Date().getTime();
        $('#image_preview img').remove();
        $('#image_preview').html('<img src="/'+options.uploadConfig.webDir + '/'+$('#selected_image').val()+'?'+now+'" id="image_preview_image"/>');
        $($('#image_preview img')[0]).on('load', function(){

            
            $('#image_preview img').Jcrop({
                // start off with jcrop-light class
                bgOpacity: 0.8,
                bgColor: 'white',
                addClass: 'jcrop-dark',
                aspectRatio: options.cropConfig.aspectRatio ? options.cropConfig.minWidth/options.cropConfig.minHeight : false ,
                minSize: [ options.cropConfig.minWidth, options.cropConfig.minHeight ],
                boxWidth: 600, 
                boxHeight: 400,
                onSelect: updateCoords
            },function(){
                api = this;
                api.setOptions({ bgFade: true });
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
                parseInt(($('#image_preview_image').width() - selectionWidth)/2),
                parseInt(($('#image_preview_image').height() - selectionHeight)/2),
                selectionWidth, 
                selectionHeight
            ]);
            //$('#image_crop').addClass('hidden');
            $('#image_crop_go_now').removeClass('hidden');
            // $('#image_crop_cancel').removeClass('hidden');
            $('#image_upload_tabs a:last').tab('show');
        });
        //$('#image_backdrop').removeClass('hidden');
        //$('#image_preview').css({ 'position': 'relative'});
    // }
    // else{
    //     c = {x: 0, y: 0, w: 0, h: 0};
    //     cropImage(id, options);
    // }
}

function cropImage(id, options){
    $('#cropping-loader').show();
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
        success: function(data){
            var data = $.parseJSON(data);
            var filename = data.filename;
            var previewSrc = data.previewSrc;
            
            // console.log('crop success');
            if (options.callback) {
                options.callback(data);
            } else {
                if(typeof galleries[id] != 'undefined'){
                    // console.log('isGallery');
                    // console.log(galleries[id]);
                    addImageToGallery(filename, id, data.galleryThumb, options);
                }
                else{
                    // console.log('simple image');
                    $('#'+id).val(filename);
                    $('#image_preview_image_'+id).html('<img src="'+previewSrc+'?'+ new Date().getTime()+'" id="'+id+'_preview"/>');
                    // console.log(options.uploadConfig.saveOriginal, $('#'+options.originalImageFieldId), options.originalImageFieldId);
                    if(options.uploadConfig.saveOriginal){
                        // console.log('set '+$('#selected_image').val());
                        $('#'+options.originalImageFieldId).val($('#selected_image').val());
                        $('#image_preview_image_'+id+' img').css('cursor: hand; cursor: pointer;');
                        $('#image_preview_image_'+id+' img').click(function(e){
                            if($( event.target ).is( "img" )){
                                $('<div class="modal hide fade"><img src="'+options.uploadConfig.webDir+'/'+$('#selected_image').val()+'"/></div>').modal();
                                return false;
                            }
                        });
                    }
                    //$('#image_preview_image_'+id).html('<img src="/'+options.uploadConfig.webDir + '/' + $('#selected_image').val()+'?'+ new Date().getTime()+'" id="'+id+'_preview"/>');
                    $('#image_preview_'+id).removeClass('hide-disabled');
                }
            }

            
            destroyJCrop(id);
            reinitModal();
        },
        complete: function() {
            $('#cropping-loader').hide();
        }
    });
}

function reinitModal() {
    $('#selected_image').val('');
    $('#image_preview').html('<p>Please select or upload an image</p>');
    $('#image_crop_go_now').addClass('hidden');
    // $('#image_crop_cancel').addClass('hidden');
    $('#image_upload_tabs a:first').tab('show');
    $('#image_upload_modal').modal('hide');
}

function addImageToGallery(filename, id, thumb, options)
{
    // $('#'+id).val(js_array_to_php_array(galleries[id]));
    // console.log('add #gallery_preview_'+id+' input');
    var nb = $('#gallery_preview_'+id+' input').length;
    var name = $('#gallery_preview_'+id).data('name');
    $('#gallery_preview_'+id).append('<div class="gallery-image-container" data-image="'+filename+'">' +
        '<span class="remove-image"><i class="fa fa-remove"></i></span>' +
        '<span class="gallery-image-helper"></span>' +
        '<input type="text" id="'+id+'_'+nb+'" name="'+name+'['+nb+']" style="padding:0; border: 0; margin: 0; opacity: 0;width: 0; max-width: 0; height: 0; max-height: 0;" value="'+filename+'">' +
        '<img src="/'+options.uploadConfig.webDir + '/' + thumb+'?'+ new Date().getTime()+'"/>' +
    '</div>');
    rebindGalleryRemove();
}

function removeImageFromGallery(filename, id)
{
    
    // ADD DELETE FILE HERE !
    $('#'+id).parent().remove();
    reorderItems(id);

}

function reorderItems(id)
{
    var name = $('#'+id).data('name');
    $( '#'+id+' .gallery-image-container' ).each(function(i, item){
        $(item).find('input').attr('name', name+'['+i+']');
    });
}

function rebindGalleryRemove()
{
    $('.gallery-image-container span').unbind('click');
    $('.gallery-image-container span').click(function(){
        removeImageFromGallery($(this).parent().data('image'), $(this).parent().find('input').attr('id'));
        return false; 
    });
}

function destroyJCrop(){
    if(!api){
        return false;
    }
    api.destroy();
    // $('#upload_image_crop').removeClass('hidden');
    $('#upload_image_crop_go').addClass('hidden');
}

// function js_array_to_php_array (a)
// This converts a javascript array to a string in PHP serialized format.
// This is useful for passing arrays to PHP. On the PHP side you can 
// unserialize this string from a cookie or request variable. For example,
// assuming you used javascript to set a cookie called "php_array"
// to the value of a javascript array then you can restore the cookie 
// from PHP like this:
//    <?php
//    session_start();
//    $my_array = unserialize(urldecode(stripslashes($_COOKIE['php_array'])));
//    print_r ($my_array);
//    ?>
// This automatically converts both keys and values to strings.
// The return string is not URL escaped, so you must call the
// Javascript "escape()" function before you pass this string to PHP.
// {
//     var a_php = "";
//     var total = 0;
//     for (var key in a)
//     {
//         ++ total;
//         a_php = a_php + "s:" +
//                 String(key).length + ":\"" + String(key) + "\";s:" +
//                 String(a[key]).length + ":\"" + String(a[key]) + "\";";
//     }
//     a_php = "a:" + total + ":{" + a_php + "}";
//     return a_php;
// }
