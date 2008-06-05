<?php
/*
Plugin Name: Pre-Publish Reminders
Plugin URI: http://nickohrn.com/pre-publish-plugin
Description: This plugin allows you to set reminders of actions you need to take prior to pressing the Publish button on your posts.  The list is customizable via an administration panel that you can find under the manage tab.
Author: Nick Ohrn
Version: 4.0.0
Author URI: http://nickohrn.com/
*/

if( !class_exists( 'Pre_Publish_Reminders' ) ) {

	class Pre_Publish_Reminders {
		
		var $version = '4.0.0';
		var $table_name;
		var $is_installed = false;
		
		
		/**
		 * Default constructor initializes the table name to use for the Pre-Publish Reminders.
		 *
		 * @return Pre_Publish_Reminders
		 */
		function Pre_Publish_Reminders() {
			global $wpdb;
			$this->table_name = $wpdb->prefix . 'Pre_Publish_Reminders';
		}
		
		// CALLBACKS
		
		/**
		 * Activation callback.
		 *
		 * Installs or upgrades the Pre-Publish Reminders plugin.
		 */
		function on_activate() {
			if( FALSE === get_option( 'Pre-Publish Reminders Version' ) ) {
				$this->install();
			} else {
				$this->upgrade( get_option( 'Pre-Publish Reminders Version' ) );
			}
		}

		/**
		 * Deactivation callback.
		 *
		 * Doesn't really do anything because the plugin is uninstalled through a separate mechanism.
		 */
		function on_deactivate() {
			
		}
		
		/**
		 * Add the Pre-Publish Reminders management page.  Also adds a meta box to the page and post
		 * interfaces.  Finally, enqueues necessary javascript files.
		 */
		function on_admin_menu() {
			if( isset( $_POST[ 'uninstall_pre_publish_reminders' ] ) ) {
				$this->uninstall();
			}
			
			$this->is_installed = ( FALSE !== get_option( 'Pre-Publish Reminders Version' ) );
			
			add_management_page( 'Publishing Reminders', 'Publishing Reminders', 8, __FILE__, array( &$this, 'manage_page' ) );
			
			add_meta_box( 'reminders', 'Pre-Publish Reminders', array( &$this, 'on_meta_box' ), 'page', 'normal' );
			add_meta_box( 'reminders', 'Pre-Publish Reminders', array( &$this, 'on_meta_box' ), 'post', 'normal' );
			
			if( strpos( $_REQUEST[ 'REQUEST_URI' ], 'pre-publish-reminders' ) ) {
				wp_enqueue_script( 'admin-forms' );
			}
			
			wp_enqueue_script( 'pre-publish-reminders', get_bloginfo( 'siteurl' ) . '/wp-content/plugins/pre-publish-reminders/js/pre-publish-reminders.js', array( 'jquery' ) );
			
		}
		
		/**
		 * Outputs the current reminders as an ordered list.
		 */
		function on_meta_box() {
			global $wpdb;
			
			wp_enqueue_script( 'jquery' );
			
			$reminders = $this->get_reminders();
			if( count( $reminders ) > 0 ) {
			?>
			<form>
				<ol style="margin: 0;padding: 0;" id="reminder_list">
				<?php
				foreach( $reminders as $reminder ) {
					$this_reminder = stripslashes( $reminder->reminder );
					if( $reminder->is_strong ) {
						$this_reminder = '<strong>' . $this_reminder . '</strong>';
					}
					if( $reminder->is_emphasized ) {
						$this_reminder = '<em>' . $this_reminder . '</em>';
					}
					if( $reminder->is_underlined ) {
						$this_reminder = '<span style="text-decoration:underline;">' . $this_reminder . '</span>';
					}
					echo '<li id="reminder_' . $reminder->id . '" style="padding: 5px; margin: 0px; color:#' . $reminder->text_color . '; background-color:#' . $reminder->back_color . ';"><input type="checkbox" class="reminder_cb" id="reminder_' . $reminder->id . '_checkbox" name="is_completed" value="' . $reminder->id . '" /> ' . $this_reminder . '</li>';
				}
				?>
				</ol>
			</form>
			<?php
			}
		}
		
		// UTILITY FUNCTIONS
		
		/**
		 * Creates the reminders table and then adds the version option.
		 */
		function install() {
			$pre_publish_reminders_table = 
						"CREATE TABLE $this->table_name(
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
			
			maybe_create_table( $this->table_name, $pre_publish_reminders_table );
			add_option( 'Pre-Publish Reminders Version', $this->version );
		}

		/**
		 * Delete the reminders table, all data contained within, and remove
		 * the option specifying the plugin version number.
		 */
		function uninstall() {
			global $wpdb;
			
			// Drop the table
			$wpdb->query( "DROP TABLE $this->table_name" );
							
			// Remove the version option
			delete_option( 'Pre-Publish Reminders Version' );
		}

		/**
		 * Upgrades the plugin from an old version.
		 *
		 * @param string $old_version the version number for the old version.
		 */
		function upgrade( $old_version ) {
			update_option( 'Pre-Publish Reminders Version', $this->version );
			
			switch( $old_version ) {
				case '4.0.0':
					break;
				default:
					$this->uninstall();
					$this->install();
					break;
			}
			
			
		}
	
		// REMINDER OPERATIONS

		/**
		 * Validates a set of reminder values to determine if they're valid values for a reminder.
		 *
		 * @param array $reminder_values
		 * @return array an array of error messages.
		 */
		function validate_reminder( $reminder_values ) {
			$errors = array();
			
			if( $reminder_values[ 'reminder_text' ] == '' ) {
				$errors[] = 'Reminder text cannot be empty.';
			} 
			
			if( !preg_match('/^#[a-fA-F0-9]{6}$/', $reminder_values[ 'back_color' ] ) )  {
				$errors[] = 'Your background color is invalid.  Remember, it has to be a 6 character hex color code.';
			} 
			
			if( !preg_match('/^#[a-fA-F0-9]{6}$/', $reminder_values[ 'text_color' ] ) ) {
				$errors[] = 'Your text color is invalid.  Remember, it has to be a 6 character hex color code.';
			}
			
			return $errors;
		}
		
		/**
		 * Returns a reminder object uniquely identified by the passed id.
		 *
		 * @param int $id the unique identifier of the reminder to return.
		 * @return object a reminder object with appropriate properties.
		 */
		function get_reminder( $id ) {
			global $wpdb;
			
			return $wpdb->get_row( "SELECT * FROM $this->table_name WHERE id = " . $wpdb->escape( $id ), OBJECT );
		}

		/**
		 * Returns an array of all reminder objects, sorted by sort order.
		 */
		function get_reminders() {
			global $wpdb;
			
			return $wpdb->get_results( "SELECT * FROM $this->table_name", OBJECT );
		}
	
		/**
		 * Adds a new reminder to the system.
		 * @param string $reminder the reminder text.
		 * @param string $text_color the text color of the reminder when displayed (in #123456 format).
		 * @param string $back_color the background color of the reminder when displayed (in #123456 format).
		 * @param string $is_strong whether or not this reminder should be strong when displayed.
		 * @param string $is_emphasized whether or not this reminder should be emphasized when displayed.
		 * @param string $is_underlined whether or not this reminder should be underlined when displayed.
		 * @param int $sort_order the ordering for the reminder.
		 */
		function add_reminder( $reminder, $text_color, $back_color, $is_strong, $is_emphasized, $is_underlined, $sort_order ) {
			global $wpdb;
			
			$reminder = $wpdb->escape( $reminder );
			$text_color = substr( $wpdb->escape( $text_color ), 1);
			$back_color = substr( $wpdb->escape( $back_color ), 1);
			$is_strong = $wpdb->escape( $is_strong );
			$is_emphasized = $wpdb->escape( $is_emphasized );
			$is_underlined = $wpdb->escape( $is_underlined );
			$sort_order = $wpdb->escape( $sort_order );
			
			$is_strong = $is_strong == 'yes' ? 1 : 0;
			$is_emphasized = $is_emphasized == 'yes' ? 1 : 0;
			$is_underlined = $is_underlined == 'yes' ? 1 : 0;
			
			$update_sort = "UPDATE $this->table_name SET sort_order = sort_order + 1 WHERE sort_order >= $sort_order";
			$insert = "INSERT INTO $this->table_name (reminder, back_color, text_color, is_strong, is_emphasized, is_underlined, sort_order) VALUES( '$reminder', '$text_color', '$back_color', $is_strong, $is_emphasized, $is_underlined, $sort_order)";
			
			$wpdb->query( $update_sort );
			$wpdb->query( $insert );
		}
		
		/**
		 * Edits a reminder currently saved in the system.
		 *
		 * @param int $id the unique identifier by which to identify this reminder.
		 * @param string $reminder the reminder text.
		 * @param string $text_color the text color of the reminder when displayed (in #123456 format).
		 * @param string $back_color the background color of the reminder when displayed (in #123456 format).
		 * @param string $is_strong whether or not this reminder should be strong when displayed.
		 * @param string $is_emphasized whether or not this reminder should be emphasized when displayed.
		 * @param string $is_underlined whether or not this reminder should be underlined when displayed.
		 * @param int $sort_order the ordering for the reminder.
		 */
		function edit_reminder( $id, $reminder, $text_color, $back_color, $is_strong, $is_emphasized, $is_underlined, $sort_order ) {
			global $wpdb;
			
			$id = $wpdb->escape( $id );
			$reminder = $wpdb->escape( $reminder );
			$text_color = substr( $wpdb->escape( $text_color ), 1);
			$back_color = substr( $wpdb->escape( $back_color ), 1);
			$is_strong = $wpdb->escape( $is_strong );
			$is_emphasized = $wpdb->escape( $is_emphasized );
			$is_underlined = $wpdb->escape( $is_underlined );
			$sort_order = $wpdb->escape( $sort_order );
			
			$is_strong = $is_strong == 'yes' ? 1 : 0;
			$is_emphasized = $is_emphasized == 'yes' ? 1 : 0;
			$is_underlined = $is_underlined == 'yes' ? 1 : 0;
			
			$initial_update_sort = "UPDATE $this->table_name SET sort_order = sort_order - 1 WHERE sort_order >= (SELECT sort_order FROM $this->table_name WHERE id = $id) AND id <> $id";
			$update_reminder = "UPDATE $this->table_name SET reminder = '$reminder', back_color = '$back_color', text_color = '$text_color', sort_order = $sort_order, is_strong = $is_strong, is_emphasized = $is_emphasized, is_underlined = $is_underlined WHERE id = $id";
			$final_update_sort = "UPDATE $this->table_name SET sort_order = sort_order + 1 WHERE sort_order >= $sort_order AND id <> $id";
			
			
			$wpdb->query( $initial_update_sort );
			$wpdb->query( $update_reminder );
			$wpdb->query( $final_update_sort );
		}
		
		/**
		 * Deletes a reminder from the system.
		 *
		 * @param int $id the unique identifier of the id to delete.
		 */
		function delete_reminder( $id ) {
			global $wpdb;
			
			$wpdb->query( "UPDATE $this->table_name SET sort_order = sort_order - 1 WHERE sort_order > (SELECT sort_order FROM $this->table_name WHERE id = " . $wpdb->escape( $id ) . ")" );
			$wpdb->query( "DELETE FROM $this->table_name WHERE id = " . $wpdb->escape( $id ) );
		}
		
		/**
		 * Deletes a set of reminders from the system.
		 *
		 * @param array $ids a collection of unique identifiers with which to delete
		 * reminders.
		 */
		function delete_reminders( $ids ) {
			foreach( $ids as $id ) {
				$this->delete_reminder( $id );
			}
		}
		
		// DISPLAY FUNCTIONS
		
		/**
		 * Echoes the output for the reminders management page.
		 */
		function manage_page() {
			if( $this->is_installed ) {
				include( dirname( __FILE__ ) . '/pages/manage.php' );
			} else {
				echo get_option( 'Pre-Publish Reminders Version' );
				echo '<div class="wrap"><p>The Pre-Publish Reminders plugin is uninstalled.  Please deactivate the plugin.  If you wish to use the plugin again, please
				reactivate it after deactivation.</p></div>';
			}
		}
		
		/**
		 * Prints out a table row for each reminder int the system.
		 *
		 */
		function reminder_rows() {
			$reminders = $this->get_reminders();
			
			foreach( $reminders as $reminder ) {
				$class = ( $class == 'alternate' ? '' : 'alternate' );
				
				$strong = $reminder->is_strong ? '<strong>Yes</strong>' : 'No';
				$emphasized = $reminder->is_emphasized ? '<em>Yes</em>' : 'No';
				$underlined = $reminder->is_underlined ? '<span style="text-decoration: underline;">Yes</span>' : 'No';
				
				?>
				<tr class="<?php echo $class; ?>" id="reminder_<?php echo $reminder->id; ?>">
					<th class="check-column" scope="row"><input type="checkbox" name="delete[<?php echo $reminder->id; ?>]" value="<?php echo $reminder->id; ?>" id="reminder_ch_<?php echo $reminder->id; ?>" /></th>
					<td><a href="<?php bloginfo( 'siteurl' ); ?>/wp-admin/edit.php?page=pre-publish-reminders/pre-publish-reminders.php&amp;id=<?php echo $reminder->id; ?>"><?php echo $reminder->sort_order; ?></a></td>
					<td><?php echo stripslashes( $reminder->reminder ); ?></td>
					<td>#<?php echo $reminder->text_color; ?></td>
					<td class="color-identifier" style="background-color: #<?php echo $reminder->text_color; ?>"></td>
					<td>#<?php echo $reminder->back_color; ?></td>
					<td class="color-identifier" style="background-color: #<?php echo $reminder->back_color; ?>"></td>
					<td><?php echo $strong; ?></td>
					<td><?php echo $emphasized; ?></td>
					<td><?php echo $underlined; ?></td>
				</tr>
				<?php
			}
		}

	} //end Pre_Publish_Reminders class

} // end class existence check

if( class_exists( 'Pre_Publish_Reminders' ) ) {
	$pre_publish_reminders = new Pre_Publish_Reminders();
	
	// Activation/Deactivation
	register_activation_hook( __FILE__, array( &$pre_publish_reminders, 'on_activate' ) );
	register_deactivation_hook( __FILE__, array( &$pre_publish_reminders, 'on_deactivate' ) );
	
	// Action hooks
	add_action( 'admin_menu', array( &$pre_publish_reminders, 'on_admin_menu' ) );
}
	
?>
