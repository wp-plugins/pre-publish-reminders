<?php
/*
Plugin Name: Pre-Publish Reminders
Plugin URI: http://nickohrn.com/pre-publish-plugin
Description: This plugin allows you to set reminders of actions you need to take prior to pressing the Publish button on your posts.  The list is customizable via an administration panel that you can find under the manage tab.
Author: Nick Ohrn
Version: 3.0.0
Author URI: http://nickohrn.com/
*/

/*  Copyright 2008  Nick Ohrn  (email : nick@ohrnventures.com)

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

/**
 * Avoid name collisions.
 */
if(!class_exists('NFO_Pre_Publish_Reminders')) {

	/**
	 * This class contains all the methods necessary to make the plugin run
	 * correctly.  It is a wrapper for all functionality necessary to provide
	 * Pre-Publish Reminders.
	 */
	class NFO_Pre_Publish_Reminders {
		static $version = '3.0.0';
		static $version_option_name = 'NFO_Pre_Publish_Reminders_Version';
		static $table_name = 'NFO_Pre_Publish_Reminders';

		/**
		 * Installs this plugin by first uninstalling any pre-2.0 version and then
		 * creating the new table for the plugin and registering a version number
		 * as a WordPress option.
		 *
		 * @return null
		 */
		function install() {
			global $wpdb;
			
			$table_name = $wpdb->prefix . NFO_Pre_Publish_Reminders::$table_name;

			// If the version isn't the same as it was in the past, upgrade the table.
			if( NFO_Pre_Publish_Reminders::$version != get_option( NFO_Pre_Publish_Reminders::$version_option_name ) ) {
			
				// Make sure the table doesn't already exist...
				if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
				
					// Create the query that will be executed to create the table to hold all the information about reminders
					$query = "CREATE TABLE $table_name(
								id INT(9) NOT NULL AUTO_INCREMENT,
								reminder TEXT NOT NULL,
								back_color VARCHAR(6) DEFAULT 'FFFFFF' NOT NULL,
								text_color VARCHAR(6) DEFAULT '000000' NOT NULL,
								order INT(9) NOT NULL,
								is_strong BOOL NOT NULL,
								is_emphasized BOOL NOT NULL,
								is_underlined BOOL NOT NULL,
								PRIMARY KEY (id))";
						
						
					require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
					dbDelta($query);
					
					// If the option already exists, update it... Otherwise add it
					if( get_option( NFO_Pre_Publish_Reminders::$version_option_name ) ) {
						update_option( NFO_Pre_Publish_Reminders::$versionOptionName, NFO_Pre_Publish_Reminders::$version );
					} else {
						add_option( NFO_Pre_Publish_Reminders::$version_option_name, NFO_Pre_Publish_Reminders::$version );
					}
					
				} // End table existence check
				
			} // End upgrade version
		}

		/**
		 * Delete the reminders table, all data contained within, and remove
		 * the option specifying the plugin version number.
		 *
		 * @return null
		 */
		function uninstall() {
			global $wpdb;
			$table_name = $wpdb->prefix . NFO_Pre_Publish_Reminders::$table_name;
			
			// Check to make sure the table exists before dropping it
			if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
			
				// Drop the table
				$wpdb->query( "DROP TABLE $table_name" );
				
				// Remove the version option
				delete_option( NFO_Pre_Publish_Reminders::$version_option_name );
			}
		}

		/**
		 * Add the administration page to the manage menu.
		 *
		 * @return null
		 */
		function add_admin_page() {
			add_management_page('Pre-Publish Reminders', 'Pre-Publish Reminders', 8, basename(__FILE__), array( 'NFO_Pre_Publish_Reminders', 'manage_page' ) );
		}

		/**
		 * Generates the output for the management page for the pre publish reminders.
		 *
		 * @return null
		 */
		public function manage_page() {
		
			// Retrieve the glob WP database object.
			global $wpdb;
			
			// Process all the possible input on the page.
			if( isset( $_POST['submitted'] ) ) {
				NFO_Pre_Publish_Reminders::process_post_form_variables( $_POST );
			} elseif( isset( $_GET['reminder_action'] ) && isset ( $_GET['id'] ) ) {
				$current = NFO_Pre_Publish_Reminders::process_get_variables( $_GET );
				$editing = $current['editing'];
			}
			
			?>
			
			<div class="wrap">
				<form name="reminder-manage" id="reminder-manage" method="post">
					<h2>Manage Reminders (<a href="#reminder-add">add new</a>)</h2>
					<div class="tablenav">
						<div class="alignleft">
							<input class="button-secondary delete" type="submit" name="deleteit" value="Delete" />
						</div>
						<br class="clear" />
					</div>
					<br class="clear" />
					<?php
					$number_reminders = NFO_Pre_Publish_Reminders::output_admin_table(); 
					?>
				</form> <!-- End the manage form -->
				<div class="tablenav">
					<br class="clear" />
				</div>
				<br class="clear" />
			</div>
			
			<div class="wrap">
				<h2>Add Reminder</h2>
				<form name="reminder-add" id="reminder-add" method="post">
					<table class="form-table">
						<tbody>
							<tr class="form-field form-required">
								<th scope="row" valign="top"><label for="reminder_text">Reminder Text</label>
								<td>
									<textarea id="reminder_text" style="width:97%;" cols="50" rows="3" name="reminder_text"></textarea><br />
									Enter the reminder text that you want to be displayed when writing a post.
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top"><label for="reminder_text_color">Text Color</label>
								<td>
									<input id="reminder_text_color" type="text" size="10" value="#000000" name="reminder_text_color" /><br />
									Choose a foreground color that will make this reminder stand out.
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top"><label for="reminder_back_color">Background Color</label>
								<td>
									<input id="reminder_back_color" type="text" size="10" value="#ffffff" name="reminder_back_color" /><br />
									Choose a background color that will make this reminder stand out.
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top">Modifiers</th>
								<td>
									<input id="is_strong" type="checkbox" name="is_strong" /> <label for="is_strong">Strong?</label><br />
									<input id="is_emphasized" type="checkbox" name="is_emphasized" /> <label for="is_emphasized">Emphasized?</label><br />
									<input id="is_underlined" type="checkbox" name="is_underlined" /> <label for="is_underlined">Underlined?</label><br />
									Select the modifiers you wish to apply to your reminder.
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top"><label for="sort_order">Sort Order</label></th>
								<td>
									<select class="postform" id="sort_order" name="sort_order">
										<?php
										$range = range(1, $number_reminders + 1);
										foreach($range as $number) {
											?>
											<option value="<?php echo $number; ?>"><?php echo $number; ?></option>
											<?php
										}
										?>
									</select><br />
									The order in which you want the plugin to appear.
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input class="button" type="submit" value="Add Reminder" name="submit" />
					</p>
				</form>
				
			</div> <!-- End Wrap -->
			
			<?php
			
		}
		
		/**
		 * Print out a table of all reminders currently in the reminder database.
		 *
		 */
		function output_admin_table() {
		
			// Retrieve the global WP database object
			global $wpdb;
			$table_name = $wpdb->prefix . NFO_Pre_Publish_Reminders::$table_name;
			
			$query = "SELECT * FROM $table_name ORDER BY sort ASC";
			$reminders = $wpdb->get_results( $query , ARRAY_A);
			?>
			
			<table class="widefat">
				<thead>
					<tr>
						<th scope="col"><input type="checkbox" /></th>
						<th scope="col">Sort Order</th>
						<th scope="col">Reminder Text</th>
						<th scope="col" colspan="2">Text Color</th>
						<th scope="col" colspan="2">Background Color</th>
						<th scope="col">Strong?</th>
						<th scope="col">Emphasized?</th>
						<th scope="col">Underlined?</th>
				</thead>
				<tbody id="the-reminders">
			<?php
			if( 0 < count( $reminders ) ) {
				$class = '';
				
				// Iterate over each reminder and print its row
				foreach( $reminders as $reminder ) {
				
					// Establish whether or not the reminder should be bold or emphasized
					$strong = $reminder['is_strong'] ? '<strong>Yes</strong>' : 'No';
					$emphasized = $reminder['is_emphasized'] ? '<em>Yes</em>' : 'No';
					$underlined = $reminder['is_underlined'] ? '<span style="text-decoration: underline;">Yes</span>' : 'No';
					
					?>
					<tr class="<?php echo $class; ?>" id="order_<?php echo $reminder['order']; ?>">
						<th scope="row"><input type="checkbox" name="reminder[<?php echo $reminder['id']; ?>]" id="reminder_cb_<?php echo $reminder['id']; ?>" /></th>
						<td><?php echo $reminder['order']; ?></td>
						<td><?php echo stripslashes($reminder['reminder']); ?></td>
						<td>#<?php echo $reminder['text_color']; ?></td>
						<td class="color-indentifier" style="background-color: #<?php echo $reminder['text_color']; ?>;"></td>
						<td>#<?php echo $reminder['back_color']; ?></td>
						<td class="color-indentifier" style="background-color: #<?php echo $reminder['back_color']; ?>;">
						<td><?php echo $strong; ?></td>
						<td><?php echo $emphasized; ?></td>
						<td><?php echo $underlined; ?></td>
					</tr>
					
					<?php
					$class = ( 'alternate' == $class ) ? '' : 'alternate';
				} // End foreach reminder
			} // End existence of reminder check
			
			?>
				</tbody>
			</table> <!-- End Management Table -->
			<?php
			
			return count( $reminders );
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
					$get_num_reminders = "(SELECT COUNT( sort ) AS num_reminders FROM " . $wpdb->prefix . "NFO_Pre_Publish_Reminders)";
					$num_reminders = $wpdb->get_results( $get_num_reminders, ARRAY_A );
					$num = $num_reminders[0]['num_reminders'];
					$query = "INSERT INTO " . $wpdb->prefix . NFO_Pre_Publish_Reminders::$tableName . " ( reminder , back_color, text_color, sort, is_bold, is_italic ) VALUES ( '$reminder_text', '$back_color', '$text_color', ($num + 1), $bold, $italic )";
					$action = 'added';
				} elseif( 'edit' == $form_array['submitted'] ) {
					$reminder_id = intval( $form_array['id'] );
					$query = "UPDATE " . $wpdb->prefix . NFO_Pre_Publish_Reminders::$tableName . " SET reminder = '$reminder_text', back_color = '$back_color', text_color = '$text_color', sort = $order, is_bold = $bold, is_italic = $italic WHERE id = $reminder_id";
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
				$order = "SELECT sort FROM " . $wpdb->prefix . "NFO_Pre_Publish_Reminders WHERE id = $id";
				$select_order = $wpdb->get_results( $order, ARRAY_A );
				if($select_order[0]) {
					$current_order = $select_order[0]['sort'];
					$redo_reminder_order = "UPDATE " . $wpdb->prefix . "NFO_Pre_Publish_Reminders SET sort = sort - 1 WHERE sort > $current_order";
					$wpdb->query( $redo_reminder_order );

					//Delete the reminder from the reminder table
					$delete = "DELETE FROM " . $wpdb->prefix . NFO_Pre_Publish_Reminders::$tableName . " WHERE id = $id";
					$wpdb->query( $delete );
					echo '<div id="message" class="updated fade"><p>Deletion successful.</p></div>';
				} else {
					echo '<div id="message" class="error fade"><p>Deletion could not occur because that reminder was not found.</p></div>';
				}
			} elseif ( 'edit_reminder' == $array['reminder_action'] ) {
				$select = "SELECT * FROM " . $wpdb->prefix . NFO_Pre_Publish_Reminders::$tableName . " WHERE id = $id";
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
		 * Outputs the current reminders as an ordered list.
		 */
		function output_reminder_list() {
			global $wpdb;
			$query = "SELECT id, reminder, back_color, text_color, sort, is_bold, is_italic FROM " . $wpdb->prefix . NFO_Pre_Publish_Reminders::$tableName . ' ORDER BY sort';
			$reminders = $wpdb->get_results( $query, ARRAY_A );
			if( count( $reminders ) > 0 ) {
				echo '<div id="remindersdiv" class="postbox"><h3><a class="togbox">+</a>Reminders</h3><div class="inside"><form><ol id="reminder_list">';
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
				echo '</ol></form></div></div>';
			}
		}
		
	} //end class

} // end if

/**
 * Insert action hooks here
 */
	add_action( 'activate_nfoppreminder.php', array( 'NFO_Pre_Publish_Reminders', 'install' ) );
	add_action( 'deactivate_nfoppreminder.php', array( 'NFO_Pre_Publish_Reminders', 'uninstall' ) );
	add_action( 'admin_menu', array( 'NFO_Pre_Publish_Reminders', 'add_admin_page' ) );
	add_action( 'edit_form_advanced', array( 'NFO_Pre_Publish_Reminders', 'output_reminder_list' ) );
	
?>
