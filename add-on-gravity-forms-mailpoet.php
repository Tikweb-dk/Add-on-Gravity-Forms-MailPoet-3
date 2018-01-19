<?php
/**
 * Plugin Name:       Add-on Gravity Forms - Mailpoet 3
 * Description:       Add a MailPoet 3 signup field to your Gravity Forms.
 * Version:           1.0.2
 * Author:            Tikweb
 * Author URI:        http://www.tikweb.dk/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       add-on-gravity-forms-mailpoet
 * Domain Path:       /languages
*/


/*
Add-on Gravity Forms - MailPoet 3 is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Add-on Gravity Forms - MailPoet 3 is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Add-on Gravity Forms - MailPoet 3. If not, see http://www.gnu.org/licenses/gpl-2.0.txt.
*/

define( 'GF_NEW_MAILPOET_ADDON_VERSION', '1.0.0' );

add_action( 'gform_loaded', array( 'GF_New_MailPoet_Startup', 'load' ), 5 );

class GF_New_MailPoet_Startup {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gfnewmailpoetaddon.php' );

		GFAddOn::register( 'GFNEWMailPoetAddOn' );
	}

}

function gf_new_mailpoet_feed_addon() {
	return GFNEWMailPoetAddOn::get_instance();
}


add_action('admin_notices', 'gf_new_mailpoet_plugin_admin_notices');
function gf_new_mailpoet_plugin_admin_notices() {
	if ($notices = get_option('gf_new_mailpoet_plugin_deferred_admin_notices')) {
		foreach ($notices as $notice) {
			echo "<div class='notice notice-warning is-dismissable'><p>$notice</p></div>";
		}
		delete_option('gf_new_mailpoet_plugin_deferred_admin_notices');
	}
}

register_deactivation_hook(__FILE__, 'gf_new_mailpoet_plugin_deactivation');
function gf_new_mailpoet_plugin_deactivation() {
	delete_option('gf_new_mailpoet_plugin_deferred_admin_notices');
}
