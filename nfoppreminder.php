<?php
/*
Plugin Name: Pre-Publish Reminders
Plugin URI: http://nickohrn.com/pre-publish-plugin
Description: This plugin allows you to set reminders of actions you need to take prior to pressing the Publish button on your posts.  The list is customizable via an administration panel that you can find under the manage tab.
Author: Nick Ohrn
Version: 3.2.0
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
		
		static $version = '3.2.0';
		static $version_option_name = 'NFO_Pre_Publish_Reminders_Version';
		var $table_name;
		
		
		function NFO_Pre_Publish_Reminders() {
			global $wpdb;
			$this->table_name = $wpdb->prefix . 'NFO_Pre_Publish_Reminders';
		}

		/**
		 * Installs this plugin by first uninstalling any pre-2.0 version and then
		 * creating the new table for the plugin and registering a version number
		 * as a WordPress option.
		 *
		 * @return null
		 */
		function install() {
			global $wpdb;
			
			// If the version isn't the same as it was in the past, upgrade the table.
			if( NFO_Pre_Publish_Reminders::$version != get_option( NFO_Pre_Publish_Reminders::$version_option_name ) ) {
				// Make sure the table doesn't already exist...
				if( $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" ) != $this->table_name ) {
					// Create the query that will be executed to create the table to hold all the information about reminders
					$query = "CREATE TABLE $this->table_name(
								id INT(9) NOT NULL AUTO_INCREMENT,
								reminder TEXT NOT NULL,
								back_color VARCHAR(6) DEFAULT 'ffffff' NOT NULL,
								text_color VARCHAR(6) DEFAULT '000000' NOT NULL,
								sort_order INT(9) NOT NULL,
								is_strong BOOL NOT NULL,
								is_emphasized BOOL NOT NULL,
								is_underlined BOOL NOT NULL,
								PRIMARY KEY (id))";
						
						
					require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
					dbDelta($query);
					
					// If the option already exists, update it... Otherwise add it
					if( get_option( NFO_Pre_Publish_Reminders::$version_option_name ) ) {
						update_option( NFO_Pre_Publish_Reminders::$version_option_name, NFO_Pre_Publish_Reminders::$version );
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
			
			// Check to make sure the table exists before dropping it
			if( $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" ) == $this->table_name ) {
			
				// Drop the table
				$wpdb->query( "DROP TABLE $this->table_name" );
			}
							
			// Remove the version option
			delete_option( NFO_Pre_Publish_Reminders::$version_option_name );
		}

		/**
		 * Add the administration page to the manage menu.
		 *
		 * @return null
		 */
		function add_admin_page() {
			$page = add_management_page( 'Publishing Reminders', 'Publishing Reminders', 8, basename( __FILE__ ), array( &$this, 'manage_page' ) );
			add_meta_box('reminders', 'Pre-Publish Reminders', array('NFO_Pre_Publish_Reminders', 'output_reminder_list'), 'post', 'normal');
			wp_enqueue_script( 'admin-forms' );
		}
		
		function admin_head() {
			?>
<script type="text/javascript">
/* <![CDATA[ */
function PPRDimReminder(id) {
	if( jQuery( "#reminder_" + id + "_checkbox" ).attr("checked")) {
		jQuery( "#reminder_" + id ).fadeTo( 1000, .2 );
	} else {
		jQuery( "#reminder_" + id ).fadeTo( 1000, 1 );
	}
}
/* ]]> */
</script>
			<?php
		}

		/**
		 * Generates the output for the management page for the pre publish reminders.
		 *
		 * @return null
		 */
		public function manage_page() {
		
			// Retrieve the glob WP database object.
			global $wpdb;
			
			$current = array();
			
			// Process all the possible input on the page.
			if( isset( $_POST['submit'] ) ) {
				$current = $this->process_add_or_edit( $_POST );
				
			} else if( isset( $_POST['deleteit'] ) ) {
				$this->process_delete( $_POST );
				
			} else if( isset ( $_GET['reminder_id'] ) ) {
				$current = $this->get_reminder( $_GET['reminder_id'] );
				
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
					$number_reminders = $this->output_admin_table(); 
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
									<textarea id="reminder_text" style="width:97%;" cols="50" rows="3" name="reminder_text"><?php echo $current['reminder']; ?></textarea><br />
									Enter the reminder text that you want to be displayed when writing a post.
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top"><label for="reminder_text_color">Text Color</label>
								<td>
									<input value="<?php echo isset( $current['text_color'] ) ? '#' . $current['text_color'] : '#000000'; ?>" id="reminder_text_color" type="text" size="10" name="reminder_text_color" /><br />
									Choose a foreground color that will make this reminder stand out.
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top"><label for="reminder_back_color">Background Color</label>
								<td>
									<input value="<?php echo isset( $current['back_color'] ) ? '#' . $current['back_color'] : '#ffffff'; ?>" id="reminder_back_color" type="text" size="10" name="reminder_back_color" /><br />
									Choose a background color that will make this reminder stand out.
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top">Modifiers</th>
								<td>
									<input <?php echo $current['is_strong'] ? 'checked="checked"' : ''; ?> id="is_strong" type="checkbox" name="is_strong" /> <label for="is_strong">Strong?</label><br />
									<input <?php echo $current['is_emphasized'] ? 'checked="checked"' : ''; ?> id="is_emphasized" type="checkbox" name="is_emphasized" /> <label for="is_emphasized">Emphasized?</label><br />
									<input <?php echo $current['is_underlined'] ? 'checked="checked"' : ''; ?> id="is_underlined" type="checkbox" name="is_underlined" /> <label for="is_underlined">Underlined?</label><br />
									Select the modifiers you wish to apply to your reminder.
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top"><label for="sort_order">Sort Order</label></th>
								<td>
									<select class="postform" id="sort_order" name="sort_order">
										<?php
										$range = range(1, $number_reminders + ( isset( $current['id'] ) ? 0 : 1 ) );
										foreach($range as $number) {
											?>
											<option <?php echo $current['sort_order'] == $number ? 'selected="selected"' : ''; ?> value="<?php echo $number; ?>"><?php echo $number; ?></option>
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
						<?php
						if( isset( $current['id'] ) ) {
						?>
						<input type="hidden" name="id" value="<?php echo $current['id']; ?>" />
						<?php
						}
						?>
						<input class="button" type="submit" value="Save Reminder" name="submit" />
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
			global $wpdb;
			
			$query = "SELECT * FROM $this->table_name ORDER BY sort_order ASC";
			$reminders = $wpdb->get_results( $query , ARRAY_A);
			?>
			
			<table class="widefat">
				<thead>
					<tr>
						<th scope="col"><input type="checkbox" onClick="checkAll(document.getElementById('reminder-manage'));" /></th>
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
					<tr class="<?php echo $class; ?>" id="order_<?php echo $reminder['sort_order']; ?>">
						<th scope="row"><input type="checkbox" name="delete[]" value="<?php echo $reminder['id']; ?>" id="reminder_cb_<?php echo $reminder['id']; ?>" /></th>
						<td><a href="/wp-admin/edit.php?page=nfoppreminder.php&amp;reminder_id=<?php echo $reminder['id']; ?>"><?php echo $reminder['sort_order']; ?></a></td>
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
		function process_add_or_edit( $values ) {
			global $wpdb;
			
			if( $values['reminder_text'] == '' ) {
				echo '<div id="message" class="error fade"><p>You have to set some reminder text!</p></div>';
			} elseif( !preg_match('/^#[a-fA-F0-9]{6}$/', $values['reminder_back_color'] ) )  {
				echo '<div id="message" class="error fade"><p>Your background color is invalid.  Remember, it has to be a 6 character hex color code!</p></div>';
			} elseif( !preg_match('/^#[a-fA-F0-9]{6}$/', $values['reminder_text_color'] ) ) {
				echo '<div id="message" class="error fade"><p>Your text color is invalid.  Remember, it has to be a 6 character hex color code!</p></div>';
			} else {
				
				//Initialize formatting variables.
				$strong = $values['is_strong'] == 'on' ? 1 : 0;
				$emphasized = $values['is_emphasized'] == 'on' ? 1 : 0;;
				$underlined = $values['is_underlined'] == 'on' ? 1 : 0;;
				
				$reminder_text = $wpdb->escape( $values['reminder_text'] );
				$back_color = $wpdb->escape( substr( $values['reminder_back_color'], 1 ) );
				$text_color = $wpdb->escape( substr( $values['reminder_text_color'], 1 ) );
				$order = $wpdb->escape( $values['sort_order'] );

				if( isset( $values['id'] ) ) {
					$id = intval( $values['id'] );
					$query = "UPDATE $this->table_name 
								SET 
								reminder = '$reminder_text', back_color = '$back_color', 
								text_color = '$text_color', sort_order = $order, is_strong = $strong, 
								is_emphasized = $emphasized, is_underlined = $underlined 
								WHERE id = $id";
					$action = 'edited';
					$result = $wpdb->query( $query );
					
				} else {
				
					$query = "INSERT INTO $this->table_name 
								( reminder , back_color, text_color, sort_order, is_strong, is_emphasized, is_underlined ) 
								VALUES 
								( '$reminder_text', '$back_color', '$text_color', $order, $strong, $emphasized, $underlined )";
					$action = 'added';
					$update_order_result = $wpdb->query( "UPDATE $this->table_name SET sort_order = sort_order + 1 WHERE sort_order >= $order" );
					$result = $wpdb->query( $query );
					
				}

				if( false !== $result ) {
					echo '<div id="message" class="updated fade"><p>You successfully ' . $action . ' your reminder!</p></div>';
					return;
				} else {
					echo '<div id="message" class="error fade"><p>Your reminder was not added to the database.  There was an unfortunate error.</p></div>';
				}
			}
			
			return $this->populate_current( $values );
		}
		
		function populate_current( $values ) {
			$current = array();
			
			$current['back_color'] = substr($values['reminder_back_color'], 1);
			$current['text_color'] = substr($values['reminder_text_color'], 1);
			$current['reminder'] = $values['reminder_text'];
			$current['sort_order'] = $values['sort_order'];
			$current['is_strong'] = $values['is_strong'];
			$current['is_emphasized'] = $values['is_emphasized'];
			$current['is_underlined'] = $values['is_underlined'];
			
			if( isset( $values['id'] ) ) {
				$current['id'] = $values['id'];
			}
			
			return $current;
		}
		
		function process_delete( $values ) {
			global $wpdb;
			
			foreach( $values['delete'] as $id_to_delete ) {
				$wpdb->query( "DELETE FROM $this->table_name WHERE id = " . $wpdb->escape( $id_to_delete ) );
			}
		}

		function get_reminder( $reminder_id ) {
			global $wpdb;
			
			$id = intval( $wpdb->escape( $reminder_id ) );

			$query = "SELECT * FROM $this->table_name WHERE id = " . $wpdb->escape( $reminder_id );

			$row = $wpdb->get_row( $query, ARRAY_A );
			if( $row ) {
				return $row;
			} else {
				return array();
			}
		}

		/**
		 * Outputs the current reminders as an ordered list.
		 */
		function output_reminder_list() {
			global $wpdb;
			
			wp_enqueue_script( 'jquery' );
			
			$query = "SELECT id, reminder, back_color, text_color, sort_order, is_strong, is_emphasized, is_underlined FROM $this->table_name ORDER BY sort_order";
			$reminders = $wpdb->get_results( $query, ARRAY_A );
			if( count( $reminders ) > 0 ) {
			?>
			<form>
				<ol id="reminder_list">
				<?php
				foreach( $reminders as $reminder ) {
					$this_reminder = stripslashes( $reminder['reminder'] );
					if( $reminder['is_strong'] ) {
						$this_reminder = '<strong>' . $this_reminder . '</strong>';
					}
					if( $reminder['is_emphasized'] ) {
						$this_reminder = '<em>' . $this_reminder . '</em>';
					}
					if( $reminder['is_underlined'] ) {
						$this_reminder = '<span style="text-decoration:underline;">' . $this_reminder . '</span>';
					}
					echo '<li id="reminder_' . $reminder['id'] . '" style="color:#' . $reminder['text_color'] . '; background-color:#' . $reminder['back_color'] . ';"><input type="checkbox" id="reminder_' . $reminder['id'] . '_checkbox" name="is_completed" value="' . $reminder['id'] . '" onclick="PPRDimReminder(' . $reminder['id'] . ')" /> ' . $this_reminder . '</li>';
				}
				?>
				</ol>
			</form>
			<?php
			}
		}
		
	} //end class

} // end if

/**
 * Insert action hooks here
 */

if(class_exists('NFO_Pre_Publish_Reminders')) {
	$ppr = new NFO_Pre_Publish_Reminders();
	
	register_activation_hook( __FILE__, array( &$ppr, 'install' ) );
	register_deactivation_hook( __FILE__, array( &$ppr, 'uninstall' ) );
	add_action( 'admin_menu', array( &$ppr, 'add_admin_page' ) );
	add_action( "admin_print_scripts", array( &$ppr, 'admin_head' ) );	
}
	
?>
