jQuery(document).ready(function($) {
	var pluginpath = '../wp-content/plugins/crop-thumbnails';
	var adminAjaxPath = '../wp-admin/admin-ajax.php';
	
	//setup for ajax connections
	$.ajaxSetup({type:'POST', url:adminAjaxPath, cache:false, timeout: (30 * 1000)});
	
	//cropping object: holds jcrop-object and image to use the crop on
	var cropping = {api:-1, img : $('.selectionArea img')};
	
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
			alert(cpt_lang['selectOne']);
			return;
		}
		var selection = cropping.api.tellSelect();
		if(active.length>0 && selection.w>0 && selection.h>0) {
			doProcessing(active,cropping);
		}
	});
	
	/********************************/
	function doProcessing(active,cropping) {
		/*console.log('doProcessing');*/
		
		var active_array = new Array();
		active.find('img').each(function() {
			active_array.push($(this).data('values'));
		});
		
		$('.mainWindow').hide();
		$('.waitingWindow').show();
		
		/*console.log('selection',cropping.api.tellSelect());*/
		
		$.ajax({ 
			data:{ 
				action: 'cptSaveThumbnail',
				'_ajax_nonce': cpt_ajax_nonce,
				cookie: encodeURIComponent(document.cookie),
				selection: JSON.stringify(cropping.api.tellSelect()),
				raw_values: JSON.stringify(cropping.img.data('values')),
				active_values: JSON.stringify(active_array)
			},
			complete : function() {
				$('.mainWindow').show();
				$('.waitingWindow').hide();
			},
			success : function( response ) {
				var result = JSON.parse(response);
				/*console.log(result);*/
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
	
	
	function deactivateArea(c) {
		if(c.api!=-1) {
			c.api.destroy();
		}
	}
	
	function activateArea(c,li) {
		deactivateArea(c);
		var allActiveThumbs = $('.thumbnail-list li.active img');
		var largestWidth = 0;
		var largestHeight = 0;
		var ratio = 0;
		allActiveThumbs.each(function() {
			if(ratio === 0) {
				ratio = $(this).data('values').ratio;//initial
			}
			if(ratio != $(this).data('values').ratio) {
				alert(cpt_lang['bug']);
			}
			
			//we only need to check in one dimension, cause per definition all images have to use the same ratio
			if($(this).data('values').width > largestWidth) {
				largestWidth = $(this).data('values').width;
				largestHeight = $(this).data('values').height; 
			}
		});
		
		
		var scale = c.img.width() / largestWidth;
		var preSelect = [ 0, 0, Math.round(scale*c.img.width()), Math.round(scale*c.img.height()) ];
		var minSize = [ largestWidth, largestHeight ];
		
		
		
		var options = {}
		options.boxWidth = c.img.width();
		options.boxHeight = c.img.height();
		options.trueSize = [cropping.img.data('values').width,c.img.data('values').height];
		options.aspectRatio = ratio;
		options.setSelect = preSelect;
		if(largestWidth>cropping.img.data('values').width || largestHeight>cropping.img.data('values').height) {
			alert(cpt_lang['warningOriginalToSmall']);
		} else {
			options.minSize = minSize;
		}
		console.log('options',options);
		
		c.api = $.Jcrop(c.img, options);
	}
});