<?php
/*
Plugin Name: Pre-Publish Reminder List
Plugin URI: http://nickohrn.com/pre-publish-plugin
Description: This nifty little plugin will allow you to setup reminders of actions you need to take prior to pressing the Publish button on your posts.  The list is customizable via an administration panel that you can find under the manage tab.
Author: Nick Ohrn
Version: 1.0.7
Author URI: http://nickohrn.com/
*/ 

/*  Copyright 2006  Nick Ohrn  (email : nickohrn@ohrnventures.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/////////////////////////////////////////////
//Current errors:
/////////////////////////////////////////////
/////////////////////////////////////////////



/**
 * This class contains all the methods necessary to make the plugin run
 * correctly.  It is a wrapper for all functionality necessary to provide
 * Pre-Publish Reminders.
 */
class NFO_Pre_Publish_Reminders {
	static $version = '1.06';

	/**
	 * Install this plugin by creating a version options and creating
	 * a new table in the database to handle reminder information.
	 *
	 * @return null
	 */
	function install() {
		global $wpdb;
		if( self::$version != get_option( 'nfoppr_ver' ) ) {
			if( $wpdb->get_var( "show tables like '" . $wpdb->prefix . 'pre_publish_reminders' . "'" ) != $wpdb->prefix . 'pre_publish_reminders' ) {
				$query = "CREATE TABLE " . $wpdb->prefix . 'pre_publish_reminders' . " (
					Reminder_ID INT(9) NOT NULL AUTO_INCREMENT,
					Reminder_Text VARCHAR(255) NOT NULL,
					Reminder_Background_Color VARCHAR(6) DEFAULT 'FFFFFF' NOT NULL,
					Reminder_Text_Color VARCHAR(6) DEFAULT '000000' NOT NULL,
					Reminder_Order INT(9) NOT NULL,
					Is_Bold BOOL NOT NULL,
					Is_Italic BOOL NOT NULL,
					PRIMARY KEY (Reminder_ID));";
				require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
				dbDelta($query);
				update_option( 'nfoppr_ver', self::$version, 'The version number of the Pre-Publish Reminder List Plugin' );
			}
		}
	}
	
	/**
	 * Delete the reminders table, all data contained within, and remove the option
	 * specifying the plugin version number.
	 *
	 * @return null
	 */
	function uninstall() {
		global $wpdb;
		if( $wpdb->get_var( "show tables like '" . $wpdb->prefix . 'pre_publish_reminders' . "'" ) == $wpdb->prefix . 'pre_publish_reminders' ) {
			$delete = "DROP TABLE " . $wpdb->prefix . 'pre_publish_reminders';
			$wpdb->query( $delete );
			delete_option( 'nfoppr_ver' );
		}
	}
	
	/**
	 * Add the administration page to the manage menu and register the javascripts
	 * needed.
	 *
	 * @return null
	 */
	function add_admin_page() {
		add_management_page('Pre-Publish Reminders', 'Pre-Publish Reminders', 8, basename(__FILE__), array( 'NFO_Pre_Publish_Reminders', 'manage_page' ) );
	}	
	
	/**
	 * Add the javascript libraries that are necessary for DHTML and AJAX actions.
	 */
	public function add_js_libs() {
		wp_enqueue_script( 'prototype' );
		wp_enqueue_script( 'scriptaculous' );
		wp_enqueue_script( 'colorpicker' );
	}
	
	/**
	 * Generates the output for the management page for the pre publish reminders.
	 * 
	 * @return null
	 */
	public function manage_page() {
		global $wpdb;
		echo '<div class="wrap">';
		// Process all the possible input on the page.
		if( isset( $_POST['submitted'] ) ) {
			self::process_post_form_variables( $_POST );
		} elseif( isset( $_GET['reminder_action'] ) && isset ( $_GET['id'] ) ) {
			$current = self::process_get_variables( $_GET );
			$editing = $current['editing'];
		}
		self::output_admin_table(); ?>
		<script type="text/javascript">
		var cp = new ColorPicker('window');
		</script>
				
		<form name="reminder" id="reminder" method="post">
		<fieldset id="reminder_text_fieldset">
			<legend>Reminder Text</legend>
			<div><input type="text" name="reminder_text" size="50" tabindex="1" value="<?php if( $editing ) { echo htmlentities( stripslashes( $current['reminder_text'] ) ); } else { echo ''; } ?>" id="reminder_text" /></div>
		</fieldset>
		<fieldset id="reminder_color_fieldset">
			<legend>Background Color</legend>
			<div><input type="text" name="background_color" size="6" tabindex="2" value="#<?php if( $editing ) { echo $current['back_color']; } else { echo 'ffffff'; } ?>" id="background_color" /><br />
			<small><a href="#" name="background_color_select" id="background_color_select" onclick="cp.select(document.forms[0].background_color, 'background_color_select');return false;">Select Text Color</a>
			<br />Defaults to white, but you can fill in your own hex color code of length 6 (don't forget the leading #)</small></div>
		</fieldset>
		<fieldset id="text_color_fieldset">
			<legend>Text Color</legend>
			<div><input type="text" name="text_color" size="6" tabindex="3" value="#<?php if( $editing ) { echo $current['text_color']; } else { echo '000000'; } ?>" id="text_color" /><br />
			<small><a href="#" name="text_color_select" id="text_color_select" onclick="cp.select(document.forms[0].text_color, 'text_color_select');return false;">Select Text Color</a>
			<br />Defaults to black, but you can fill in your own hex color code of length 6 (don't forget the leading #)</small></div>
		</fieldset>
		<fieldset>
			<legend>Text Formatting</legend>
			<ul id="formatting_options">
				<li><input type="checkbox" tabindex="4" name="formatting[]" value="bold" <?php if( $current['is_bold'] ) { echo 'checked="checked"'; } ?> /><label>Bold</label></li>
				<li><input type="checkbox" tabinder="5" name="formatting[]" value="italic" <?php if( $current['is_italic'] ) { echo 'checked="checked"'; } ?> /><label>Italic</label></li>
			</ul>
		</fieldset>
		<p class="submit">
			<?php if( $editing ) { ?>
			<input type="hidden" name="order" value="<?php echo $current['order']; ?>" id="hidden_reminder_order" />
			<input type="hidden" name="id" value="<?php echo $current['id']; ?>" id="hidden_reminder_id" />
			<?php } ?>
			<input type="hidden" name="submitted" value="<?php if( $editing ) { echo 'edit'; } else { echo 'add'; } ?>" id="hidden_submitted_check" />
			<input type="submit" name="submit" tabindex="6" value="Save Pre-Publish Reminder" id="ppr_submit" />
		</p>
		</form>
		</div>
		<?php
	}
	
	/**
	 * Process the form variables submitted to the page.
	 * 
	 * @param Array, an array of post variables.
	 */
	function process_post_form_variables( $form_array ) {
		global $wpdb;
		if( $form_array['reminder_text'] == '' ) {
			echo '<h3>You have to set some reminder text!</h3>';
		} elseif( !preg_match('/^#[a-fA-F0-9]{6}$/', $form_array['background_color'] ) )  {
			echo '<h3>Your background color is invalid.  Remember, it has to be a 6 character hex color code!</h3>';
		} elseif( !preg_match('/^#[a-fA-F0-9]{6}$/', $form_array['text_color'] ) ) {
			echo '<h3>Your text color is invalid.  Remember, it has to be a 6 character hex color code!</h3>';
		} else {
			//Initialize formatting variables.			
			$bold = 0;
			$italic = 0;
			if ( is_array( $form_array['formatting'] ) ) {
				foreach ( $form_array['formatting'] as $format_option ) {
					switch($format_option) {
						case "bold":
							$bold = 1;
							break;
						case "italic":
							$italic = 1;
							break;
					}
				}
			} 
			$reminder_text = $wpdb->escape( $form_array['reminder_text'] );
			$back_color = $wpdb->escape( substr( $form_array['background_color'], 1 ) );
			$text_color = $wpdb->escape( substr( $form_array['text_color'], 1 ) );
			$order = $wpdb->escape( $form_array['order'] );
			
			if( 'add' == $form_array['submitted'] ) {
				$get_num_reminders = "(SELECT COUNT( Reminder_Order ) AS num_reminders FROM " . $wpdb->prefix . "pre_publish_reminders)";
				$num_reminders = $wpdb->get_results( $get_num_reminders, ARRAY_A );
				$num = $num_reminders[0]['num_reminders'];
				$query = "INSERT INTO " . $wpdb->prefix . 'pre_publish_reminders' . " ( Reminder_Text , Reminder_Background_Color , Reminder_Text_Color, Reminder_Order, Is_Bold, Is_Italic ) VALUES ( '$reminder_text', '$back_color', '$text_color', ($num + 1), $bold, $italic )";
				$action = 'added';
			} elseif( 'edit' == $form_array['submitted'] ) {
				$reminder_id = intval( $form_array['id'] );
				$query = "UPDATE " . $wpdb->prefix . 'pre_publish_reminders' . " SET Reminder_Text = '$reminder_text', Reminder_Background_Color = '$back_color', Reminder_Text_Color = '$text_color', Reminder_Order = $order, Is_Bold = $bold, Is_Italic = $italic WHERE Reminder_ID = $reminder_id";
				$action = 'edited';
			}
			$result = $wpdb->query( $query );
			
			if( false !== $result ) {
				echo "<h3>You successfully $action your reminder!</h3>";
			} else {
				echo '<h3>Your reminder was not added to the database.  There was an unfortunate error.</h3>';
			}
		}
	}
	
	/**
	 * Process the variables contained in the $_GET array.  Specifically, add or delete a reminder.
	 */
	function process_get_variables( $array ) {
		global $wpdb;
		$id = intval( $wpdb->escape( $array['id'] ) );
		if( 'delete_reminder' == $array['reminder_action'] ) {
		
			//Do the order updating.  For everything with a sort order greater than this reminder, decrease the sort order.
			$order = "SELECT Reminder_Order FROM " . $wpdb->prefix . "pre_publish_reminders WHERE Reminder_ID = $id";
			$select_order= $wpdb->get_results( $order, ARRAY_A );
			if($select_order[0]) {
				$current_order = $select_order[0]['Reminder_Order'];			
				$redo_reminder_order = "UPDATE " . $wpdb->prefix . "pre_publish_reminders SET Reminder_Order = Reminder_Order - 1 WHERE Reminder_Order > $current_order";
				$wpdb->query( $redo_reminder_order );
				
				//Delete the reminder from the reminder table
				$delete = "DELETE FROM " . $wpdb->prefix . 'pre_publish_reminders' . " WHERE Reminder_ID = $id";
				$wpdb->query( $delete );
				echo '<h3>Deletion successful.</h3>';
			} else {
				echo '<h3>Deletion could not occur because that reminder was not found.</h3>';
			}
		} elseif ( 'edit_reminder' == $array['reminder_action'] ) {
			$select = "SELECT * FROM " . $wpdb->prefix . 'pre_publish_reminders' . " WHERE Reminder_ID = $id";
			$result = $wpdb->get_results( $select, ARRAY_A );
			if( $result[0] ) {
				$return_array = array();
				$return_array['editing'] = true;
				$return_array['text_color'] = $result[0]['Reminder_Text_Color'];
				$return_array['back_color'] = $result[0]['Reminder_Background_Color'];
				$return_array['reminder_text'] = $result[0]['Reminder_Text'];
				$return_array['is_bold'] = $result[0]['Is_Bold'];
				$return_array['is_italic'] = $result[0]['Is_Italic'];
				$return_array['order'] = $result[0]['Reminder_Order'];
				$return_array['id'] = $result[0]['Reminder_ID'];
				return $return_array;
				echo '<h3>Now editing...</h3>';
			} else {
				echo '<h3>Sorry, but there is no reminder with that ID number available for editing.</h3>';
			}
		}
	}
	
	
	
	/**
	 * Print out a table of all reminders currently in the reminder database.
	 *
	 */
	function output_admin_table() {
		global $wpdb;
		$query = "SELECT * FROM " . $wpdb->prefix . 'pre_publish_reminders' . " ORDER BY Reminder_Order ASC";
		$reminders = $wpdb->get_results( $query , ARRAY_A);
		if( count( $reminders ) > 0 ) {
			echo '<table class="widefat" id="reminder_table"><thead>';
			echo '<tr><th scope="col">Sort Order</th><th scope="col">Reminder Text</th><th scope="col">Text Color</th><th>Color Sample</th><th scope="col">Background Color</th><th>Color Sample</th><th scope="col">Bold</th><th scope="col">Italic</th><th scope="col"></th><th scope="col"></th>';
			echo '</tr></thead><tbody id="the-reminders">';
			$class = '';
			foreach( $reminders as $reminder ) {
				if( $reminder['Is_Bold'] ) { $bold = '<strong>Yes</strong>'; } else { $bold = 'No'; }
				if( $reminder['Is_Italic'] ) { $italic = '<em>Yes</strong>'; } else { $italic = 'No'; }
				echo '<tr class="' . $class . '" id="order_' . $reminder['Reminder_Order'] . '">';
				echo '<th scope="row">' . $reminder['Reminder_Order'] . '</td>';
				echo '<td>' . stripslashes( $reminder['Reminder_Text'] ) . '</td>';
				echo '<td>#' . $reminder['Reminder_Text_Color'] . '</td><td class="color_identifier" style="background-color: #' . $reminder['Reminder_Text_Color'] . ';"></td>';
				echo '<td>#' . $reminder['Reminder_Background_Color'] . '</td><td class="color_identifier" style="background-color: #' . $reminder['Reminder_Background_Color'] . ';"></td>';
				echo '<td>' . $bold . '</td><td>' . $italic . '</td>';
				echo '<td><a class="edit" href="' . basename( $_SERVER['PHP_SELF'] ) . '?page=' . basename( __FILE__ ) . '&amp;reminder_action=edit_reminder&amp;id=' . $reminder['Reminder_ID'] . '">Edit</a></td>';
				echo '<td><a onclick="javascript: return confirm(\'Are you sure you wish to delete the reminder\n' . addslashes( htmlentities( stripslashes( $reminder['Reminder_Text'] ) ) ) . '\');" class="delete" href="' . basename( $_SERVER['PHP_SELF'] ) . '?page=' . basename( __FILE__ ) . '&amp;reminder_action=delete_reminder&amp;id=' . $reminder['Reminder_ID'] . '">Delete</a></td>';
				echo '</tr>';
				if( 'alternate' == $class ) {
					$class = '';
				} else {
					$class = 'alternate';
				}
			}
			echo '</tbody>';
			echo '</table>';
		}
	}
	
	/**
	 * Outputs the current reminders as an ordered list.
	 */
	function output_reminder_list() {
		global $wpdb;
		$query = "SELECT Reminder_Text, Reminder_Background_Color, Reminder_Text_Color, Reminder_Order, Is_Bold, Is_Italic FROM " . $wpdb->prefix . 'pre_publish_reminders ORDER BY Reminder_Order';
		$reminders = $wpdb->get_results( $query, ARRAY_A );
		if( count( $reminders ) > 0 ) {
			echo '<div id="reminder_div"><strong>Did you remember to do all these things?</strong><ol id="reminder_list">';
			foreach( $reminders as $reminder ) {
				$this_reminder = stripslashes( $reminder['Reminder_Text'] );
				if( $reminder['Is_Bold'] ) {
					$this_reminder = '<strong>' . $this_reminder . '</strong>';
				}
				if( $reminder['Is_Italic'] ) {
					$this_reminder = '<em>' . $this_reminder . '</em>';
				}
				echo '<li style="color:#' . $reminder['Reminder_Text_Color'] . '; background-color:#' . $reminder['Reminder_Background_Color'] . ';">' . $this_reminder . '</li>';
			}
			echo '</ol></div>';		
		}
	}
	
	
	
	/**
	 * Add some style declarations to the head of the page for the reminder input 
	 * fields.  Just used to space stuff out, remove some bullets, and then add a border.
	 *
	 * @return null
	 */
	function add_header_stuff() {
		?>
		<style type="text/css">
		#reminder_text_fieldset, #reminder_color_fieldset, #text_color_fieldset {margin-bottom: 8px; }
		#reminder_list { padding-left: 0; } 
		#reminder_list li { list-style-position: inside; padding: 2px; }
		#reminder_div { margin: 8px 0; padding: 4px; border: 1px solid #666; }
		#text_color, #background_color {margin-bottom: 2px; }
		#formatting_options { list-style-type: none; padding: 0; }
		#formatting_options li { margin: 0 0 4px 0; }
		#formatting_options input { margin: 0 10px 0 0; }
		td.color_identifier { width: 3em; }
		</style>
		<?php
	}	
} //end class

/**
 * Insert action hooks here
 */
	add_action( 'activate_nfoppreminder.php', array( 'NFO_Pre_Publish_Reminders', 'install' ) );
	add_action( 'deactivate_nfoppreminder.php', array( 'NFO_Pre_Publish_Reminders', 'uninstall' ) );
	add_action( 'admin_menu', array( 'NFO_Pre_Publish_Reminders', 'add_admin_page' ) );
	//JS libraries not needed yet because DHTML and AJAX functionality not added for version 1.05
	add_action('admin_menu', array('NFO_Pre_Publish_Reminders', 'add_js_libs'));
	add_action('edit_form_advanced', array( 'NFO_Pre_Publish_Reminders', 'output_reminder_list' ) );
	add_action('admin_head', array( 'NFO_Pre_Publish_Reminders', 'add_header_stuff' ) );
?>