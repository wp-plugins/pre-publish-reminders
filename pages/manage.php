<?php

if( isset( $_POST[ 'submit' ] ) ) {
	
	$errors = $this->validate_reminder( $_POST );
	
	if( empty( $errors ) ) {
		if( isset( $_POST[ 'id' ] ) ) {
			$this->edit_reminder( $_POST[ 'id' ], $_POST[ 'reminder_text' ], $_POST[ 'text_color' ], $_POST[ 'back_color' ], $_POST[ 'is_strong' ], $_POST[ 'is_emphasized' ], $_POST[ 'is_underlined' ], $_POST[ 'sort_order' ] );
		} else {
			$this->add_reminder( $_POST[ 'reminder_text' ], $_POST[ 'text_color' ], $_POST[ 'back_color' ], $_POST[ 'is_strong' ], $_POST[ 'is_emphasized' ], $_POST[ 'is_underlined' ], $_POST[ 'sort_order' ] );
		}		
	} else {
		?>
		<div id="message" class="error">
		<ul>
		<?php foreach( $errors as $error ) { ?>
			<li><?php echo $error; ?></li>
		<?php } ?>
		</ul>
		</div>
		<?php
	}
	
}

if( isset( $_POST[ 'deleteit' ] ) ) {
	$this->delete_reminders( $_POST[ 'delete' ] );
}

if( isset( $_GET[ 'id' ] ) ) {
	$current = $this->get_reminder( $_GET[ 'id' ] );
?>
<div class="wrap">
	<h2>Edit Reminder</h2>
<?php
} else { 
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
		<table class="widefat">
			<thead>
				<tr>
					<th scope="col" class="check-column"></th>
					<th scope="col">Sort Order</th>
					<th scope="col">Reminder Text</th>
					<th scope="col" colspan="2">Text Color</th>
					<th scope="col" colspan="2">Background Color</th>
					<th scope="col">Strong?</th>
					<th scope="col">Emphasized?</th>
					<th scope="col">Underlined?</th>
				</tr>
			</thead>
			<tbody>
				<?php $this->reminder_rows(); ?>
			</tbody>
		</table>
	</form> <!-- End the manage form -->
	<div class="tablenav">
		<br class="clear" />
	</div>
	<br class="clear" />
</div>

<div class="wrap">
	<h2>Add Reminder</h2>
<?php
}
?>
	<form name="reminder-add" id="reminder-add" method="post">
		<table class="form-table">
			<tbody>
				<tr class="form-field form-required">
					<th scope="row" valign="top"><label for="reminder_text">Reminder Text</label>
					<td>
						<textarea id="reminder_text" style="width:97%;" cols="50" rows="3" name="reminder_text"><?php echo $current->reminder; ?></textarea><br />
						Enter the reminder text that you want to be displayed when writing a post.
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" valign="top"><label for="text_color">Text Color</label>
					<td>
						<input value="<?php echo isset( $current->text_color ) ? '#' . $current->text_color : '#000000'; ?>" id="text_color" type="text" size="10" name="text_color" /><br />
						Choose a foreground color that will make this reminder stand out. Must be in hexadecimal format (i.e. #123456 ).
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" valign="top"><label for="back_color">Background Color</label>
					<td>
						<input value="<?php echo isset( $current->back_color ) ? '#' . $current->back_color : '#ffffff'; ?>" id="back_color" type="text" size="10" name="back_color" /><br />
						Choose a background color that will make this reminder stand out. Must be in hexadecimal format (i.e. #123456 ).
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" valign="top">Modifiers</th>
					<td>
						<input <?php echo $current->is_strong ? 'checked="checked"' : ''; ?> id="is_strong" type="checkbox" name="is_strong" value="yes" /> <label for="is_strong">Strong?</label><br />
						<input <?php echo $current->is_emphasized ? 'checked="checked"' : ''; ?> id="is_emphasized" type="checkbox" name="is_emphasized" value="yes" /> <label for="is_emphasized">Emphasized?</label><br />
						<input <?php echo $current->is_underlined ? 'checked="checked"' : ''; ?> id="is_underlined" type="checkbox" name="is_underlined" value="yes" /> <label for="is_underlined">Underlined?</label><br />
						Select the modifiers you wish to apply to your reminder.
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" valign="top"><label for="sort_order">Sort Order</label></th>
					<td>
						<select class="postform" id="sort_order" name="sort_order">
							<?php
							$reminders = $this->get_reminders();
							$range = range(1, count( $reminders ) + ( isset( $current->id ) ? 0 : 1 ) );
							foreach($range as $number) {
								?>
								<option <?php echo $current->sort_order == $number ? 'selected="selected"' : ''; ?> value="<?php echo $number; ?>"><?php echo $number; ?></option>
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
			if( isset( $current->id ) ) {
			?>
			<input type="hidden" name="id" value="<?php echo $current->id; ?>" />
			<input class="button" type="submit" value="Edit Reminder" name="submit" />
			<?php
			} else {
			?>
			<input class="button" type="submit" value="Add Reminder" name="submit" />
			<?php
			}
			?>
		</p>
	</form>
</div> <!-- End Wrap -->