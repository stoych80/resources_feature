jQuery(function ($) {
	var the_width = $(window).width()+15, the_width2; //+the scrollbar
	if (the_width>1100) {
		the_width2 = '65%';
	} else if (the_width>991) {
		the_width2 = '80%';
	} else {
		the_width2 = '94%';
	}
	$(".topicbtn").fancybox({fitToView:false,margin:[35,20,20,20],width:the_width2,height:"auto",autoSize:false,closeClick:false,openEffect:"elastic",openSpeed:200,closeEffect:"elastic",helpers:{overlay:null}
		,afterShow: function () {
			fancyParent = $(".fancybox-wrap").parents(); // normally html and body
			fancyParent.on("click", function () {
				$.fancybox.close();
			});
			$(".fancybox-wrap").on("click", function (event) {
				// prevents closing when clicking inside the fancybox wrap
				event.stopPropagation();
			});
		},
		afterClose: function () {
			fancyParent.unbind("click");
		}
	});
	$('#topicsbox div.topicsinner').on('click', function(e) {
		if ($('#alm_is_animating').val()==1) return false;
		var thethis = $(this);
		if (thethis.hasClass('withborder')) thethis.removeClass('withborder'); else thethis.addClass('withborder');
	});
	$('#topicsbox .submit-topics').on('click', function(e) {
		e.preventDefault();
		//deselect all categories checkboxes
		$('.dd_resources_feature-alm-filter-nav li .cbox input[type="checkbox"]').each(function () {
			if ($(this).is(":checked")) $(this).prop('checked', false);
		});
		//select only the categories on the right for the selected topics in the fancybox
		$('#topicsbox div.topicsinner').each(function () {
			var thethis = $(this);
			if (thethis.hasClass('withborder')) {
				thethis.removeClass('withborder');
				$('#term_id'+thethis.attr('term_id')).prop('checked', true);
			}
		});
		var browse_by_category_name = $(this).val().replace('Submit ','');
		$('h3.filter-title').each(function () {
			//leave only the 1st category (Topics) open, close the rest
			if ($(this).text()==browse_by_category_name) {
				if (!$(this).hasClass('activetab')) {
					$(this).addClass('activetab');
					$(this).next('div.filter-content').slideDown();
				}
			} else {
				if ($(this).hasClass('activetab')) {
					$(this).removeClass('activetab');
					$(this).next('div.filter-content').slideUp();
				}
			}
		});
		$.fancybox.close();
		$.fn.runALMfilter();
	});
	
	
	$(document).on('click','.link-show-more-filter',function(e) {
		var thenewcount;
		$(this).parent('div.layer-more').prev('ul.no-style').children('li.hidefilter').each(function () {
			thenewcount=$(this).children('.cbox').children('div.layer-label-wrapper').children('span.style-count').html().replace('(','').replace(')','');
			$(this).removeClass('hidefilter');
			if (show_categories_in_the_filter_with_no_posts || thenewcount>0) {
				$(this).slideDown();
			} else $(this).hide();
		});
		$(this).slideUp();
	});
	$(document).on('click','h3.filter-title',function(e) {
		if ($(this).hasClass('activetab')) {
			$(this).removeClass('activetab');
		} else {
			$(this).addClass('activetab');
		}
		$(this).next('div.filter-content').slideToggle();
	});
	
	$(document).on('click','.dd_resources_feature-alm-filter-nav li .cbox',function(e) {
		e.preventDefault();
		if ($('#alm_is_animating').val()==1) return false;
		if (typeof $.fn.dd_resources_feature_cbox_clicked_pre == 'function') {
			$.fn.dd_resources_feature_cbox_clicked_pre($(this));
		}
		var the_selected_checkbox = $(this).children('input[type="checkbox"]');
		the_selected_checkbox.prop('checked', !the_selected_checkbox.is(":checked"));
		$.fn.runALMfilter();
	});
	$('.search-button').on('click', function(e) {
		e.preventDefault();
		if ($('#alm_is_animating').val()==1) return false;
		$.fn.runALMfilter();
	});
	//post type tab clicked on
	$(document).on('click','.resulttabwrap .horizontaltabs li a',function(e) {
		e.preventDefault();
		if ($('#alm_is_animating').val()==1) return false;
		//clear all ticked categories checkboxes
		$('.dd_resources_feature-alm-filter-nav li .cbox input[type="checkbox"]').each(function () {
			if ($(this).is(":checked")) $(this).prop('checked', false);
		});
		$('h3.filter-title').each(function () {
			if ($(this).hasClass('activetab')) {
				$(this).removeClass('activetab');
				$(this).next('div.filter-content').slideUp();
			}
		});
		var the_parent_li = $(this).parent('li');
		$('#form-filter').css({'height':$('#form-filter').height()+'px'});
		$('#form-filter-categories-wrapper').fadeOut(400,function () {
			$('#form-filter-categories-wrapper').html('<div style="text-align:center;"><img src="'+site_url+'/wp-content/plugins/dd_features_for_wordpress/classes_features/assets/dd_resources_feature/images/loading.gif" /></div>');
			$('#form-filter-categories-wrapper').fadeIn();
		});
		if (the_parent_li.attr('post_type') == 'civicrm_events' && !table_civicrm_event_wp_terms_link_exists) $('.searchright').fadeOut(); else $('.searchright').fadeIn();
		$.ajax({
			type : 'post',
			dataType : 'text',
			url : admin_url_dd_resources_feature,
			data : {action: 'rebuildHTMLCategories_ajax', 'post_type':the_parent_li.attr('post_type'), nonce:post_type_tab_clicked_on_nonce},
			success: function(response) {
				$('#form-filter-categories-wrapper').fadeOut(400,function () {
					$('#form-filter').css({'height':'auto'});
					$('#form-filter-categories-wrapper').html(response);
					$('#form-filter-categories-wrapper').fadeIn();
				});
			}
		});
		the_parent_li.addClass('active').siblings('li').removeClass('active');
		the_parent_li.html(the_parent_li.attr('post_type_label')+' <span class="tabs-count">('+the_parent_li.attr('count_posts')+')</span>');
		the_parent_li.siblings('li').each(function () {
			$(this).html('<a href="?type='+$(this).attr('post_type')+'">'+$(this).attr('post_type_label')+' <span class="tabs-count">('+$(this).attr('count_posts')+')</span></a>');
		});
		$.fn.runALMfilter();
	});
	$(document).on('click','div.orderby a',function(e) {
		e.preventDefault();
		if ($('#alm_is_animating').val()==1) return false;
		$.fn.switchTitleOrderBy($(this).children('.span-order').text());
		$.fn.runALMfilter();
	});
	$('.link-reset-search').on('click', function(e) {
		e.preventDefault();
		if ($('#alm_is_animating').val()==1) return false;
		$.fn.preResetSearchClearValues();
		$.fn.runALMfilter();
	});

	$.fn.preResetSearchClearValues = function() {
		$('.form-control-search').val('');
		//switch the order to the default one - the Date
		$.fn.switchTitleOrderBy('date');
		//clear all ticked categories checkboxes
		$('.dd_resources_feature-alm-filter-nav li .cbox input[type="checkbox"]').each(function () {
			if ($(this).is(":checked")) $(this).prop('checked', false);
		});
	};
	$.fn.switchTitleOrderBy = function(sort_by_active_one) {
		var thelink=$('div.orderby a'), orderby_html='',  post_type_active_one = thelink.attr('post_type_active_one'), keyword=encodeURIComponent(thelink.attr('keyword')), term_id_url=thelink.attr('term_id_url');
		for (var i in order_items) {
			if (i == sort_by_active_one) {
				orderby_html+=' <span class="span-order order-title">'+sort_by_active_one.substring(0,1)+sort_by_active_one.substring(1)+'</span>';
			} else {
				orderby_html+=' <a post_type_active_one="'+post_type_active_one+'" keyword="'+keyword+'" term_id_url="'+term_id_url+'" href="?type='+post_type_active_one+'&keyword='+keyword+'&sort_by='+encodeURIComponent(i)+term_id_url+'"><span class="span-order">'+i.substring(0,1)+i.substring(1)+'</span></a>';
			}
		}
		$('div.orderby').html('<span>Order by:</span>'+orderby_html);
	};
	$.fn.fadeOutOrfadeInInputsWhileSearchIsInProgress = function(isfadeOut) {
		//fadeout the search inputs, also they are set above not to be used again while the search is in progress
		$('.style-checkbox, .resulttabwrap .horizontaltabs li a, div.orderby a, .link-reset-search').each(function () {
			$(this).fadeTo(500, isfadeOut ? 0.3 : 1);
		});
		$('.search-button').css({'background-blend-mode': isfadeOut ? 'soft-light' : 'normal'});
	};
	//ALM (Ajax Load More) filter
	$.fn.runALMfilter = function() {
		$('#alm_is_animating').val(1);
		var thedata = {}; // the_selected_checkbox.data()
		$.fn.fadeOutOrfadeInInputsWhileSearchIsInProgress(true);
		
		thedata['postType'] = $('.resulttabwrap .horizontaltabs li.active').attr('post_type');
		var order_title = $('div.orderby').children('.order-title').text();
		if (typeof order_items[order_title] != 'undefined') {
			thedata['orderby'] = order_items[order_title].orderby;
			thedata['order'] = order_items[order_title].order;
		}
		thedata['search'] = $('.form-control-search').val() ? $('.form-control-search').val() : null;
		
		if (selected_categories_AND_or_OR_filtering=='AND') {
			var all_selected_categories=[], cat_id, posts_in_cat, add_the_post, post_found_inthecat, thenewcount, thecount_elem;
			$('.dd_resources_feature-alm-filter-nav li .cbox input[type="checkbox"]').each(function () {
				if ($(this).is(":checked")) {
					cat_id = $(this).val();
					if (typeof all_selected_categories[cat_id]=='undefined') all_selected_categories[cat_id]=[];
					posts_in_cat = $(this).attr('data-post--in').split(',');
					for (var i=0;i<posts_in_cat.length;i++) {
						all_selected_categories[cat_id].push(posts_in_cat[i]);
					}
				}
			});
		}
		//reset postIn, it will be built based on checkboxes if they were selected
		thedata['postIn'] = '';
		$('.dd_resources_feature-alm-filter-nav li .cbox input[type="checkbox"]').each(function () {
			if (update_categories_count_on_filtering) {
				$(this).next('span.style-checkbox').next('div.layer-label-wrapper').children('span.style-count').html('<img src="'+site_url+'/wp-content/plugins/dd_features_for_wordpress/classes_features/assets/dd_resources_feature/images/loading_small.gif" style="vertical-align:middle;" />');
			}
			if ($(this).is(":checked")) {
				if (selected_categories_AND_or_OR_filtering=='OR') {
					thedata['postIn'] += thedata['postIn']!='' ? ',' : '';
					thedata['postIn'] += $(this).attr('data-post--in');
				} else if (selected_categories_AND_or_OR_filtering=='AND') {
					thedata['postIn'] += thedata['postIn']=='' ? '-0.5' : '';
					//add the post only if it exists in all selected categories
					posts_in_cat = $(this).attr('data-post--in').split(',');
					for (var i=0;i<posts_in_cat.length;i++) {
						add_the_post=true;
						for (var k in all_selected_categories) {
							post_found_inthecat = false;
							for (var kk=0;kk<all_selected_categories[k].length;kk++) {
								if (posts_in_cat[i] == all_selected_categories[k][kk]) {
									post_found_inthecat=true;
								}
							}
							//if not found in one cat do not add it
							if (!post_found_inthecat) add_the_post=false;
						}
						if (add_the_post) {
							thedata['postIn'] += ',' + posts_in_cat[i];
						}
					}
				}
			}
		});
		if (typeof $.fn.dd_resources_feature_data_tobe_submitted_append == 'function') {
			var theappendeddata = $.fn.dd_resources_feature_data_tobe_submitted_append(thedata);
			for (var i in theappendeddata) {
				thedata[i] = theappendeddata[i];
			}
		}
		var transition = 'slide', // 'slide' | 'fade' | null
		speed = '400'; //in milliseconds
		$.fn.almFilter(transition, speed, thedata); // Run the filter
	};
	$.fn.updateTotalPostsOnFinishedQuery = function(alm) {
		$('.resulttabwrap .horizontaltabs li.active .tabs-count').html('('+alm.totalposts+')');
		$('#alm_is_animating').val(0);
		$.fn.fadeOutOrfadeInInputsWhileSearchIsInProgress(false);
		if (update_categories_count_on_filtering) {
			var posts_in_cat, thenewcount;
			$('.dd_resources_feature-alm-filter-nav li .cbox input[type="checkbox"]').each(function () {
				thenewcount=0;
				posts_in_cat = $(this).attr('data-post--in').split(',');
				for (var i=0;i<posts_in_cat.length;i++) {
					if ($.inArray(posts_in_cat[i], alm.meta.all_post_ids) !== -1) {
						thenewcount++;
					}
				}
				$(this).next('span.style-checkbox').next('div.layer-label-wrapper').children('span.style-count').html('('+thenewcount+')');
				if (show_categories_in_the_filter_with_no_posts || thenewcount>0) {
					if (!$(this).parent('.cbox').parent('li').hasClass('hidefilter')) $(this).parent('.cbox').parent('li').slideDown();
				} else
					$(this).parent('.cbox').parent('li').slideUp();
			});
		}
	};
//	https://connekthq.com/plugins/ajax-load-more/docs/callback-functions/
	$.fn.almComplete = function(alm) {
		$.fn.updateTotalPostsOnFinishedQuery(alm);
	};
	$.fn.almEmpty = function(alm) {
		$(alm.content).append('<li class="alm-no-results-found">'+nothing_found_alm_msg+'</li>');
		$.fn.updateTotalPostsOnFinishedQuery(alm);
	};
	
	//functions used in our own hooks in plugins/ajax-load-more/core/dist/js/ajax-load-more.min.js
	$.fn.dd_resources_feature_before_data_is_submitted = function(alm) {
		$('#alm_is_animating').val(1);
		$.fn.fadeOutOrfadeInInputsWhileSearchIsInProgress(true);
		//add here another potential hook for the client
	};
});