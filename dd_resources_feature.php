<?php
require_once 'dd_abstract_feature.php';

class dd_resources_feature  extends dd_abstract_feature {
	// class instance
	protected static $instance;
	protected static $special_filters = array('date_published');
	
	public function __construct() {
		if (get_option('dd_resources_enabled')) {
			//add extra column to categories
			add_action('dd_resources_category_edit_form_fields', array($this,'dd_resources_category_edit_form_fields'),10,2);
			add_action('dd_resources_category_add_form_fields', array($this,'dd_resources_category_add_form_fields'));
			add_action('edited_dd_resources_category', array($this,'edited_dd_resources_category'),10,2);
			add_action('created_dd_resources_category', array($this,'edited_dd_resources_category'),10,2);
			
			//add extra column to the "Manage Categories" table
			//filter: manage_edit-{$taxonomy}_columns
			add_filter( "manage_edit-dd_resources_category_columns",function ($columns) {
				$columns['icon'] = 'Icon';
				return $columns;
			});
			//action: manage_{$taxonomy}_custom_column
			add_action('manage_dd_resources_category_custom_column', function ($value, $column_name, $tax_id) {
				 if ($column_name === 'icon') {
					 $font_awesome_icon_selected = get_term_meta($tax_id, 'dd_resources_category_font_awesome_icon', true);
					 return !empty($font_awesome_icon_selected) ? '<i class="fa '.$font_awesome_icon_selected.' fa-3x" aria-hidden="true" title="'.$font_awesome_icon_selected.'"></i>' : '';
				 }
			}, 10, 3);
			
			add_action('wp_loaded', function () {
				if (apply_filters('dd_resources_feature_register_post_type_dd_resources', true)) {
					register_post_type('dd_resources',
						array(
							'labels' => dd_features_for_wordpress::get_custom_postype_labels('Resource', 'Resources'),
							'public' => true,
							'show_ui' => true,
							'capability_type' => 'post',
							'supports' => array('title', 'editor', 'thumbnail','excerpt', 'author'),
							'has_archive' => true,
							'menu_icon' => 'dashicons-portfolio',
							'menu_position' => 6,
							'rewrite' => array('slug' => 'dd_resources')
						)
					);
					$args = array(
						'labels' => dd_features_for_wordpress::get_custom_postype_taxonomy_labels('Resource'),
						'hierarchical' => true,
					);
					$dd_resources = get_option('dd_resources',array('dd_resources'));
					register_taxonomy('dd_resources_category', $dd_resources, $args);
					//If Posts are our resources - remove their taxonomy, the dd_resources_category is used
					if (apply_filters('dd_resources_feature_unregister_taxonomy_post', true)) {
						if (in_array('post', $dd_resources)) {
							unregister_taxonomy_for_object_type('category', 'post');
						}
					}
				}
			});
			add_shortcode('dd_resources', array($this, 'dd_resources_shortcode'));
			
			add_action("wp_ajax_rebuildHTMLCategories_ajax", array($this, 'rebuildHTMLCategories_ajax'));
			add_action("wp_ajax_nopriv_rebuildHTMLCategories_ajax", function () {
				die('Allowed only when logged in');
			});
		}
	}
	public function dd_resources_category_add_form_fields($taxonomy) {
		$font_awesome_icons = $this->get_font_awesome_icons();
		$this->dd_font_awesome_icons_list_assets();
		?>
		<div class="form-field term-dd_resources_Cat_fa-wrap">
			<label for="tag-description">Category Icon</label>
			<input type="text" name="dd_resources_category_font_awesome_icon" id="dd_resources_category_font_awesome_icon" size="3" style="width:60%;" value=""> <span id="dd_resources_category_font_awesome_icon_display"></span>
			<p class="font_awesome_icons_list_expand_icons_description"><a href="#" class="font_awesome_icons_list_expand_icons">Expand Icons</a> <span class="description">Click on icon below to use it</span></p>
			<div class="font_awesome_icons_list">
			<?php foreach ($font_awesome_icons as $font_awesome_icon) { ?>
				<i class="fa <?=$font_awesome_icon?> fa-3x" aria-hidden="true" onclick="document.getElementById('dd_resources_category_font_awesome_icon').value='<?=$font_awesome_icon?>';document.getElementById('dd_resources_category_font_awesome_icon_display').innerHTML='<i class=\'fa <?=$font_awesome_icon;?> fa-5x\' aria-hidden=\'true\' style=\'float:right;margin-right:15%;\'></i>';" style="margin:5px;cursor:pointer;" title="<?=$font_awesome_icon?>"></i>
			<?php
			} ?>
			</div>
		</div>
		<?php
	}
	public function dd_resources_category_edit_form_fields($tag, $taxonomy) {
		$font_awesome_icons = $this->get_font_awesome_icons();
		$font_awesome_icon_selected = get_term_meta($tag->term_id, 'dd_resources_category_font_awesome_icon', true);
		$this->dd_font_awesome_icons_list_assets();
		?>
		<tr class="form-field">
		<th scope="row" valign="top"><label for="dd_resources_category_font_awesome_icon">Category Icon</label></th>
		<td style="padding: 0px;padding-top:10px;vertical-align:top;" valign="top">
			<input type="text" name="dd_resources_category_font_awesome_icon" id="dd_resources_category_font_awesome_icon" size="3" style="width:60%;" value="<?=$font_awesome_icon_selected;?>"> <span id="dd_resources_category_font_awesome_icon_display"><i class="fa <?=$font_awesome_icon_selected;?> fa-5x" aria-hidden="true" style="float:right;margin-right:28%;"></i></span>
			<p class="font_awesome_icons_list_expand_icons_description"><a href="#" class="font_awesome_icons_list_expand_icons">Expand Icons</a> <span class="description">Click on icon below to use it</span></p>
		</td>
		</tr>
		<tr class="form-field">
		<td colspan="2">
			<div class="font_awesome_icons_list">
			<?php foreach ($font_awesome_icons as $font_awesome_icon) { ?>
				<i class="fa <?=$font_awesome_icon?> fa-3x" aria-hidden="true" onclick="document.getElementById('dd_resources_category_font_awesome_icon').value='<?=$font_awesome_icon?>';document.getElementById('dd_resources_category_font_awesome_icon_display').innerHTML='<i class=\'fa <?=$font_awesome_icon;?> fa-5x\' aria-hidden=\'true\' style=\'float:right;margin-right:28%;\'></i>';" style="margin:5px;cursor:pointer;" title="<?=$font_awesome_icon?>"></i>
			<?php
			} ?>
			</div>
		</td>
		</tr>
		<?php
	}
	public function edited_dd_resources_category($term_id, $tt_id) {
		if (isset($_POST['dd_resources_category_font_awesome_icon'])) {
			update_term_meta($term_id, 'dd_resources_category_font_awesome_icon', $_POST['dd_resources_category_font_awesome_icon']);
		}
	}
	private function dd_font_awesome_icons_list_assets() {
		$site_url = get_site_url();
		?>
		<style>
			div.font_awesome_icons_list {
				margin-top:5px;
				display: none;
			}
			p.font_awesome_icons_list_expand_icons_description span.description {
				display: none;
			}
		</style>
		<script type="text/javascript">
		jQuery(function ($) {
			$('a.font_awesome_icons_list_expand_icons').click(function (e) {
				e.preventDefault();
				var the_link = $(this);
				$('div.font_awesome_icons_list').slideToggle(400,function () {
					var the_descr = $('p.font_awesome_icons_list_expand_icons_description').children('span.description');
					if (the_link.html() == 'Expand Icons') {
						the_link.html('Collapse Icons');
						the_descr.slideDown();
					} else {
						the_link.html('Expand Icons');
						the_descr.slideUp();
					}
				});
			});
		});
		</script>
		<link rel='stylesheet' href='<?=$site_url?>/wp-content/plugins/dd_features_for_wordpress/classes_features/assets/dd_resources_feature/font-awesome/css/font-awesome.min.css' type='text/css' media='all'/>
	<?php
	}
	private function get_font_awesome_icons() {
		//if you update font-awesome to get the new list of icons go to (in Chrome) http://fontawesome.io/cheatsheet/ , open the developer console and run:
//		var names = [];
//		$('.row .col-md-4').each(function() {
//		  var s = $(this).text();
//		  var m = s.match(/fa-.*/);
//		  if (m && m[0] && s.indexOf('(alias)') < 0) {
//			names.push(m[0]);
//		 }
//		});
//		console.log( JSON.stringify( names ) );
		require_once 'assets/dd_resources_feature/font-awesome-icons-list.php';
		return $font_awesome_icons;
	}
	
