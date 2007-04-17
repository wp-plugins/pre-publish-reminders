=== Plugin Name ===
Contributors: nickohrn
Donate link: http://nickohrn.com
Tags: admin, reminders
Requires at least: 2.0

Tested up to: 2.1.3
Stable tag: 1.06

This plugin displays a configurable list of reminders directly below the submit buttons in the WordPress Write Post interface.

== Description ==

Lorelle, of Lorelle on WordPress fame requested a simple reminder plugin that could be used from the administration
panel within WordPress.  I decided to deliver with this little piece of code.

Your reminders can be input through the WordPress administration interface, and you can format them in a variety
of styles.  You can change the text color, background color, and make the text bold or italic.

== Installation ==

Installation is super simple.  Follow these steps:

1. Upload nfoppreminder.php to your '/wp-content/plugins/' directory.
1. Activate your plugin through the 'Plugins' menu in WordPress.
1. Manage your plugin under the manage tab in the administration menu.

== Frequently Asked Questions ==

I haven't been asked any questions, but if I were someone using this plugin I would probably say:

1. Why does the color picker pop up in a new window instead of a in-page div or something like that?

Answer: The ColorPicker included with the WordPress distribution (while very nice) does not position the in-page
divs like you think it might.  In both the default WordPress administration theme and the Tiger Admin theme the placement
is totally out of whack.  To work around this, I used the pop-up window option of that same library as a temporary
solution until I find a javascript color picker that I like or I create my own simple one.  Until then, this will have
to do.

== Screenshots ==

1. The administration screen in action.
2. The awesome list displayed below your submit button.