$(function(){
    $('.fileinput-button').click(function(event){ 
        if( $( event.target ).is( "span" ) )
        {
            console.log('click');
            $('#image_upload_file').click();
            event.stopPropagation();
            return false;
        } 
    });

});

function initializeImageManager(id, options){
    if((typeof options.uploadConfig.library == 'undefined' || options.uploadConfig.library) && typeof options.uploadConfig.libraryDir != 'undefined' && options.uploadConfig.libraryRoute != 'undefined'){
        $('#select-existing').removeClass('hidden');
        $('#existing-images .image-container').remove();
        $.ajax({
            url: Routing.generate(options.uploadConfig.libraryRoute),
            data: {dir: options.uploadConfig.libraryDir},
            success: function(response){
                var response = $.parseJSON(response);
                console.log(response);
                var files = response.files;
                console.log(files);
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
    }
    console.log('init');
    console.log($('#image_upload_file'));
    var url = Routing.generate(options.uploadConfig.uploadRoute);
    console.log(url);
    console.log($('.fileinput-button'));
    //$('#image_upload_file').bind('change', function(){console.log('change')});
    $('#image_upload_file').fileupload({
        url: url,
        dataType: 'json',
        formData: {'config': JSON.stringify(options) },
        dropZone: $('#image_upload_drop_zone'),
        done: function (e, data) {
            console.log('uploaded');
            if(data.result['image_upload_file'][0].error){
                $('#image_upload_widget_error').text(data.result['image_upload_file'][0].error);
                $('#image_upload_widget_error').parent().removeClass('hidden');
            }
            else{
                $('#image_upload_widget_error').text('');
                $('#image_upload_widget_error').parent().addClass('hidden');
                console.log(data.result, data.result['image_upload_file']);
                // $('#image_preview img').remove();
                // $('#image_preview').html('<img src="/'+data.result['image_upload_file'][0].url+'" id="image_preview_image"/>');
                $('#selected_image').val(data.result['image_upload_file'][0].name); 
                initJCrop(id, options);
            }
            
        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#image_upload_file_progress .progress-bar').css(
                'width',
                progress + '%'
            );
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');
    // $('#image_upload_file').bind('fileuploadadd', function (e, data) {console.log('add')});
    $('#image_crop_go_now').click(function(){ cropImage(id, options)});
    // $('#'+id+'_image_crop span').click(initJCrop_{{id}});
    // $('#'+id+'_image_crop_go_cancel').click(destroyJCrop);
}

function destroyImageManager(){
    $('#image_upload_file').fileupload('destroy');
    destroyJCrop();
    $('#image_crop_go_now').unbind('click');
    $('#image_preview').html('<p>Please select or upload an image</p>');
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
    var now = new Date().getTime();
    $('#image_preview img').remove();
    $('#image_preview').html('<img src="/'+options.uploadConfig.webDir + '/'+$('#selected_image').val()+'?'+now+'" id="image_preview_image"/>');
    $('#image_preview img').load(function(){

        
        $('#image_preview img').Jcrop({
            // start off with jcrop-light class
            bgOpacity: 0.8,
            bgColor: 'black',
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
        console.log(parseInt(($('#image_preview_image').width() - selectionWidth)/2),
            parseInt(($('#image_preview_image').height() - selectionHeight)/2),
            selectionWidth, 
            selectionHeight);

        api.setSelect([
            parseInt(($('#image_preview_image').width() - selectionWidth)/2),
            parseInt(($('#image_preview_image').height() - selectionHeight)/2),
            selectionWidth, 
            selectionHeight
        ]);
        //$('#image_crop').addClass('hidden');
        $('#image_crop_go_now').removeClass('hidden');
        $('#image_upload_tabs a:last').tab('show');
    });
    //$('#image_backdrop').removeClass('hidden');
    //$('#image_preview').css({ 'position': 'relative'});
}

function cropImage(id, options){
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
            var filename = $.parseJSON(data).filename;
            $('#'+id).val(filename);
            $('#selected_image').val(filename);
            $('#image_preview').html('<p>Please select or upload an image</p>');
            $('#image_preview_image_'+id).html('<img src="/'+options.uploadConfig.webDir + '/' + $('#selected_image').val()+'?'+ new Date().getTime()+'" id="'+id+'_preview"/>');
            destroyJCrop(id);
            $('#image_preview_'+id).removeClass('hide-disabled');
            $('#image_crop_go_now').addClass('hidden');
            $('#image_upload_tabs a:first').tab('show');
            $('#image_upload_modal').modal('hide');
        }
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