	public function dd_resources_shortcode($atts) {
		global $wpdb;
		$atts = shortcode_atts(array(
			'resources' => 'dd_resources'
		), $atts);
		$dd_resources = explode(',', str_replace(' ', '', $atts['resources']));
		if (!empty($dd_resources)) {
				$site_url = get_site_url();
				$plugin_data = get_plugin_data(ABSPATH.'wp-content/plugins/dd_features_for_wordpress/dd_features_for_wordpress.php');
				$plugin_version = $plugin_data['Version'];
				$keyword = isset($_REQUEST['keyword']) ? esc_attr(stripslashes($_REQUEST['keyword'])) : '';
				$post_type_active_one = isset($_REQUEST['type']) ? esc_attr($_REQUEST['type']) : apply_filters('dd_resources_feature_default_post_type','dd_resources');
				$sort_by_active_one = isset($_REQUEST['sort_by']) ? esc_attr($_REQUEST['sort_by']) : 'Date';
				$alm_posts_per_page = apply_filters('dd_resources_feature_alm_posts_per_page', 10);
				$taxonomies = apply_filters('dd_resources_feature_default_parent_category', array('dd_resources_category'));
				$selected_categories_AND_or_OR_filtering = apply_filters('dd_resources_feature_selected_categories_AND_or_OR_filtering', 'OR');
				$update_categories_count_on_filtering = apply_filters('dd_resources_feature_update_categories_count_on_filtering', false);
				$show_categories_in_the_filter_with_no_posts = apply_filters('dd_resources_feature_show_categories_in_the_filter_with_no_posts',true);
				$nothing_found_alm_msg = apply_filters('dd_resources_feature_nothing_found_alm_msg', 'Sorry, nothing found in this filter query.');
				$order_items = apply_filters('dd_resources_feature_order_items',array('Relevance'=>['orderby'=>'menu_order','order'=>'ASC'], 'Title'=>['orderby'=>'title','order'=>'ASC'], 'Date'=>['orderby'=>'date','order'=>'DESC']));
				$post_type_tab_clicked_on_nonce = wp_create_nonce('***');
				$admin_url = admin_url('admin-ajax.php');
				//To link civicrm_events to wp_terms create the table below and add custom linking functionality in civicrm. Example - Ten client (search for the table below)
				/*
				CREATE TABLE `dd_resources_feature_civicrm_event_wp_terms_link` (
					`term_id` BIGINT(20) UNSIGNED NOT NULL,
					`civicrm_event_id` INT UNSIGNED NOT NULL,
					PRIMARY KEY (`term_id`,civicrm_event_id),
					CONSTRAINT `term_id2` FOREIGN KEY (`term_id`) REFERENCES `wp_terms` (`term_id`) ON DELETE CASCADE ON UPDATE CASCADE,
					CONSTRAINT `civicrm_event_id3` FOREIGN KEY (`civicrm_event_id`) REFERENCES `civicrm_event` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;*/
				$table_civicrm_event_wp_terms_link_exists = (bool) $wpdb->get_var('SHOW TABLES LIKE \'dd_resources_feature_civicrm_event_wp_terms_link\'');
				?>
				<link rel='stylesheet' href='<?=$site_url?>/wp-content/plugins/dd_features_for_wordpress/classes_features/assets/dd_resources_feature/style.css?ver=<?=$plugin_version?>' type='text/css' media='all'/>
				<link rel='stylesheet' href='<?=$site_url?>/wp-content/plugins/dd_features_for_wordpress/classes_features/assets/dd_resources_feature/font-awesome/css/font-awesome.min.css?ver=<?=$plugin_version?>' type='text/css' media='all'/>
				<!-- Add fancyBox -->
				<link rel='stylesheet' href='<?=$site_url?>/wp-content/plugins/dd_features_for_wordpress/classes_features/assets/dd_resources_feature/fancyapps-fancyBox/source/jquery.fancybox.css?ver=<?=$plugin_version?>' type='text/css' media='all'/>
				<script type='text/javascript' src='<?=$site_url?>/wp-content/plugins/dd_features_for_wordpress/classes_features/assets/dd_resources_feature/fancyapps-fancyBox/source/jquery.fancybox.pack.js?ver=<?=$plugin_version?>'></script>
				<script type="text/javascript">
					var site_url = '<?=$site_url?>'; //used in the script file below
					var selected_categories_AND_or_OR_filtering = '<?=$selected_categories_AND_or_OR_filtering?>'; //used in the script file below
					var update_categories_count_on_filtering = <?=$update_categories_count_on_filtering ? 1 : 0?>; //used in the script file below
					var show_categories_in_the_filter_with_no_posts = <?=$show_categories_in_the_filter_with_no_posts ? 1 : 0?>; //used in the script file below
					var nothing_found_alm_msg = '<?=esc_js($nothing_found_alm_msg)?>'; //used in the script file below
					var post_type_tab_clicked_on_nonce = '<?=$post_type_tab_clicked_on_nonce?>'; //used in the script file below
					var admin_url_dd_resources_feature = '<?=$admin_url?>'; //used in the script file below
					var table_civicrm_event_wp_terms_link_exists = <?=$table_civicrm_event_wp_terms_link_exists ? 1 : 0?>; //used in the script file below
					var order_items = <?=json_encode($order_items);?>; //used in the script file below
				</script>
				<script type='text/javascript' src='<?=$site_url?>/wp-content/plugins/dd_features_for_wordpress/classes_features/assets/dd_resources_feature/js.js?ver=<?=$plugin_version?>'></script>
				<?php do_action('dd_resources_feature_extra_css_js_files'); ?>
			<div id="dd_resources_feature-wrapper">
			<div class="col-md-8 col-sm-12 searchleft">
				<form action="?" method="post" name="topsearch">
					<input type="hidden" name="type" value="<?=$post_type_active_one?>" />
					<input type="hidden" name="sort_by" value="<?=$sort_by_active_one?>" />
					<input class="form-control-search" type="text" name="keyword" value="<?=$keyword?>" placeholder="<?=apply_filters('dd_resources_feature_shortcode_search_textinput_placeholder','Search...');?>">
					<input class="search-button" type="submit">
				</form>
			</div>
			<?php
			$categories_parent_order_by = apply_filters('dd_resources_feature_categories_parent_order_by', 'name');
			$categories_parent_order = apply_filters('dd_resources_feature_categories_parent_order', 'ASC');
			$categories_children_order_by = apply_filters('dd_resources_feature_categories_children_order_by', 'name'); //for custom order - change to term_group for the client and set the oder in `wp_terms`
			$categories_children_order = apply_filters('dd_resources_feature_categories_children_order', 'ASC');
			//get the 1st parent category as a Topics one
			$browse_by_category_name = apply_filters('dd_resources_feature_browse_by_category_name', 'Topics');
			$taxonomy = $taxonomies[0];
			$browse_by_category = get_terms($taxonomy, array(
				'orderby'=>$categories_parent_order_by,
				'order'=>$categories_parent_order,
				'name'=>$browse_by_category_name,
				'get'=>'all',
				'number'=>1,
				'parent'=>''
			));
			$child_categories = array();
			if ($browse_by_category) {
				$browse_by_category_name = $browse_by_category[0]->name;
				$child_categories = get_terms($taxonomy, array(
					'orderby'=>$categories_children_order_by,
					'order'=>$categories_children_order,
					'get'=>'all',
					'parent'=>$browse_by_category[0]->term_id
				));
			}
			?>
			<div class="col-md-4 col-sm-4 col-xs-4 searchright">
				<a class="topicbtn btn btn-maroon" href="#topicsbox">Browse by <?=$browse_by_category_name?></a>
				<div id="topicsbox" class="topicscontainer" style="display: none;">
					<div class="topicswrap">
						<h3>Choose <?=$browse_by_category_name?> <input class="submit-topics" type="button" value="Submit <?=esc_attr($browse_by_category_name);?>"></h3>
						<?php
						if (!empty($child_categories)) {
							foreach ($child_categories as $child_category) {
								$font_awesome_icon_selected = get_term_meta($child_category->term_id, 'dd_resources_category_font_awesome_icon', true);
								?>
								<div class="col-md-3 col-sm-3">
									<div class="topicsinner" term_id="<?=$child_category->term_id?>"><?php if (!empty($font_awesome_icon_selected)) { ?><i class="fa <?=$font_awesome_icon_selected?> fa-3x" aria-hidden="true" title="<?=$font_awesome_icon_selected?>"></i><br/><?php } ?><p><?=$child_category->name?></p></div>
									<h4 class="mobile-title-toggle"><a href="?term_id[]=<?=$child_category->term_id?>&keyword=<?=urlencode($keyword)?>&type=<?=$post_type_active_one?>&sort_by=<?=$sort_by_active_one?>"><?=$child_category->name?></a></h4>
									<ul class="mobile-content-toggle">
									</ul>
								</div>
						<?php
							}
						}
						?>
					</div>
				</div>
			</div>
			<div class="article-sidebar-wrapper">
			<div class="primary col-md-8 col-sm-12">
			<article class="entry">
				<div class="resulttabwrap">
					<ul class="horizontaltabs">
				<?php
				$is_special_filter_triggered = false;
				foreach (self::$special_filters as $special_filter) {
					if (isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter])) {
						$is_special_filter_triggered = true;
						break 1;
					}
				}
				$all_resources = self::get_all_resources();
				foreach ($dd_resources as $dd_resource) {
					if ($dd_resource == 'civicrm_events') {
						$sql = 'SELECT count(e.id) FROM civicrm_event e
						JOIN civicrm_option_value ov ON e.event_type_id=ov.value AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name=\'event_type\' AND is_active=1)';
						if (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && $table_civicrm_event_wp_terms_link_exists) {
							$sql .= ' JOIN dd_resources_feature_civicrm_event_wp_terms_link tl ON e.id=tl.civicrm_event_id';
						}
						$sql .= ' WHERE e.is_active=1 AND e.end_date>=NOW()';
						if (!empty($keyword) && $post_type_active_one == $dd_resource) {
							$sql .= ' AND (e.title LIKE %s OR e.summary LIKE %s OR e.description LIKE %s)';
						}
						if (((isset($_REQUEST['event_types']) && is_array($_REQUEST['event_types'])) || (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id'])) || $is_special_filter_triggered) && $post_type_active_one == $dd_resource) {
							if ($is_special_filter_triggered) {
								foreach (self::$special_filters as $special_filter) {
									if (isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter])) {
										if ($special_filter == 'date_published') {
											$dates = array();
											foreach ($_REQUEST['term_id_'.$special_filter] as $year) {
												$dates[] = "'".$wpdb->escape($year)."'";
											}
											$sql .= " AND YEAR(STR_TO_DATE(e.start_date, '%%Y')) IN (".  implode(',', $dates).")";
										}
									}
								}
							}
							if (isset($_REQUEST['event_types']) && is_array($_REQUEST['event_types'])) {
								$event_types_escaped = array();
								foreach ($_REQUEST['event_types'] as $event_type) {
									$event_types_escaped[] = $wpdb->escape($event_type);
								}
								if (!empty($event_types_escaped))
								$sql .= ' AND ov.value IN ('.implode(',', $event_types_escaped).')';
							}
							if (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && $table_civicrm_event_wp_terms_link_exists) {
								$term_id_escaped = array();
								foreach ($_REQUEST['term_id'] as $term_id) {
									$term_id_escaped[] = $wpdb->escape($term_id);
								}
								if (!empty($term_id_escaped))
								$sql .= ' AND tl.term_id IN ('.implode(',', $term_id_escaped).')';
							}
						}
						$sql = $wpdb->prepare($sql,'%'.$keyword.'%','%'.$keyword.'%','%'.$keyword.'%');
						$count_posts = $wpdb->get_var($sql);
					} else {
						$args = array('post_type' => $dd_resource, 'post_status'=>array('publish'), 'nopaging'=>true, 'suppress_filters'=>true);
						if (((isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id'])) || $is_special_filter_triggered) && $post_type_active_one == $dd_resource) {
							$args['post__in'] = array();
							if ($is_special_filter_triggered) {
								foreach (self::$special_filters as $special_filter) {
									if (isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter])) {
										if ($special_filter == 'date_published') {
											$dates = array();
											foreach ($_REQUEST['term_id_'.$special_filter] as $year) {
												$dates[] = "'".$wpdb->escape($year)."'";
											}
											$result = $wpdb->get_col("SELECT ID FROM wp_posts WHERE post_type='$dd_resource' AND post_status='publish' AND YEAR(STR_TO_DATE(post_date, '%Y')) IN (".  implode(',', $dates).')');
											$args['post__in'] = $result ? $result : array(-0.5);
										}
									}
								}
							}
							if (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id'])) {
								$term_posts = get_objects_in_term($_REQUEST['term_id'],$taxonomies);
								$term_posts = $term_posts ? $term_posts : array(-0.5);
								$args['post__in'] = array_merge($args['post__in'], $term_posts);
							}
						}
						if (!empty($keyword) && $post_type_active_one == $dd_resource) {
							$args['s'] = $keyword;
						}
						wp_reset_postdata();
						$count_posts = count(get_posts($args));
					}
					$post_type_label = str_ireplace('CiviCRM ', '', $all_resources[$dd_resource]->labels->name); ?>
					<li post_type="<?=esc_attr($dd_resource)?>" post_type_label="<?=esc_attr($post_type_label)?>" count_posts="<?=esc_attr($count_posts)?>"<?=$post_type_active_one == $dd_resource ? ' class="active"' : ''?>><?php if ($post_type_active_one != $dd_resource) { ?><a href="?type=<?=$dd_resource?>"><?php } ?><?=$post_type_label.' <span class="tabs-count">('.$count_posts.')</span>';?><?php if ($post_type_active_one != $dd_resource) { ?></a><?php } ?></li>
				<?php
				} ?>
					</ul>
				</div>
				<div class="results-wrapper">
					<div id="tabresources" class="tab">
						<div class="orderby"><span>Order by:</span>
							<?php
							foreach ($order_items as $sort_by => $sort_details) {
								if ($sort_by_active_one == $sort_by) { ?>
									<span class="span-order order-title"><?=$sort_by?></span>
								<?php
								} else {
									$term_id_url = '';
									if (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id'])) {
										foreach ($_REQUEST['term_id'] as $term_id) {
											$term_id_url .= '&term_id[]='.urlencode($term_id);
										}
									}
									if (isset($_REQUEST['event_types']) && is_array($_REQUEST['event_types'])) {
										foreach ($_REQUEST['event_types'] as $event_type) {
											$term_id_url .= '&event_types[]='.urlencode($event_type);
										}
									}
									?>
									<a post_type_active_one="<?=$post_type_active_one?>" keyword="<?=urlencode($keyword)?>" term_id_url="<?=$term_id_url?>" href="?type=<?=$post_type_active_one?>&keyword=<?=urlencode($keyword);?>&sort_by=<?=$sort_by.$term_id_url?>"><span class="span-order"><?=$sort_by?></span></a>
								<?php
								}
							}
							?>
						</div>
						<div class="items">
							<?php
							//if the ajax_load_more repeater has to be different than the default one. The repeater must be overloaded in the child theme - wp-content/themes/kleo-child/alm_templates/default.php
							$alm_repeater = apply_filters('dd_resources_feature_alm_repeater','default');
							$alm_load_on_scroll = apply_filters('dd_resources_feature_alm_load_on_scroll','true');
							$post__in = array();
							if ($post_type_active_one == 'civicrm_events') {
								$sql = 'SELECT e.id FROM civicrm_event e';
								if (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && $table_civicrm_event_wp_terms_link_exists) {
									$sql .= ' JOIN dd_resources_feature_civicrm_event_wp_terms_link tl ON e.id=tl.civicrm_event_id';
								}
								$sql .= ' JOIN civicrm_option_value ov ON e.event_type_id=ov.value AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name=\'event_type\' AND is_active=1)	
								WHERE e.is_active=1 AND e.end_date>=NOW()';
								if (!empty($keyword)) $sql .= ' AND (e.title LIKE %s OR e.summary LIKE %s OR e.description LIKE %s)';
								if ((isset($_REQUEST['event_types']) && is_array($_REQUEST['event_types'])) || (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id'])) || $is_special_filter_triggered) {
									if ($is_special_filter_triggered) {
										foreach (self::$special_filters as $special_filter) {
											if (isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter])) {
												if ($special_filter == 'date_published') {
													$dates = array();
													foreach ($_REQUEST['term_id_'.$special_filter] as $year) {
														$dates[] = "'".$wpdb->escape($year)."'";
													}
													$sql .= " AND YEAR(STR_TO_DATE(e.start_date, '%%Y')) IN (".  implode(',', $dates).")";
												}
											}
										}
									}
									if (isset($_REQUEST['event_types']) && is_array($_REQUEST['event_types'])) {
										$event_types_escaped = array();
										foreach ($_REQUEST['event_types'] as $event_type) {
											$event_types_escaped[] = $wpdb->escape($event_type);
										}
										if (!empty($event_types_escaped))
										$sql .= ' AND ov.value IN ('.implode(',', $event_types_escaped).')';
									}
									if (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && $table_civicrm_event_wp_terms_link_exists) {
										$term_id_escaped = array();
										foreach ($_REQUEST['term_id'] as $term_id) {
											$term_id_escaped[] = $wpdb->escape($term_id);
										}
										if (!empty($term_id_escaped))
										$sql .= ' AND tl.term_id IN ('.implode(',', $term_id_escaped).')';
									}
								}
								if (!empty($keyword)) $sql = $wpdb->prepare($sql,'%'.$keyword.'%','%'.$keyword.'%','%'.$keyword.'%');
								$result = $wpdb->get_col($sql);
								$post__in = $result ? $result : array(-0.5);
							} else {
								if ((isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id'])) || $is_special_filter_triggered) {
									if ($is_special_filter_triggered) {
										foreach (self::$special_filters as $special_filter) {
											if (isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter])) {
												if ($special_filter == 'date_published') {
													$dates = array();
													foreach ($_REQUEST['term_id_'.$special_filter] as $year) {
														$dates[] = "'".$wpdb->escape($year)."'";
													}
													$result = $wpdb->get_col("SELECT ID FROM wp_posts WHERE post_type='$post_type_active_one' AND post_status='publish' AND YEAR(STR_TO_DATE(post_date, '%Y')) IN (".  implode(',', $dates).')');
													$post__in = $result ? $result : array(-0.5);
												}
											}
										}
									}
								}
							}
							$shortcode = '[ajax_load_more post_type="'.$post_type_active_one.'" repeater="'.$alm_repeater.'" posts_per_page="'.$alm_posts_per_page.'" scroll="'.$alm_load_on_scroll.'" transition="fade" button_label="Load More..."';
							if (!empty($orderby)) {
								$shortcode.=' orderby="'.$orderby.'"';
							}
							if (!empty($order)) {
								$shortcode.=' order="'.$order.'"';
							}
							if (!empty($keyword)) {
								$shortcode.=' search="'.$keyword.'"';
							}
							if ($post_type_active_one != 'civicrm_events' && isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id'])) {
								$post__in = array_merge($post__in, $term_posts);
							}
							if (!empty($post__in)) {
								$shortcode.=' post__in="'.implode(',', $post__in).'"';
							}
							$shortcode.=']';
							echo do_shortcode($shortcode);
							?>
						</div>
					</div>
				</div>
			</article>
			</div>
			<aside class="sidebar right col-md-4 col-sm-12 col-xs-12">
				<div class="filter-widget widget">
					<form action="?" method="post" id="form-filter">
						<input type="hidden" id="alm_is_animating" value="0" />
						<input type="hidden" name="keyword" value="<?=$keyword?>" />
						<input type="hidden" name="type" value="<?=$post_type_active_one?>" />
						<input type="hidden" name="sort_by" value="<?=$sort_by_active_one?>" />
						<div id="form-filter-categories-wrapper">
						<?php
						$this->buildHTMLCategories($post_type_active_one); ?>
						</div>
					</form>
				</div>
				<div class="layer-reset-search">
					<a href="<?=strtok($_SERVER["REQUEST_URI"],'?');?>" class="link-reset-search">Reset search</a>
				</div>
			</aside>
			</div>
			</div>
		<?php
		}
	}
	public function rebuildHTMLCategories_ajax() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'], '***')) {
			die("nonce not valid");
		}
		die($this->buildHTMLCategories($_POST['post_type']));
	}
	private function buildHTMLCategories($post_type_active_one) {
		global $wpdb;
		$taxonomies = apply_filters('dd_resources_feature_default_parent_category', array('dd_resources_category'));
		$show_categories_in_the_filter_with_no_posts = apply_filters('dd_resources_feature_show_categories_in_the_filter_with_no_posts',true);
		$dd_resources_show_more_button_enabled = get_option('dd_resources_show_more_button_enabled');
		
		$categories_parent_order_by = apply_filters('dd_resources_feature_categories_parent_order_by', 'name');
		$categories_parent_order = apply_filters('dd_resources_feature_categories_parent_order', 'ASC');
		$categories_children_order_by = apply_filters('dd_resources_feature_categories_children_order_by', 'name'); //for custom order - change to term_group for the client and set the oder in `wp_terms`
		$categories_children_order = apply_filters('dd_resources_feature_categories_children_order', 'ASC');
		
		$exclude_dd_resources_categories_from_frontend = apply_filters('exclude_dd_resources_categories_from_frontend', array());
		$dd_resources_feature_show_theparent_category_as_searchable = apply_filters('dd_resources_feature_show_theparent_category_as_searchable', true);
		$dd_resources_feature_show_theparent_category_if_no_children = apply_filters('dd_resources_feature_show_theparent_category_if_no_children', true);
		$dd_resources_special_filter_custom_filter_show_after_taxonomy = apply_filters('dd_resources_special_filter_custom_filter_show_after_taxonomy', null);
		$dd_resources_special_filter_custom_filter_shown = false;
		if ($post_type_active_one == 'civicrm_events') {
			$table_civicrm_event_wp_terms_link_exists = (bool) $wpdb->get_var('SHOW TABLES LIKE \'dd_resources_feature_civicrm_event_wp_terms_link\'');
			if ($table_civicrm_event_wp_terms_link_exists) {
			//get the 1st parent category as a Topics one
			$browse_by_category_name = apply_filters('dd_resources_feature_browse_by_category_name', 'Topics');
			$taxonomy=$taxonomies[0];
			$browse_by_category = get_terms($taxonomy, array(
				'orderby'=>$categories_parent_order_by,
				'order'=>$categories_parent_order,
				'name'=>$browse_by_category_name,
				'get'=>'all',
				'number'=>1,
				'parent'=>''
			));
			if ($browse_by_category)
			foreach ($browse_by_category as $parent_category) {
				if (in_array($parent_category->name, $exclude_dd_resources_categories_from_frontend)) {
					continue;
				}
				$child_categories = get_terms($taxonomy, array(
					'orderby'=>$categories_children_order_by,
					'order'=>$categories_children_order,
					'get'=>'all',
					'parent'=>$parent_category->term_id
				));
				$activetab = '';
				if (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id'])) {
					if (in_array($parent_category->term_id, $_REQUEST['term_id'])) {
						$activetab = ' activetab';
					} else {
						foreach ($child_categories as $child_category) {
							if (in_array($child_category->term_id, $_REQUEST['term_id'])) {
								$activetab = ' activetab';
								break 1;
							}
						}
					}
				}
				if ($dd_resources_feature_show_theparent_category_as_searchable) {
					$selected = isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && in_array($parent_category->term_id, $_REQUEST['term_id']);
					$sql = 'SELECT e.id FROM civicrm_event e
							JOIN dd_resources_feature_civicrm_event_wp_terms_link tl ON e.id=tl.civicrm_event_id
							JOIN civicrm_option_value ov ON e.event_type_id=ov.value AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name=\'event_type\' AND is_active=1)
							WHERE e.is_active=1 AND e.end_date>=NOW() AND tl.term_id='.absint($parent_category->term_id);

					$result = $wpdb->get_col($sql);
					$count_posts = count($result);
				}
				?>
				<h3 class="filter-title<?=$activetab?>"><?=$parent_category->name;?></h3>
				<div class="filter-content" style="display:<?=$activetab ? 'block' : 'none'?>;">
					<ul class="no-style dd_resources_feature-alm-filter-nav">
						<?php if ($dd_resources_feature_show_theparent_category_as_searchable && ($show_categories_in_the_filter_with_no_posts || $count_posts>0)) : ?>
						<li>
							<label class="cbox"><input type="checkbox" name="term_id[]" id="term_id<?=$parent_category->term_id;?>" value="<?=$parent_category->term_id;?>"<?=$selected ? ' checked' : ''?> data-post--in="<?=implode(',',$result ? $result : array(-0.5))?>" /><span class="style-checkbox"></span><div class="layer-label-wrapper"><?=$parent_category->name;?> <span class="style-count">(<?=$count_posts;?>)</span></div></label>
						</li>
						<?php
						endif;
						if (($apply_hide_filter = $dd_resources_show_more_button_enabled)) {
							$count_child_categories = 0;
							foreach ($child_categories as $child_category) {
								$selected = isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && in_array($child_category->term_id, $_REQUEST['term_id']);
								if ($selected && $count_child_categories>=4) {
									$apply_hide_filter = false;
									break 1;
								}
								$count_child_categories++;
							}
						}
						$count_child_categories = 0;
						foreach ($child_categories as $child_category) {
							$selected = isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && in_array($child_category->term_id, $_REQUEST['term_id']);
							$sql = 'SELECT e.id FROM civicrm_event e
									JOIN dd_resources_feature_civicrm_event_wp_terms_link tl ON e.id=tl.civicrm_event_id
									JOIN civicrm_option_value ov ON e.event_type_id=ov.value AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name=\'event_type\' AND is_active=1)
									WHERE e.is_active=1 AND e.end_date>=NOW() AND tl.term_id='.absint($child_category->term_id);
							$result = $wpdb->get_col($sql);
							$count_posts = count($result);
						if ($show_categories_in_the_filter_with_no_posts || $count_posts>0) : ?>
						<li class="<?=$count_child_categories>=4 && $apply_hide_filter ? 'hidefilter' : ''?>">
							<label class="cbox"><input type="checkbox" name="term_id[]" id="term_id<?=$child_category->term_id;?>" value="<?=$child_category->term_id;?>"<?=$selected ? ' checked' : ''?> data-post--in="<?=implode(',',$result ? $result : array(-0.5))?>" /><span class="style-checkbox"></span><div class="layer-label-wrapper"><?=$child_category->name;?> <span class="style-count">(<?=$count_posts;?>)</span></div></label>
						</li>
						<?php
							$count_child_categories++;
						endif;
						}
						?>
					</ul>
					<?php if ($apply_hide_filter && $count_child_categories>4) { ?>
					<div class="layer-more">
						<a class="link-show-more-filter" data-max="5">Show more</a>
					</div>
					<?php } ?>
				</div>
			<?php
			}
			}
			$sql = 'SELECT ov.value, ov.label FROM civicrm_option_value ov
			WHERE ov.is_active=1 AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name=\'event_type\' AND is_active=1) ORDER BY ov.label';
			$event_types = $wpdb->get_results($sql);
			$activetab = '';
			if (isset($_REQUEST['event_types']) && is_array($_REQUEST['event_types'])) {
				foreach ($event_types as $event_type) {
					if (in_array($event_type->value, $_REQUEST['event_types'])) {
						$activetab = ' activetab';
						break 1;
					}
				}
			}
			?>
			<h3 class="filter-title<?=$activetab?>">Event Types</h3>
			<div class="filter-content" style="display:<?=$activetab ? 'block' : 'none'?>;">
				<ul class="no-style dd_resources_feature-alm-filter-nav">
					<?php
					if (($apply_hide_filter = $dd_resources_show_more_button_enabled)) {
						$count_child_categories = 0;
						foreach ($event_types as $event_type) {
							$selected = isset($_REQUEST['event_types']) && is_array($_REQUEST['event_types']) && in_array($event_type->value, $_REQUEST['event_types']);
							if ($selected && $count_child_categories>=4) {
								$apply_hide_filter = false;
								break 1;
							}
							$count_child_categories++;
						}
					}
					$count_child_categories = 0;
					foreach ($event_types as $event_type) {
						$selected = isset($_REQUEST['event_types']) && is_array($_REQUEST['event_types']) && in_array($event_type->value, $_REQUEST['event_types']);
						$result = $wpdb->get_col('SELECT id FROM civicrm_event WHERE event_type_id='.$event_type->value.' AND is_active=1 AND end_date>=NOW()');
						$count_events=count($result);
						if ($show_categories_in_the_filter_with_no_posts || $count_events>0) : ?>
					<li class="<?=$count_child_categories>=4 && $apply_hide_filter ? 'hidefilter' : ''?>">
						<label class="cbox"><input type="checkbox" name="event_types[]" id="event_types<?=$event_type->value;?>" value="<?=$event_type->value;?>"<?=$selected ? ' checked' : ''?> data-post--in="<?=implode(',',$result ? $result : array(-0.5))?>" /><span class="style-checkbox"></span><div class="layer-label-wrapper"><?=$event_type->label;?> <span class="style-count">(<?=$count_events;?>)</span></div></label>
					</li>
					<?php
						$count_child_categories++;
						endif;
					}
					?>
				</ul>
				<?php if ($apply_hide_filter && $count_child_categories>4) { ?>
				<div class="layer-more">
					<a class="link-show-more-filter" data-max="5">Show more</a>
				</div>
				<?php } ?>
			</div>
		<?php
		} else if ($taxonomies) {
		$show_taxonomy_labels = count($taxonomies)>1;
		foreach ($taxonomies as $taxonomy) {
		$taxonomy_obj = get_taxonomy($taxonomy);
		$parent_categories = get_terms($taxonomy, array(
			'orderby'=>$categories_parent_order_by,
			'order'=>$categories_parent_order,
			'get'=>'all',
			'parent'=>0
		));
		$taxonomy_label_shown = false;
		foreach ($parent_categories as $parent_category) {
			if (in_array($parent_category->name, $exclude_dd_resources_categories_from_frontend)) {
				continue;
			}
			$child_categories = get_terms($taxonomy, array(
				'orderby'=>$categories_children_order_by,
				'order'=>$categories_children_order,
				'get'=>'all',
				'parent'=>$parent_category->term_id
			));
			$activetab = '';
			if (isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id'])) {
				if (in_array($parent_category->term_id, $_REQUEST['term_id'])) {
					$activetab = ' activetab';
				} else {
					foreach ($child_categories as $child_category) {
						if (in_array($child_category->term_id, $_REQUEST['term_id'])) {
							$activetab = ' activetab';
							break 1;
						}
						$child_categories2 = get_terms($taxonomy, array(
							'orderby'=>$categories_children_order_by,
							'order'=>$categories_children_order,
							'get'=>'all',
							'parent'=>$child_category->term_id
						));
						foreach ($child_categories2 as $child_category2) {
							if (in_array($child_category2->term_id, $_REQUEST['term_id'])) {
								$activetab = ' activetab';
								break 2;
							}
						}
					}
				}
			}
			if ($dd_resources_feature_show_theparent_category_as_searchable) {
				$selected = isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && in_array($parent_category->term_id, $_REQUEST['term_id']);
				$result = $this->getPostIdsOfCategory($post_type_active_one, $parent_category->term_id);
				$count_posts = count($result);
			}
			if ($show_taxonomy_labels && !$taxonomy_label_shown) : ?>
			<h2 class="dd_resources_feature-category-section-title"><?=$taxonomy_obj->labels->singular_name?></h2>
			<?php
				$taxonomy_label_shown=true;
			endif;
			$hide_parent_category_section = false;
			if (!$dd_resources_feature_show_theparent_category_if_no_children) {
				$there_is_child_cat = false;
				if ($dd_resources_feature_show_theparent_category_as_searchable && ($show_categories_in_the_filter_with_no_posts || $count_posts>0)) {
					$there_is_child_cat = true;
				}
				if (!$there_is_child_cat) {
					foreach ($child_categories as $child_category) {
						if ($this->getPostIdsOfCategory($post_type_active_one, $child_category->term_id)) {
							$there_is_child_cat = true;break 1;
						}
						$child_categories2 = get_terms($taxonomy, array(
							'orderby'=>$categories_children_order_by,
							'order'=>$categories_children_order,
							'get'=>'all',
							'parent'=>$child_category->term_id
						));
						foreach ($child_categories2 as $child_category2) {
							if ($this->getPostIdsOfCategory($post_type_active_one, $child_category2->term_id)) {
								$there_is_child_cat = true;break 2;
							}
						}
					}
				}
				$hide_parent_category_section = !$there_is_child_cat;
			}
		if (!$hide_parent_category_section) {
		?>
		<h3 class="filter-title<?=$activetab?>"><?=$parent_category->name;?></h3>
		<div class="filter-content" style="display:<?=$activetab ? 'block' : 'none'?>;">
			<ul class="no-style dd_resources_feature-alm-filter-nav">
				<?php if ($dd_resources_feature_show_theparent_category_as_searchable && ($show_categories_in_the_filter_with_no_posts || $count_posts>0)) : ?>
				<li>
					<label class="cbox"><input type="checkbox" name="term_id[]" id="term_id<?=$parent_category->term_id;?>" value="<?=$parent_category->term_id;?>"<?=$selected ? ' checked' : ''?> data-post--in="<?=implode(',',$result ? $result : array(-0.5))?>" /><span class="style-checkbox"></span><div class="layer-label-wrapper"><?=$parent_category->name;?> <span class="style-count">(<?=$count_posts;?>)</span></div></label>
				</li>
				<?php
				endif;
				if (($apply_hide_filter = $dd_resources_show_more_button_enabled)) {
					$count_child_categories = 0;
					foreach ($child_categories as $child_category) {
						$selected = isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && in_array($child_category->term_id, $_REQUEST['term_id']);
						if ($selected && $count_child_categories>=4) {
							$apply_hide_filter = false;
							break 1;
						}
						$child_categories2 = get_terms($taxonomy, array(
							'orderby'=>$categories_children_order_by,
							'order'=>$categories_children_order,
							'get'=>'all',
							'parent'=>$child_category->term_id
						));
						foreach ($child_categories2 as $child_category2) {
							$selected = isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && in_array($child_category2->term_id, $_REQUEST['term_id']);
							if ($selected && $count_child_categories>=4) {
								$apply_hide_filter = false;
								break 2;
							}
							$count_child_categories++;
						}
						$count_child_categories++;
					}
				}
				$count_child_categories = 0;
				foreach ($child_categories as $child_category) {
					$selected = isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && in_array($child_category->term_id, $_REQUEST['term_id']);
					$result = $this->getPostIdsOfCategory($post_type_active_one, $child_category->term_id);
					$count_posts = count($result);
					if ($show_categories_in_the_filter_with_no_posts || $count_posts>0) :
					$has_subchild = get_terms($taxonomy, array(
						'orderby'=>$categories_children_order_by,
						'order'=>$categories_children_order,
						'get'=>'all',
						'number'=>1,
						'parent'=>$child_category->term_id
					)) ? 'has_subchild' : '';
					?>
					<li class="<?=$has_subchild.($count_child_categories>=4 && $apply_hide_filter ? ($has_subchild?' ':'').'hidefilter' : '')?>">
						<label class="cbox"><input type="checkbox" name="term_id[]" id="term_id<?=$child_category->term_id;?>" value="<?=$child_category->term_id;?>"<?=$selected ? ' checked' : ''?> data-post--in="<?=implode(',',$result ? $result : array(-0.5))?>" /><span class="style-checkbox"></span><div class="layer-label-wrapper"><?=$child_category->name;?> <span class="style-count">(<?=$count_posts;?>)</span></div></label>
					</li>
					<?php
						$count_child_categories++;
					endif;
					$child_categories2 = get_terms($taxonomy, array(
						'orderby'=>$categories_children_order_by,
						'order'=>$categories_children_order,
						'get'=>'all',
						'parent'=>$child_category->term_id
					));
					foreach ($child_categories2 as $child_category2) {
						$selected = isset($_REQUEST['term_id']) && is_array($_REQUEST['term_id']) && in_array($child_category2->term_id, $_REQUEST['term_id']);
						$result = $this->getPostIdsOfCategory($post_type_active_one, $child_category2->term_id);
						$count_posts = count($result);
						if ($show_categories_in_the_filter_with_no_posts || $count_posts>0) : ?>
						<li class="subchild<?=$count_child_categories>=4 && $apply_hide_filter ? ' hidefilter' : ''?>">
							<label class="cbox"><input type="checkbox" name="term_id[]" id="term_id<?=$child_category2->term_id;?>" value="<?=$child_category2->term_id;?>"<?=$selected ? ' checked' : ''?> data-post--in="<?=implode(',',$result ? $result : array(-0.5))?>" /><span class="style-checkbox"></span><div class="layer-label-wrapper"><?=$child_category2->name;?> <span class="style-count">(<?=$count_posts;?>)</span></div></label>
						</li>
						<?php
							$count_child_categories++;
						endif;
					}
				}
				?>
			</ul>
			<?php if ($apply_hide_filter && $count_child_categories>4) { ?>
			<div class="layer-more">
				<a class="link-show-more-filter" data-max="5">Show more</a>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
		<?php }
			if ($dd_resources_special_filter_custom_filter_show_after_taxonomy == $taxonomy) {
				$dd_resources_special_filter_custom_filter_shown = true;
				//Add additional filter specific for the client
				do_action('dd_resources_special_filter_custom_filter',$post_type_active_one,$dd_resources_feature_show_theparent_category_if_no_children,$show_categories_in_the_filter_with_no_posts,$dd_resources_show_more_button_enabled);
			}
		}
		}
		if (get_option('dd_resources_special_filter_date_published_enabled')) { ?>
		<!-- Special Filters (not categories) i.e. Date published -->
		<?php
		$hide_parent_category_section = false;
		if (!$dd_resources_feature_show_theparent_category_if_no_children && !$show_categories_in_the_filter_with_no_posts) {
			$there_is_child_cat = false;
			for ($i=0;$i<15;$i++) {
				$year = date('Y',strtotime('-'.$i.' years'));
				if ($post_type_active_one == 'civicrm_events') {
					$sql = "SELECT e.id FROM civicrm_event e
					JOIN civicrm_option_value ov ON e.event_type_id=ov.value AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name='event_type' AND is_active=1)	
					WHERE e.is_active=1 AND e.end_date>=NOW() AND YEAR(STR_TO_DATE(e.start_date, '%Y')) IN ('".$year."') LIMIT 1";
				} else {
					$sql = $wpdb->prepare("SELECT ID FROM wp_posts WHERE post_type=%s AND post_status='publish' AND YEAR(STR_TO_DATE(post_date, '%Y')) IN ('".$year."') LIMIT 1",$post_type_active_one);
				}
				if ($wpdb->get_var($sql)) {
					$there_is_child_cat = true;break 1;
				}
			}
			$hide_parent_category_section = !$there_is_child_cat;
		}
		if (!$hide_parent_category_section) {
			$special_filter = 'date_published';
			$activetab = isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter]);
		?>
		<h3 class="filter-title<?=$activetab ? ' activetab' : ''?>">Date published</h3>
		<div class="filter-content" style="display:<?=$activetab ? 'block' : 'none'?>;">
			<ul class="no-style dd_resources_feature-alm-filter-nav">
				<?php
				if (($apply_hide_filter = $dd_resources_show_more_button_enabled)) {
					for ($i=0;$i<15;$i++) {
						$year = date('Y',strtotime('-'.$i.' years'));
						$selected = isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter]) && in_array($year, $_REQUEST['term_id_'.$special_filter]);
						if ($selected && $i>4) {
							$apply_hide_filter = false;
							break 1;
						}
					}
				}
				$count_years_categories = 0;
				for ($i=0;$i<15;$i++) {
					$year = date('Y',strtotime('-'.$i.' years'));
					if ($post_type_active_one == 'civicrm_events') {
						$sql = "SELECT e.id FROM civicrm_event e
						JOIN civicrm_option_value ov ON e.event_type_id=ov.value AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name='event_type' AND is_active=1)	
						WHERE e.is_active=1 AND e.end_date>=NOW() AND YEAR(STR_TO_DATE(e.start_date, '%Y')) IN ('".$year."')";
						$result = $wpdb->get_col($sql);
						$count_posts = count($result);
						$extra_data_bind_attrs = ' data-post--in="'.implode(',',$result ? $result : array(-0.5)).'"';
					} else {
						wp_reset_postdata();
						$args = array('post_type' => $post_type_active_one, 'post_status'=>array('publish'), 'nopaging'=>true, 'suppress_filters'=>true);
						$result = $wpdb->get_col($wpdb->prepare("SELECT ID FROM wp_posts WHERE post_type=%s AND post_status='publish' AND YEAR(STR_TO_DATE(post_date, '%Y')) IN ('".$year."')",$post_type_active_one));
						$args['post__in'] = $result ? $result : array(-0.5);
						$count_posts = count(get_posts($args));
						wp_reset_postdata();
						$extra_data_bind_attrs = ' data-post--in="'.implode(',',$args['post__in']).'"';
					}
					$selected = isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter]) && in_array($year, $_REQUEST['term_id_'.$special_filter]);
					if ($show_categories_in_the_filter_with_no_posts || $count_posts>0) :
					?>
					<li class="<?=$count_years_categories>4 && $apply_hide_filter ? 'hidefilter' : ''?>">
					<label class="cbox"><input type="checkbox" name="term_id_<?=$special_filter?>[]" id="term_id<?=$year;?>" value="<?=$year;?>"<?=$selected ? ' checked' : ''?><?=$extra_data_bind_attrs?> /><span class="style-checkbox"></span><div class="layer-label-wrapper"><?=$year;?> <span class="style-count">(<?=$count_posts?>)</span></div></label>
				</li>
				<?php
						$count_years_categories++;
					endif;
				}
				?>
			</ul>
			<?php if ($apply_hide_filter && $count_years_categories>4) { ?>
			<div class="layer-more">
				<a class="link-show-more-filter" data-max="5">Show more</a>
			</div>
			<?php } ?>
		</div>
		<!-- End Special Filter Date Published -->
		<?php }
		}
		if (get_option('dd_resources_special_filter_authors_enabled')) { ?>
		<!-- Special Filter Authors -->
		<?php
		$hide_parent_category_section = false;
		if (!$dd_resources_feature_show_theparent_category_if_no_children && !$show_categories_in_the_filter_with_no_posts) {
			$there_is_child_cat = false;
			if ($post_type_active_one == 'civicrm_events') {
				$sql = "SELECT p.id FROM civicrm_participant p
				JOIN civicrm_event e ON e.id=p.event_id
				JOIN civicrm_option_value ov ON e.event_type_id=ov.value AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name='event_type' AND is_active=1)
				JOIN civicrm_contact c ON c.id=p.contact_id
				JOIN civicrm_uf_match ufm ON ufm.contact_id=p.contact_id
				JOIN wp_users u ON ufm.uf_id=u.ID
				GROUP BY u.ID
				LIMIT 1";
			} else {
				$sql = $wpdb->prepare("SELECT p.ID FROM wp_posts p
					JOIN wp_users u ON p.post_author=u.ID
					WHERE p.post_type=%s AND p.post_status='publish' GROUP BY u.ID LIMIT 1",$post_type_active_one);
			}
			if ($wpdb->get_var($sql)) $there_is_child_cat = true;
			$hide_parent_category_section = !$there_is_child_cat;
		}
		if (!$hide_parent_category_section) {
		$special_filter = 'dd_resources_authors';
		$activetab = isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter]);
		?>
		<h2 class="dd_resources_feature-category-section-title">&nbsp;</h2>
		<h3 class="filter-title<?=$activetab ? ' activetab' : ''?>"><?=apply_filters('dd_resources_special_filter_authors_title', 'Authors')?></h3>
		<div class="filter-content" style="display:<?=$activetab ? 'block' : 'none'?>;">
			<ul class="no-style dd_resources_feature-alm-filter-nav">
				<?php
				if ($post_type_active_one == 'civicrm_events') {
					$sql = "SELECT u.ID,u.display_name FROM civicrm_participant p
					JOIN civicrm_event e ON e.id=p.event_id
					JOIN civicrm_option_value ov ON e.event_type_id=ov.value AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name='event_type' AND is_active=1)
					JOIN civicrm_contact c ON c.id=p.contact_id
					JOIN civicrm_uf_match ufm ON ufm.contact_id=p.contact_id
					JOIN wp_users u ON ufm.uf_id=u.ID
					GROUP BY u.ID ORDER BY u.display_name";
				} else {
					$sql = $wpdb->prepare("SELECT u.ID,u.display_name FROM wp_posts p
						JOIN wp_users u ON p.post_author=u.ID
						WHERE p.post_type=%s AND p.post_status='publish' GROUP BY u.ID ORDER BY u.display_name",$post_type_active_one);
				}
				$results = $wpdb->get_results($sql);
				if (($apply_hide_filter = $dd_resources_show_more_button_enabled)) {
					$i=0;
					foreach ($results as $wp_user) {
						$selected = isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter]) && in_array($wp_user->ID, $_REQUEST['term_id_'.$special_filter]);
						if ($selected && $i>4) {
							$apply_hide_filter = false;
							break 1;
						}
						$i++;
					}
				}
				$count_authors_categories = 0;
				foreach ($results as $wp_user) {
					if ($post_type_active_one == 'civicrm_events') {
						$sql = "SELECT e.id FROM civicrm_participant p
							JOIN civicrm_event e ON e.id=p.event_id
							JOIN civicrm_option_value ov ON e.event_type_id=ov.value AND ov.option_group_id=(SELECT id FROM civicrm_option_group WHERE name='event_type' AND is_active=1)
							JOIN civicrm_contact c ON c.id=p.contact_id
							JOIN civicrm_uf_match ufm ON ufm.contact_id=p.contact_id
							JOIN wp_users u ON ufm.uf_id=u.ID
							WHERE e.is_active=1 AND e.end_date>=NOW() AND u.ID=".$wp_user->ID;
						$result = $wpdb->get_col($sql);
						$count_posts = count($result);
						$extra_data_bind_attrs = ' data-post--in="'.implode(',',$result ? $result : array(-0.5)).'"';
					} else {
						wp_reset_postdata();
						$args = array('post_type' => $post_type_active_one, 'post_status'=>array('publish'), 'nopaging'=>true, 'suppress_filters'=>true);
						$result = $wpdb->get_col($wpdb->prepare("SELECT ID FROM wp_posts WHERE post_type=%s AND post_status='publish' AND post_author=%d",$post_type_active_one,$wp_user->ID));
						$args['post__in'] = $result ? $result : array(-0.5);
						$count_posts = count(get_posts($args));
						wp_reset_postdata();
						$extra_data_bind_attrs = ' data-post--in="'.implode(',',$args['post__in']).'"';
					}
					$selected = isset($_REQUEST['term_id_'.$special_filter]) && is_array($_REQUEST['term_id_'.$special_filter]) && in_array($wp_user->ID, $_REQUEST['term_id_'.$special_filter]);
					if ($show_categories_in_the_filter_with_no_posts || $count_posts>0) :
					?>
					<li class="<?=$count_authors_categories>4 && $apply_hide_filter ? 'hidefilter' : ''?>">
					<label class="cbox"><input type="checkbox" name="term_id_<?=$special_filter?>[]" id="term_id<?=$wp_user->ID;?>" value="<?=$wp_user->ID;?>"<?=$selected ? ' checked' : ''?><?=$extra_data_bind_attrs?> /><span class="style-checkbox"></span><div class="layer-label-wrapper"><?=$wp_user->display_name;?> <span class="style-count">(<?=$count_posts?>)</span></div></label>
				</li>
				<?php
						$count_authors_categories++;
					endif;
				}
				?>
			</ul>
			<?php if ($apply_hide_filter && $count_authors_categories>4) { ?>
			<div class="layer-more">
				<a class="link-show-more-filter" data-max="5">Show more</a>
			</div>
			<?php } ?>
		</div>
		<!-- End Special Filter Authors -->
		<?php }
		}
		if (!$dd_resources_special_filter_custom_filter_shown) {
			//Add additional filter specific for the client
			do_action('dd_resources_special_filter_custom_filter',$post_type_active_one,$dd_resources_feature_show_theparent_category_if_no_children,$show_categories_in_the_filter_with_no_posts,$dd_resources_show_more_button_enabled);
		}
	}
	private function getPostIdsOfCategory($post_type_active_one, $term_id) {
		global $wpdb;
		return $wpdb->get_col($wpdb->prepare('SELECT p.ID FROM wp_posts p
					JOIN wp_term_relationships tr ON p.ID=tr.object_id
					JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
					JOIN wp_terms t ON t.term_id=tt.term_id
					WHERE p.post_type=%s AND p.post_status=\'publish\' AND t.term_id=%d',$post_type_active_one,$term_id));
	}
	
	public static function enable_disable_feature() {
		if (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
			update_option('dd_resources_enabled',isset($_POST['dd_resources_enabled']) ? $_POST['dd_resources_enabled'] : 0);
			update_option('dd_resources_show_more_button_enabled',isset($_POST['dd_resources_show_more_button_enabled']) ? $_POST['dd_resources_show_more_button_enabled'] : 0);
			update_option('dd_resources_special_filter_date_published_enabled',isset($_POST['dd_resources_special_filter_date_published_enabled']) ? $_POST['dd_resources_special_filter_date_published_enabled'] : 0);
			update_option('dd_resources_special_filter_authors_enabled',isset($_POST['dd_resources_special_filter_authors_enabled']) ? $_POST['dd_resources_special_filter_authors_enabled'] : 0);
			update_option('dd_resources',isset($_POST['dd_resources']) ? $_POST['dd_resources'] : array());
		}
		$dd_resources_enabled = get_option('dd_resources_enabled');
		$dd_resources_show_more_button_enabled = get_option('dd_resources_show_more_button_enabled');
		$dd_resources_special_filter_date_published_enabled = get_option('dd_resources_special_filter_date_published_enabled');
		$dd_resources_special_filter_authors_enabled = get_option('dd_resources_special_filter_authors_enabled');
		$dd_resources = get_option('dd_resources',array());
		?>
		<div class="resources_wrap">
			<h2>Resources</h2>
			<div id="dd_resources">
				<label><strong>Are Resources enabled:</strong> <input type="checkbox" id="dd_resources_enabled" name="dd_resources_enabled" value="1"<?=!empty($dd_resources_enabled) ? ' checked' : ''?> /></label> <i>This will also register custom post type Resources</i>
			</div>
			<div id="dd_resources_show_more_button_enabled">
				<label><strong>Use "Show more" button in the filters:</strong> <input type="checkbox" id="dd_resources_show_more_button_enabled" name="dd_resources_show_more_button_enabled" value="1"<?=!empty($dd_resources_show_more_button_enabled) ? ' checked' : ''?> /></label> <i>If enabled only the 1st 5 filter options will be shown and there will be "Show more" button to expand the rest.</i>
			</div>
			<div id="dd_resources_special_filter_date_published_enabled">
				<label><strong>Special Filter "Date Published" enabled:</strong> <input type="checkbox" id="dd_resources_special_filter_date_published_enabled" name="dd_resources_special_filter_date_published_enabled" value="1"<?=!empty($dd_resources_special_filter_date_published_enabled) ? ' checked' : ''?> /></label>
			</div>
			<div id="dd_resources_special_filter_authors_enabled">
				<label><strong>Special Filter "Authors" enabled:</strong> <input type="checkbox" id="dd_resources_special_filter_authors_enabled" name="dd_resources_special_filter_authors_enabled" value="1"<?=!empty($dd_resources_special_filter_authors_enabled) ? ' checked' : ''?> /></label>
			</div>
			<div id="dd_resources_post_types" style="margin-top:1em;">
				<label><strong>Select what to be used as resources, it will be shown in the shortcode:</strong>
					<select name="dd_resources[]" multiple="multiple">
						<?php
						$resources_selected = false;
						$all_resources = self::get_all_resources();
						foreach ($all_resources as $post_type_name => $post_type_obj) {
							$selected = '';
							if (in_array($post_type_name, $dd_resources)) {
								$selected = ' selected';
								$resources_selected = true;
							}
							?>
							<option value="<?=$post_type_name?>"<?=$selected?>><?=$post_type_obj->labels->name?></option>
						<?php
						}
						?>
					</select>
				</label>
				<?php
				if ($resources_selected) { ?>
					Use Shortcode [dd_resources resources="<?=implode(',',$dd_resources)?>"]
				<?php
				}
				?>
			</div>
		</div>
		<hr style="margin-bottom:1em;"/>
	<?php
	}
	public static function get_all_resources() {
		$all_resources = get_post_types('', 'objects');
		$all_plugins = get_plugins();
		if (array_key_exists('civicrm/civicrm.php', $all_plugins)) {
			$all_resources['civicrm_events'] = new stdClass();
			$all_resources['civicrm_events']->labels = new stdClass();
			$all_resources['civicrm_events']->labels->name = 'CiviCRM Events';
		}
		return $all_resources;
	}
	
	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}