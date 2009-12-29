<?php 
$allReminders = $this->getAllReminders();
?>
<div class="wrap">
	<?php 
	if( $_GET[ 'updated'] == 1 ) {
		?><div id="pre-publish-reminders-updated" class="updated fade"><p><?php _e( 'Changes Saved!' ); ?></p></div><?php 
	} elseif( $_GET['deleted'] > 0 ) {
		?><div id="pre-publish-reminders-updated" class="updated fade"><p><?php printf( __( '%d Reminders Deleted!' ), absint($_GET['deleted'])); ?></p></div><?php 
	}
	?>
	<h2><?php _e( 'Manage Reminders' ); ?> (<a href="<?php echo admin_url('tools.php?page=pre-publish-reminders#edit-reminder'); ?>"><?php _e( 'add new' ); ?>)</a></h2>
	
	<form method="post">
		<?php wp_nonce_field( 'delete-reminders' ); ?>
		<div class="tablenav">
			<div class="alignleft actions">
				<input type="submit" class="button-secondary action" id="delete-reminders-2" name="delete-reminders-2" value="<?php _e( 'Delete Selected Reminders' ); ?>"/>
				<br class="clear"/>
			</div>
			<br class="clear"/>
		</div>
		<table class="widefat">
			<thead>
				<tr>
					<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" /></th>
					<th scope="col"><?php _e( 'Reminder' ); ?></th>
					<th scope="col"><span style="padding: 3px 10px;"><?php _e( 'Color' ); ?></span></th>
					<th scope="col"><?php _e( 'Strong?' ); ?></th>
					<th scope="col"><?php _e( 'Emphasized?' ); ?></th>
					<th scope="col"><?php _e( 'Underlined?' ); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" /></th>
					<th scope="col"><?php _e( 'Reminder' ); ?></th>
					<th scope="col"><span style="padding: 3px 10px;"><?php _e( 'Color' ); ?></span></th>
					<th scope="col"><?php _e( 'Strong?' ); ?></th>
					<th scope="col"><?php _e( 'Emphasized?' ); ?></th>
					<th scope="col"><?php _e( 'Underlined?' ); ?></th>
				</tr>
			</tfoot>
			<tbody id="reminders-sortable">
				<?php 
				foreach( $allReminders as $reminder ) {
					?>
					<tr id="reminder_<?php echo $reminder->ID; ?>">
						<th scope="row" class="manage-column column-cb check-column">
							<input type="checkbox" name="reminders-to-delete[]" id="reminders-to-delete-<?php echo $reminder->ID; ?>" value="<?php echo $reminder->ID; ?>" />
						</th>
						<td>
							<?php echo esc_html($reminder->post_content); ?>
						</td>
						<td>
							<span style="padding: 3px 10px; background-color:#<?php echo $reminder->backColor; ?>; color:#<?php echo $reminder->textColor; ?>;"><?php _e( 'Color' ); ?></span>
						</td>
						<td>
							<?php echo $this->yesNo($reminder->strong); ?>
						</td>
						<td>
							<?php echo $this->yesNo($reminder->emphasized); ?>					
						</td>
						<td>
							<?php echo $this->yesNo($reminder->underlined); ?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<div class="tablenav">
			<div class="alignleft actions">
				<input type="submit" class="button-secondary action" id="delete-reminders" name="delete-reminders" value="<?php _e( 'Delete Selected Reminders' ); ?>"/>
				<br class="clear"/>
			</div>
			<br class="clear"/>
		</div>
	</form>
	
	<form method="post" id="edit-reminder">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="reminder-text"><?php _e( 'Text' ); ?></label></th>
					<td>
						<textarea class="large-text" name="reminder-text" id="reminder-text"><?php echo esc_html($text); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="reminder-foreground"><?php _e( 'Text Color' ); ?></label></th>
					<td>
						<input class="regular-text reminder-color-picker" type="text" name="reminder-foreground" id="reminder-foreground" value="<?php echo $foreColor; ?>" /> <?php _e( '(6 character hex, click for color picker)')?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="reminder-background"><?php _e( 'Background Color' ); ?></label></th>
					<td>
						<input class="regular-text reminder-color-picker" type="text" name="reminder-background" id="reminder-background" value="<?php echo $backColor; ?>" /> <?php _e( '(6 character hex, click for color picker)')?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Modifiers' ); ?></th>
					<td>
						<ul>
							<li><label <?php checked( true, $strong ); ?> for="reminder-modifiers-strong"><input type="checkbox" name="reminder-modifiers[strong]" id="reminder-modifiers-strong" value="1" /> <?php _e( 'Strong' ); ?></label></li>
							<li><label <?php checked( true, $emphasized ); ?> for="reminder-modifiers-emphasized"><input type="checkbox" name="reminder-modifiers[emphasized]" id="reminder-modifiers-emphasized" value="1" /> <?php _e( 'Emphasized' ); ?></label></li>
							<li><label <?php checked( true, $underlined); ?> for="reminder-modifiers-underlined"><input type="checkbox" name="reminder-modifiers[underlined]" id="reminder-modifiers-underlined" value="1" /> <?php _e( 'Underlined' ); ?></label></li>
						</ul>
					</td>
				</tr>
			</tbody>
		</table>
		<p>
			<?php wp_nonce_field('save-reminder'); ?>
			<input class="button button-primary" type="submit" name="save-reminder" id="save-reminder" value="<?php _e( 'Save Reminder' ); ?>" />
		</p>
	</form>
</div>