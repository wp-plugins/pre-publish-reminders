<?php
/*
Plugin Name: Pre-Publish Reminder List
Plugin URI: http://nickohrn.com/pre-publish-plugin
Description: This nifty little plugin will allow you to setup reminders of actions you need to take prior to pressing the Publish button on your posts.  The list is customizable via an administration panel that you can find under the manage tab.
Author: Nick Ohrn
Version: 2.0.0
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

/* Known Errors

*/


if(!class_exists('NFO_PPR')) {

	/**
	 * This class contains all the methods necessary to make the plugin run
	 * correctly.  It is a wrapper for all functionality necessary to provide
	 * Pre-Publish Reminders.
	 */
	class NFO_PPR {
		static $version = '2.0.0';
		static $versionOptionName = 'NFO_PPR_Version';
		static $tableName = 'NFO_PPR';

		/**
		 * Installs this plugin by first uninstalling any pre-2.0 version and then
		 * creating the new table for the plugin and registering a version number
		 * as a WordPress option.
		 *
		 * @return null
		 */
		function install() {
			global $wpdb;

			// If a previous version was installed, go ahead and uninstall it.
			if( get_option( 'nfoppr_ver' ) ) {
				uninstallOld();
			}

			// If the version isn't the same as it was in the past, upgrade the table.
			if( NFO_PPR::$version != get_option( NFO_PPR::$versionOptionName ) ) {
				if( $wpdb->get_var( "show tables like '" . $wpdb->prefix . NFO_PPR::$tableName . "'" ) != $wpdb->prefix . NFO_PPR::$tableName ) {
					$query = "CREATE TABLE " . $wpdb->prefix . NFO_PPR::$tableName . " (
						id INT(9) NOT NULL AUTO_INCREMENT,
						reminder TEXT NOT NULL,
						back_color VARCHAR(6) DEFAULT 'FFFFFF' NOT NULL,
						text_color VARCHAR(6) DEFAULT '000000' NOT NULL,
						sort INT(9) NOT NULL,
						is_bold BOOL NOT NULL,
						is_italic BOOL NOT NULL,
						PRIMARY KEY (id))";
					require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
					dbDelta($query);
					update_option( NFO_PPR::$versionOptionName, NFO_PPR::$version, 'The version number of the Pre-Publish Reminder List plugin written by Nick Ohrn' );
				}
			}
		}

		/**
		 * Delete the old reminders table, all data contained within, and remove
		 * the option specifying the plugin version number.
		 *
		 * @return null
		 */
		function uninstallOld() {
			global $wpdb;
			if( $wpdb->get_var( "show tables like '" . $wpdb->prefix . 'pre_publish_reminders' . "'" ) == $wpdb->prefix . 'pre_publish_reminders' ) {
				$delete = "DROP TABLE " . $wpdb->prefix . 'pre_publish_reminders';
				$wpdb->query( $delete );
				delete_option( NFO_PPR::$versionOptionName );
			}
		}

		/**
		 * Delete the reminders table, all data contained within, and remove
		 * the option specifying the plugin version number.
		 *
		 * @return null
		 */
		function uninstall() {
			global $wpdb;
			if( $wpdb->get_var( "show tables like '" . $wpdb->prefix . NFO_PPR::$tableName . "'" ) == $wpdb->prefix . NFO_PPR::$tableName ) {
				$delete = "DROP TABLE " . $wpdb->prefix . NFO_PPR::$tableName;
				$wpdb->query( $delete );
				delete_option( NFO_PPR::$versionOptionName );
			}
		}

		/**
		 * Add the administration page to the manage menu.
		 *
		 * @return null
		 */
		function add_admin_page() {
			add_management_page('Pre-Publish Reminders', 'Pre-Publish Reminders', 8, basename(__FILE__), array( 'NFO_PPR', 'manage_page' ) );
		}

		/**
		 * Generates the output for the management page for the pre publish reminders.
		 *
		 * @return null
		 */
		public function manage_page() {
			global $wpdb;
			// Process all the possible input on the page.
			if( isset( $_POST['submitted'] ) ) {
				NFO_PPR::process_post_form_variables( $_POST );
			} elseif( isset( $_GET['reminder_action'] ) && isset ( $_GET['id'] ) ) {
				$current = NFO_PPR::process_get_variables( $_GET );
				$editing = $current['editing'];
			}
			echo '<div class="wrap">';
			NFO_PPR::output_admin_table(); ?>

			<form name="reminder" id="reminder" method="post">
			<fieldset id="reminder_text_fieldset">
				<legend>Reminder Text</legend>
				<div><input type="text" name="reminder" size="50" tabindex="1" value="<?php if( $editing ) { echo htmlentities( stripslashes( $current['reminder'] ) ); } else { echo ''; } ?>" id="reminder_text" /></div>
			</fieldset>
			<fieldset id="reminder_color_fieldset">
				<legend>Background Color</legend>
				<div><input type="text" name="background_color" size="6" tabindex="2" value="#<?php if( $editing ) { echo $current['back_color']; } else { echo 'ffffff'; } ?>" id="background_color" /><br />
				<br />Defaults to white, but you can fill in your own hex color code of length 6 (don't forget the leading #)</small></div>
			</fieldset>
			<fieldset id="text_color_fieldset">
				<legend>Text Color</legend>
				<div><input type="text" name="text_color" size="6" tabindex="3" value="#<?php if( $editing ) { echo $current['text_color']; } else { echo '000000'; } ?>" id="text_color" /><br />
				<br />Defaults to black, but you can fill in your own hex color code of length 6 (don't forget the leading #)</small></div>
			</fieldset>
			<fieldset>
				<legend>Text Formatting</legend>
				<ul id="formatting_options">
					<li><input type="checkbox" tabindex="4" name="formatting[]" id="format_bold" value="bold" <?php if( $current['is_bold'] ) { echo 'checked="checked"'; } ?> /><label for="format_bold">Bold</label></li>
					<li><input type="checkbox" tabinder="5" name="formatting[]" id="format_italic" value="italic" <?php if( $current['is_italic'] ) { echo 'checked="checked"'; } ?> /><label for="format_italic">Italic</label></li>
				</ul>
			</fieldset>
			<p class="submit">
				<?php if( $editing ) { ?>
				<input type="hidden" name="sort" value="<?php echo $current['sort']; ?>" id="hidden_reminder_order" />
				<input type="hidden" name="id" value="<?php echo $current['id']; ?>" id="hidden_reminder_id" />
				<?php } ?>
				<input type="hidden" name="submitted" value="<?php if( $editing ) { echo 'edit'; } else { echo 'add'; } ?>" id="hidden_submitted_check" />
				<input type="submit" name="submit" tabindex="6" value="Save Reminder" id="ppr_submit" />
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
			if( $form_array['reminder'] == '' ) {
				echo '<div id="message" class="error fade"><p>You have to set some reminder text!</p></div>';
			} elseif( !preg_match('/^#[a-fA-F0-9]{6}$/', $form_array['background_color'] ) )  {
				echo '<div id="message" class="error fade"><p>Your background color is invalid.  Remember, it has to be a 6 character hex color code!</p></div>';
			} elseif( !preg_match('/^#[a-fA-F0-9]{6}$/', $form_array['text_color'] ) ) {
				echo '<div id="message" class="error fade"><p>Your text color is invalid.  Remember, it has to be a 6 character hex color code!</p></div>';
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
				$reminder_text = $wpdb->escape( $form_array['reminder'] );
				$back_color = $wpdb->escape( substr( $form_array['background_color'], 1 ) );
				$text_color = $wpdb->escape( substr( $form_array['text_color'], 1 ) );
				$order = $wpdb->escape( $form_array['sort'] );

				if( 'add' == $form_array['submitted'] ) {
					$get_num_reminders = "(SELECT COUNT( sort ) AS num_reminders FROM " . $wpdb->prefix . "NFO_PPR)";
					$num_reminders = $wpdb->get_results( $get_num_reminders, ARRAY_A );
					$num = $num_reminders[0]['num_reminders'];
					$query = "INSERT INTO " . $wpdb->prefix . NFO_PPR::$tableName . " ( reminder , back_color, text_color, sort, is_bold, is_italic ) VALUES ( '$reminder_text', '$back_color', '$text_color', ($num + 1), $bold, $italic )";
					$action = 'added';
				} elseif( 'edit' == $form_array['submitted'] ) {
					$reminder_id = intval( $form_array['id'] );
					$query = "UPDATE " . $wpdb->prefix . NFO_PPR::$tableName . " SET reminder = '$reminder_text', back_color = '$back_color', text_color = '$text_color', sort = $order, is_bold = $bold, is_italic = $italic WHERE id = $reminder_id";
					$action = 'edited';
				}
				$result = $wpdb->query( $query );

				if( false !== $result ) {
					echo '<div id="message" class="updated fade"><p>You successfully ' . $action . ' your reminder!</p></div>';
				} else {
					echo '<div id="message" class="error fade"><p>Your reminder was not added to the database.  There was an unfortunate error.</p></div>';
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
				$order = "SELECT sort FROM " . $wpdb->prefix . "NFO_PPR WHERE id = $id";
				$select_order = $wpdb->get_results( $order, ARRAY_A );
				if($select_order[0]) {
					$current_order = $select_order[0]['sort'];
					$redo_reminder_order = "UPDATE " . $wpdb->prefix . "NFO_PPR SET sort = sort - 1 WHERE sort > $current_order";
					$wpdb->query( $redo_reminder_order );

					//Delete the reminder from the reminder table
					$delete = "DELETE FROM " . $wpdb->prefix . NFO_PPR::$tableName . " WHERE id = $id";
					$wpdb->query( $delete );
					echo '<div id="message" class="updated fade"><p>Deletion successful.</p></div>';
				} else {
					echo '<div id="message" class="error fade"><p>Deletion could not occur because that reminder was not found.</p></div>';
				}
			} elseif ( 'edit_reminder' == $array['reminder_action'] ) {
				$select = "SELECT * FROM " . $wpdb->prefix . NFO_PPR::$tableName . " WHERE id = $id";
				$result = $wpdb->get_results( $select, ARRAY_A );
				if( $result[0] ) {
					$return_array = array();
					$return_array['editing'] = true;
					$return_array['text_color'] = $result[0]['text_color'];
					$return_array['back_color'] = $result[0]['back_color'];
					$return_array['reminder'] = $result[0]['reminder'];
					$return_array['is_bold'] = $result[0]['is_bold'];
					$return_array['is_italic'] = $result[0]['is_italic'];
					$return_array['sort'] = $result[0]['sort'];
					$return_array['id'] = $result[0]['id'];
					return $return_array;
					echo '<div id="message" class="updated fade"><p>Now editing...</p></div>';
				} else {
					echo '<div id="message" class="error fade"><p>Sorry, but there is no reminder with that ID number available for editing.</p></div>';
				}
				
			}
		}



		/**
		 * Print out a table of all reminders currently in the reminder database.
		 *
		 */
		function output_admin_table() {
			global $wpdb;
			$query = "SELECT * FROM " . $wpdb->prefix . NFO_PPR::$tableName . " ORDER BY sort ASC";
			$reminders = $wpdb->get_results( $query , ARRAY_A);
			if( count( $reminders ) > 0 ) {
				echo '<table class="widefat" id="reminder_table"><thead>';
				echo '<tr><th scope="col">Sort Order</th><th scope="col">Reminder Text</th><th scope="col">Text Color</th><th>Color Sample</th><th scope="col">Background Color</th><th>Color Sample</th><th scope="col">Bold</th><th scope="col">Italic</th><th scope="col"></th><th scope="col"></th>';
				echo '</tr></thead><tbody id="the-reminders">';
				$class = '';
				foreach( $reminders as $reminder ) {
					if( $reminder['is_bold'] ) { $bold = '<strong>Yes</strong>'; } else { $bold = 'No'; }
					if( $reminder['is_italic'] ) { $italic = '<em>Yes</strong>'; } else { $italic = 'No'; }
					echo '<tr class="' . $class . '" id="order_' . $reminder['Reminder_Order'] . '">';
					echo '<th scope="row">' . $reminder['sort'] . '</td>';
					echo '<td>' . stripslashes( $reminder['reminder'] ) . '</td>';
					echo '<td>#' . $reminder['text_color'] . '</td><td class="color_identifier" style="background-color: #' . $reminder['text_color'] . ';"></td>';
					echo '<td>#' . $reminder['back_color'] . '</td><td class="color_identifier" style="background-color: #' . $reminder['back_color'] . ';"></td>';
					echo '<td>' . $bold . '</td><td>' . $italic . '</td>';
					echo '<td><a class="edit" href="' . basename( $_SERVER['PHP_SELF'] ) . '?page=' . basename( __FILE__ ) . '&amp;reminder_action=edit_reminder&amp;id=' . $reminder['id'] . '">Edit</a></td>';
					echo '<td><a onclick="javascript: return confirm(\'Are you sure you wish to delete the reminder\n' . addslashes( htmlentities( stripslashes( $reminder['reminder'] ) ) ) . '\');" class="delete" href="' . basename( $_SERVER['PHP_SELF'] ) . '?page=' . basename( __FILE__ ) . '&amp;reminder_action=delete_reminder&amp;id=' . $reminder['id'] . '">Delete</a></td>';
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
			$query = "SELECT id, reminder, back_color, text_color, sort, is_bold, is_italic FROM " . $wpdb->prefix . NFO_PPR::$tableName . ' ORDER BY sort';
			$reminders = $wpdb->get_results( $query, ARRAY_A );
			if( count( $reminders ) > 0 ) {
				echo '<div id="reminder_div"><strong>Did you remember to do all these things?</strong><form><ol id="reminder_list">';
				foreach( $reminders as $reminder ) {
					$this_reminder = stripslashes( $reminder['reminder'] );
					if( $reminder['is_bold'] ) {
						$this_reminder = '<strong>' . $this_reminder . '</strong>';
					}
					if( $reminder['is_italic'] ) {
						$this_reminder = '<em>' . $this_reminder . '</em>';
					}
					echo '<li id="reminder_' . $reminder['id'] . '" style="color:#' . $reminder['text_color'] . '; background-color:#' . $reminder['back_color'] . ';"><input type="checkbox" id="reminder_' . $reminder['id'] . '_checkbox" name="is_completed" value="' . $reminder['id'] . '" onclick="DimReminder(' . $reminder['id'] . ')" /> ' . $this_reminder . '</li>';
				}
				echo '</ol></form></div>';
			}
		}



		/**
		 * Add some style declarations to the head of the page for the reminder input
		 * fields.  Just used to space stuff out, remove some bullets, and then add a border.
		 * Also, add the DimReminder javascript function.
		 *
		 * @return null
		 */
		function add_header_stuff() {
			$output = '<style type="text/css">#reminder_text_fieldset, #reminder_color_fieldset, #text_color_fieldset {margin-bottom: 8px; }#reminder_list { padding-left: 0; }	#reminder_list li { list-style-position: inside; padding: 2px; }#reminder_div { margin: 8px 0; padding: 4px; border: 1px solid #666; }#text_color, #background_color {margin-bottom: 2px; }#formatting_options { list-style-type: none; padding: 0; }#formatting_options li { margin: 0 0 4px 0; }#formatting_options input { margin: 0 10px 0 0; }td.color_identifier { width: 3em; }</style>';
			$output .= "\n";
			$output .= '<script type="text/javascript">function DimReminder(id) {if( $( "reminder_" + id + "_checkbox" ).checked ) {jQuery("#reminder_" + id).fadeTo(1000, .2);} else {jQuery("#reminder_" + id).fadeTo(1000, 1);}}</script>';
			echo $output;
		}
	} //end class

} // end if

/**
 * Insert action hooks here
 */
	add_action( 'activate_nfoppreminder.php', array( 'NFO_PPR', 'install' ) );
	add_action( 'deactivate_nfoppreminder.php', array( 'NFO_PPR', 'uninstall' ) );
	add_action( 'admin_menu', array( 'NFO_PPR', 'add_admin_page' ) );
	add_action( 'edit_form_advanced', array( 'NFO_PPR', 'output_reminder_list' ) );
	add_action( 'admin_head', array( 'NFO_PPR', 'add_header_stuff' ) );
	
?>
