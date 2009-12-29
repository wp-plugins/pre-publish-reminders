<?php
/*
 Plugin Name: Pre-Publish Reminders
 Plugin URI: http://nickohrn.com/pre-publish-plugin
 Description: This plugin allows you to set reminders for actions that need to be taken before publishing a post.  Examples of actions include setting tags, settings categories, or adding a post signature.
 Author: Nick Ohrn
 Version: 5.0.0
 Author URI: http://plugin-developer.com/
 */

if( !class_exists( 'PrePublishReminders' ) ) {

	class PrePublishReminders {

		var $version = '5.0.0';
		var $postType = 'reminder';
		var $_meta_Checked = '_reminders-checked';
		var $_meta_TextColor = '_reminder-text-color';
		var $_meta_BackColor = '_reminder-back-color';
		var $_meta_Modifiers = '_reminder-modifiers';
		
		function PrePublishReminders() {
			$this->addActions();
			$this->addFilters();
		}

		function addActions() {
			add_action( 'admin_init', array( &$this, 'modifyReminders' ) );
			add_action( 'admin_menu', array( &$this, 'addAdministrativeMenuItems' ) );
			add_action( 'save_post', array( &$this, 'savePostReminders' ) );
			add_action( 'wp_ajax_sort_pre_publish_reminders', array( &$this, 'sortPrePublishReminders' ) );
		}

		function addFilters() {

		}

		/// CALLBACKS

		/**
		 * Add the Pre-Publish Reminders management page.  Also adds a meta box to the page and post
		 * interfaces.  Finally, enqueues necessary javascript files.
		 */
		function addAdministrativeMenuItems() {
			add_management_page( __( 'Publishing Reminders' ), __( 'Publishing Reminders' ), 'manage_options', 'pre-publish-reminders', array( &$this, 'displayManagementPage' ) );
			add_meta_box( 'pre-publish-reminders-meta-box', __( 'Pre-Publish Reminders' ), array( &$this, 'displayMetaBox' ), 'page', 'normal' );
			add_meta_box( 'pre-publish-reminders-meta-box', __( 'Pre-Publish Reminders' ), array( &$this, 'displayMetaBox' ), 'post', 'normal' );
			wp_enqueue_script( 'ppr-js', plugins_url('resources/pre-publish-reminders.js',__FILE__), array( 'jquery', 'jquery-ui-sortable' ) );
			
			global $pagenow;
			if( 'tools.php' == $pagenow && $_GET['page'] == 'pre-publish-reminders' ) {
				wp_enqueue_script( 'jquery-colorpicker', plugins_url('resources/colorpicker/js/colorpicker.js',__FILE__), array( 'jquery') );
				wp_enqueue_style( 'jquery-colorpicker', plugins_url('resources/colorpicker/css/colorpicker.css',__FILE__), array() );	
			}
		}

		function modifyReminders() {
			if( ( isset( $_POST[ 'delete-reminders' ] ) && check_admin_referer( 'delete-reminders' ) ) || ( isset( $_POST['delete-reminders-2'] ) && check_admin_referer( 'delete-reminders' )) ) {
				$count = 0;
				foreach( (array)$_POST['reminders-to-delete'] as $reminder ) {
					$reminderPost = get_post($reminder);
					if( $reminderPost->post_type == $this->postType ) {
						wp_delete_post($reminder);
						$count++;
					}
				}
				wp_redirect(admin_url( 'tools.php?page=pre-publish-reminders&deleted=' . $count));
				exit();
			}

			if( isset( $_POST['save-reminder'] ) && check_admin_referer( 'save-reminder' ) ) {
				$text = empty($_POST['reminder-text']) ? __( 'Intentionally Blank' ) : wp_kses_data(stripslashes($_POST['reminder-text']));
				$textColor = preg_match('/^[0-9a-fA-F]{6}$/', stripslashes($_POST['reminder-foreground'])) ? stripslashes($_POST['reminder-foreground']) : '000000';
				$backColor = preg_match('/^[0-9a-fA-F]{6}$/', stripslashes($_POST['reminder-background'])) ? stripslashes($_POST['reminder-background']) : 'ffffff';
				$strong = $_POST['reminder-modifiers']['strong'] == 1;
				$emphasized = $_POST['reminder-modifiers']['emphasized'] == 1;
				$underlined = $_POST['reminder-modifiers']['underlined'] == 1;
				
				global $wpdb;
				$maxMenuOrder = $wpdb->get_var( $wpdb->prepare( "SELECT (MAX(menu_order) + 1) FROM {$wpdb->posts} WHERE post_type = %s", $this->postType ) );
				
				$post = array('post_content'=>$text, 'post_type'=>$this->postType, 'post_status' => 'publish', 'menu_order' => $maxMenuOrder);
				if( isset( $_POST['reminder-id'] ) ) {
					$post['ID'] = absint($_POST['reminder-id']);
				}
				
				$postId = wp_insert_post($post);
				if( !is_wp_error($postId) ) {
					update_post_meta($postId, $this->_meta_TextColor, $textColor);
					update_post_meta($postId, $this->_meta_BackColor, $backColor);
					update_post_meta($postId, $this->_meta_Modifiers, array('strong'=>$strong,'emphasized'=>$emphasized,'underlined'=>$underlined));
				}
				
				wp_redirect(admin_url('tools.php?page=pre-publish-reminders&updated=1'));
				exit();
			}
		}

		function savePostReminders($postId) {
			if( false === wp_is_post_autosave($postId) && false === wp_is_post_revision($postId) ) {
				$checkedReminders = (array)$_POST['pre-publish-reminders'];
				update_post_meta($postId, $this->_meta_Checked, $checkedReminders);
			}
		}
		
		function sortPrePublishReminders() {
			$reminders = wp_parse_args($_POST['reminders']);
			$reminders = $reminders['reminder'];
			global $wpdb;
			for( $i = 0; $i < count( $reminders ); $i++ ) {
				$wpdb->query($wpdb->prepare( "UPDATE {$wpdb->posts} SET menu_order = %d WHERE ID = %d", $i, $reminders[$i]));
			}
			exit();
		}

		/// DISPLAY

		function displayManagementPage() {
			include('views/manage.php');
		}

		function displayMetaBox() {
			include('views/meta-box.php');
		}
		
		/// UTILITY
		
		function getAllReminders() {
			$reminders = get_posts(array( 'numberposts' => 0, 'post_type' => $this->postType, 'orderby' => 'menu_order', 'order' => 'ASC'));
			foreach($reminders as $reminder) {
				$reminder->textColor = get_post_meta($reminder->ID,$this->_meta_TextColor,true);
				$reminder->backColor = get_post_meta($reminder->ID,$this->_meta_BackColor,true);
				$modifiers = get_post_meta($reminder->ID,$this->_meta_Modifiers,true);
				$reminder->strong = $modifiers['strong'] == 1;
				$reminder->emphasized = $modifiers['emphasized'] == 1;
				$reminder->underlined = $modifiers['underlined'] == 1;
			}
			return $reminders;
		}
		
		function getCheckedRemindersForPost($postId) {
			$postId = absint($postId);
			if( $postId > 0 ) {
				return (array)get_post_meta($postId,$this->_meta_Checked,true);
			} else {
				return array();
			}
		}
		
		function yesNo($value) {
			if( $value ) {
				return __( 'Yes' );
			} else {
				return __( 'No' );
			}
		}
	}
	
	$prePublishReminders = new PrePublishReminders;
}
