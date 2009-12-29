<?php 
global $post;
$checkedReminders = $this->getCheckedRemindersForPost($post->ID);
$allReminders = $this->getAllReminders();
?>
<?php 
if( empty( $allReminders ) ) {
	?>
	<p>
		<?php printf( __( 'You don\'t have any reminders created.  Go <a href="%s">create some</a>!' ), admin_url('tools.php?page=pre-publish-reminders' ) ); ?>
	</p>
	<?php
} else {
	?>
	<ol>
		<?php 
		foreach( $allReminders as $reminder ) { 
			$text = esc_html($reminder->post_content);
			if( $reminder->strong ) {
				$text = "<strong>{$text}</strong>";
			}
			if( $reminder->emphasized ) {
				$text = "<em>{$text}</em>";
			}
			if( $reminder->underliend ) {
				$text = "<span style='text-decoration:underline;'>{$text}</span>";
			}
			$text = "<span style='color:#{$reminder->textColor};background-color:#{$reminder->backColor};padding:3px 10px;'>{$text}</span>";
			?>
			<li>
				<label>
				<input <?php checked( true, in_array( $reminder->ID, $checkedReminders ) ); ?> class="pre-publish-reminder-check" type="checkbox" name="pre-publish-reminders[]" id="pre-publish-reminder-<?php echo $reminder->ID; ?>" value="<?php echo $reminder->ID; ?>" /> 
				<?php echo $text; ?>
				</label>
			</li>
			<?php 
		} 
		?>
	</ol>
	<?php
}
?>