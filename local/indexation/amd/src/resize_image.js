/* jshint ignore:start */
define(['jquery', 'jqueryui','local_magisterelib/cropperjs'], function() {
    return {
        init: function() {
            const targetNode = document.querySelector('.filepicker-filename');
            const config = { attributes: false, childList: true, subtree: false };

            if($(".filepicker-filename").find('a').attr('href')){
                initCropper(false);
            }

            // Callback function to execute when mutations are observed
            const callback = function(mutationsList, observer) {
                initCropper(true);
            };

            // Create an observer instance linked to the callback function
            const observer = new MutationObserver(callback);

            // Start observing the target node for configured mutations
            observer.observe(targetNode, config);

            function initCropper(first_try){
                var $image = $('#image');

                //Destroy current Cropper (Case when user upload image aboce another)
                $image.cropper("destroy");

                //Get img url from filepicker
                $image.attr('src',$(".filepicker-filename").find('a').attr('href'));

                //Cropper initialization https://github.com/fengyuanchen/cropperjs/blob/master/README.md
                $image.cropper({
                    dragMode: 'move',
                    aspectRatio: 16 / 9,
                    restore: false,
                    guides: false,
                    center: false,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    ready: function (event) {
                        if(first_try){
                            //step to set the default area
                            var w_i =  $image.cropper("getCanvasData").naturalWidth;
                            var h_i = $image.cropper("getCanvasData").naturalHeight;

                            var w_o = w_i ;
                            var h_o = 9 * w_o / 16;

                            //if output height is longer then width
                            if ( h_i < h_o) {
                                h_o = h_i;
                                w_o = 16 * h_o / 9;
                            }
                            var x_o = w_i - w_o;
                            var y_o = h_i - h_o;

                            if (x_o + w_o > w_i) w_o = w_i - x_o; // If width of output image is bigger then input image (considering x_o), reduce it
                            if (y_o + h_o > h_i) h_o = h_i - y_o; // If height of output image is bigger then input image (considering y_o), reduce it

                            var datas = {
                                x : x_o,
                                y : y_o,
                                width : w_o,
                                height : h_o
                            };

                            $image.cropper("setData",{
                                x : datas.x/2,
                                y : datas.y/2,
                                width : datas.width,
                                height : datas.height,
                                scaleX : 1,
                                scaleY : 1
                            })
                        }else{
                            var datas = JSON.parse($("input[name='img_old_datas']").val());
                            $("input[name='img_old_datas']").val("");
                            $image.cropper("setData",{
                                x : datas.x,
                                y : datas.y,
                                width : datas.width,
                                height : datas.height,
                                scaleX : 1,
                                scaleY : 1
                            });

                        }
                    },

                    crop: function (event) {
                        if(!$("input[name='img_old_datas']").val()){
                            var datas = {
                                x : event.detail.x,
                                y : event.detail.y,
                                width : event.detail.width,
                                height : event.detail.height
                            };

                            $("input[name='img_datas']").val(JSON.stringify(datas));
                        }
                    },

                    zoom: function (event) {
                        // Keep the image in its natural size
                        if (event.detail.oldRatio === 1) {
                            event.preventDefault();
                        }
                    }
                });
            }
        }
    };
});