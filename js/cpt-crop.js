jQuery(document).ready(function($) {
	var pluginpath = '../wp-content/plugins/crop-thumbnails';
	var adminAjaxPath = '../wp-admin/admin-ajax.php';

	//setup for ajax connections
	$.ajaxSetup({type:'POST', url:adminAjaxPath, cache:false, timeout: (30 * 1000)});

	//cropping object: holds jcrop-object and image to use the crop on
	var cropping = {
		api:null, 
		img : $('.selectionArea img')
	};

	/*needed cause the js-logic is currently not handle the hidden objects in dependence with "select all of the same ratio"*/
	$('.thumbnail-list li.hidden').remove();

	cropping.img.fadeTo(0, 0.3);

	//handle click on an entry
	$('.thumbnail-list li').click(function() {
		selectAllWithSameRatio($(this));
		activateArea(cropping);
	});


	//handle checkbox for selecting all with same ratio
	$('#cpt-same-ratio').change(function() {
		var active = $('.thumbnail-list li.active');
		if($(this).attr('checked')==='checked') {
			if(active.length>0) {
				selectAllWithSameRatio($(active[0]));
				activateArea(cropping);
			}
		} else {
			if(active.length>1) {
				$('.thumbnail-list li').removeClass('active');
				deactivateArea(cropping);
			}
		}
	});


	$('#cpt-deselect').click(function() {
		$('.thumbnail-list li.active').removeClass('active');
		deactivateArea(cropping);
	});


	$('#cpt-generate').click(function() {
		var active = $('.thumbnail-list li.active');
		if(active.length===0) {
			alert(cpt_lang.selectOne);
			return;
		}
		var selection = cropping.api.getData();
		if(active.length>0 && selection.width>0 && selection.height>0) {
			var selectionData = {//needed cause while changing from jcrop to cropperjs i do not want to change the api
				x:selection.x,
				y:selection.y,
				x2:selection.x + selection.width,
				y2:selection.y + selection.height,
				w:selection.width,
				h:selection.height
			};
			doProcessing(active,cropping,selectionData);
		}
	});


	$('.cpt-debug .cpt-debug-handle').click(function(e) {
		e.preventDefault();
		$('.cpt-debug').toggleClass('closed');
	});

	/********************************/
	function doProcessing(active,cropping,selection) {
		/*console.log('doProcessing');*/

		var active_array = [];
		active.find('img').each(function() {
			active_array.push($(this).data('values'));
		});

		$('.mainWindow').hide();
		$('.waitingWindow').show();

		$.ajax({
			data:{
				action: 'cptSaveThumbnail',
				'_ajax_nonce': cpt_ajax_nonce,
				cookie: encodeURIComponent(document.cookie),
				selection: JSON.stringify(selection),
				raw_values: JSON.stringify(cropping.img.data('values')),
				active_values: JSON.stringify(active_array),
				same_ratio_active: $('#cpt-same-ratio').is('checked')
			},
			complete : function() {
				$('.mainWindow').show();
				$('.waitingWindow').hide();
			},
			success : function( response ) {
				try {
					var result = JSON.parse(response);

					if(cpt_debug_js) {
						console.log('Save Function Debug',result.debug);
					}

					if(typeof result.success == "number") {

						if(result.changed_image_format) {
							window.location.reload();
						} else {
							doCacheBreaker(result.success);
						}
					} else {
						//saving fail
						alert(result.error);
					}
				} catch(e) {
					alert(e.message+"\n"+response);
				}

			}
		});
	}

	function doCacheBreaker(number) {
		$('.thumbnail-list li img').each(function() {
			var imgurl = $(this).attr('src');
			var last = imgurl.lastIndexOf('?');
			if(last<0) {
				imgurl+='?'+number;
			} else {
				imgurl = imgurl.substring(0, last) + '?'+number;
			}
			$(this).attr('src',imgurl);
		});
	}

	function selectAllWithSameRatio(elem) {
		$('.thumbnail-list li').removeClass('active');
		if($('#cpt-same-ratio').attr('checked')==='checked') {
			var ratio = elem.attr('rel');
			var elements = $('.thumbnail-list li[rel="'+ratio+'"]');
			elements.addClass('active');
		} else {
			elem.addClass('active');
		}
	}


	function deactivateArea(cropping) {
		if(cropping.api!==null) {
			cropping.api.destroy();
		}
	}

	function activateArea(cropping) {
		deactivateArea(cropping);
		var allActiveThumbs = $('.thumbnail-list li.active img');
		var largestWidth = 0;
		var largestHeight = 0;
		var ratio = 0;

		var options = {
			aspectRatio: 0,
			viewMode:1,//for prevent negetive values
			checkOrientation:false,
			background:false, //do not show the grid background
			autoCropArea:1,
			zoomable:false,
			zoomOnTouch:false,
			zoomOnWheel:false,
			//minCropBoxWidth:250,
		};

		//get the options
		allActiveThumbs.each(function() {
			var img_data = $(this).data('values');
			if(ratio === 0) {
				options.aspectRatio = img_data.ratio;//initial
			}
			if(options.aspectRatio != img_data.ratio) {
				console.info('Crop Thumbnails: print ratio is different from normal ratio on image size "'+img_data.name+'".');
			}

			//we only need to check in one dimension, cause per definition all selected images have to use the same ratio
			if(img_data.width > largestWidth) {
				largestWidth = img_data.width;
				largestHeight = img_data.height;
			}
		});


		//debug
		if(cpt_debug_js) {
			console.log('choosed image - data',cropping.img.data('values'));
			console.log('Cropping options',options);
		}

		cropping.api = new Cropper(cropping.img[0], options);
	}
});
