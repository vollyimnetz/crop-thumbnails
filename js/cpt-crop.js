jQuery(document).ready(function($) {
	var pluginpath = '../wp-content/plugins/crop-thumbnails';
	var adminAjaxPath = '../wp-admin/admin-ajax.php';
	
	//setup for ajax connections
	$.ajaxSetup({type:'POST', url:adminAjaxPath, cache:false, timeout: (30 * 1000)});
	
	//cropping object: holds jcrop-object and image to use the crop on
	var cropping = {api:-1, img : $('.selectionArea img')};
	
	//active ration: use as object to get call by reference
	var active_ratio = {'state':''};//initial
	
	/*needed cause the js-logic is currently not handle the hidden objects in dependence with "select all of the same ratio"*/
	$('.thumbnail-list li.hidden').remove();
	
	cropping.img.fadeTo(0, 0.3);
	
	$('.thumbnail-list li').click(function() {
		if(isRatioGood($(this),active_ratio,cropping)) {
			$(this).toggleClass('active');
		
			//check if something is active
			if($('.thumbnail-list li.active').length>0) {
				activateArea(cropping,$(this));
			} else {
				deactivateArea(cropping);
			}
		}
	});
	
	$('#cpt-same-ratio').click(function() {
		var active = $('.thumbnail-list li.active');
		if(active.length>0) {
			var tmpratio = $(active[0]).attr('rel');
			var tmp = $('.thumbnail-list li[rel="'+tmpratio+'"]');
			if(tmp.length>1) { tmp.addClass('active'); }
		} else {
			alert(cpt_lang.selectOne);
		}
	});
	
	$('#cpt-deselect').click(function() {
		$('.thumbnail-list li.active').removeClass('active');
		deactivateArea(cropping);
		active_ratio.state = '';
	});
	
	
	$('#cpt-generate').click(function() {
		var active = $('.thumbnail-list li.active');
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
	
	/**
	 * 
     * @param {Object} elem clicked list element
     * @param {Object} active_ratio object to hold the last/current ratio stand
	 */
	function isRatioGood(elem,active_ratio,cropping) {
		var ratio = elem.attr('rel');
		if( active_ratio.state=='' ) {
			if(elem.hasClass('active')) {
				//bug - this case shouldnt be happend
				alert(cpt_lang.bug);
			} else {
				active_ratio.state=ratio;
				return true;
			}
		} else {//ratio already defined
			if(elem.hasClass('active')) {
				//good
				if($('.thumbnail-list li.active').length==1) {
					active_ratio.state = '';
				}
				return true;
			} else {
				if(ratio==active_ratio.state) {
					//good
					return true;
				} else {
					//bad
					var text = cpt_lang.wrongRatio;
					text = text.replace( (new RegExp("<br \>","g")) ,'\n');
					
					//want to release selection?
					if(confirm(text)) {
						//release selection
						$('.thumbnail-list li.active').removeClass('active');
						deactivateArea(cropping);
						active_ratio.state = '';
					}
					return false;
				}
			}
		}
	}
	
	
	function deactivateArea(c) {
		if(c.api!=-1) {
			c.api.destroy();
		}
	}
	
	function activateArea(c,li) {
		deactivateArea(c);
		
		var thumb = $(li.find('img')[0]);
		
		var scale = c.img.width() / thumb.data('values').width;
		var preSelect = [ 0, 0, Math.round(scale*c.img.width()), Math.round(scale*c.img.height()) ];
		var minSize = [ thumb.data('values').width, thumb.data('values').height ];
		
		var options = {}
		options.boxWidth = c.img.width();
		options.boxHeight = c.img.height();
		options.trueSize = [cropping.img.data('values').width,c.img.data('values').height];
		options.aspectRatio = thumb.data('values').ratio;
		options.setSelect = preSelect;
		options.minSize = minSize;
		//console.log('options',options);
		
		c.api = $.Jcrop(c.img, options);
	}
});