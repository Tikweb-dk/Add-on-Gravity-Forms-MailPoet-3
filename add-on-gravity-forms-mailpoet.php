<?php
/**
 * Plugin Name:       Add-on Gravity Forms - Mailpoet 3
 * Description:       Add a MailPoet 3 signup field to your Gravity Forms.
 * Version:           1.1.1
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

// If this file is called directly, abort.
if(!defined( 'WPINC' )){
	exit;
}

/**
 * Include plugin.php to detect plugin.
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

/**
 * Check MailPoet active
 * Prerequisite
 */
if(!is_plugin_active('mailpoet/mailpoet.php')){
	add_action('admin_notices', function(){
		?>
		<div class="error">
			<p>
			<?php
				$name = 'Add-on Gravity Forms - Mailpoet 3';
				$mp_link = '<a href="https://wordpress.org/plugins/mailpoet/" target="_blank">MailPoet</a>';
				printf(
					__('%s plugin requires %s plugin, Please activate %s first to using %s.', 'add-on-gravity-forms-mailpoet'),
					$name,
					$mp_link,
					$mp_link,
					$name
				);
			?>
			</p>
		</div>
		<?php
	});
	return;	// If not then return
}

/**
 * After gravity form loaded.
 */
add_action( 'gform_loaded', array( 'GF_New_MailPoet_Startup', 'load' ), 5 );

class GF_New_MailPoet_Startup {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once plugin_dir_path( __FILE__ ) .'/class-gfnewmailpoetaddon.php';

		GFAddOn::register( 'GFNEWMailPoetAddOn' );

		// include mailpoet field
		require_once plugin_dir_path( __FILE__ ) .'/mailpoet-fields.php';
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

/**
 * Mailpoet class load 
 */
use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;

/**
 * Add mailpoet list to choice.
 */
add_action('gform_predefined_choices','mailpoet_predefiend_list' );
function mailpoet_predefiend_list( $choices )
{
    $ret = array();

    $segments = Segment::where_not_equal('type', Segment::TYPE_WP_USERS)->findArray();

    foreach ($segments as $s_key => $s_val) {

        $ret['Mailpoet List'][] = $s_val['name'].'|'.$s_val['id'];

    }

   foreach ($choices as $key => $value) {
        $ret[$key] = $value;
   }

    return $ret;
}

/**
 * Set default input
 */
add_action( 'gform_editor_js_set_default_values', 'mailpoet_list_set_default' );
function mailpoet_list_set_default()
{
    $segments = Segment::where_not_equal('type', Segment::TYPE_WP_USERS)->findArray();
    
    $choice = '[';    
    foreach ($segments as $key => $value) {
        $choice .= 'new Choice("'.$value["name"].'","'.$value["id"].'"), ';
    }

    $choice .= '];';

    if ( empty($segments) ){
        $choice = "[new Choice('List one'), new Choice('List two'), new Choice('Please set a list')];";
    }

    ?>

    case "mailpoet":
        field.label = "Subscribe";
        field.choices = <?= $choice; ?>
        break;
    <?php
}


/**
 * Process form submission, make subscriber, etc.
 */
add_action('gform_after_submission','process_mailpoet_list', 10, 2);
function process_mailpoet_list( $entry, $form )
{

    if ( !is_array( $entry) || !is_array($form) || empty( $entry) || empty( $form) ){
        return;
    }

    if ( !isset($form['fields']) ){
        return;
    }

    // extract email
    $email_key = array_search('email', array_column($form['fields'], 'type'));
    if ( false === $email_key ){
        $email_key = array_search('email', array_column(array_map('get_object_vars', $form['fields']), 'type'));
    }


    if ( !is_integer($email_key) ){
        return;
    }

    $email_id = $form['fields'][$email_key]->id;
    $email = rgar( $entry, $email_id );


    if ( empty($email) ){
        return;
    }

    $subscriber = Subscriber::findOne( $email );


    if ( false !== $subscriber ){
        $segments = $subscriber->segments()->findArray();

        if ( !empty($segments) ){
            return;
        }
    }

    $subscriber_data = array(
        'email' => $email
    );

    // extract name
    $name_key = array_search('name', array_column($form['fields'], 'type'));
    if ( false === $name_key ){
        $name_key = array_search('name', array_column(array_map('get_object_vars', $form['fields']), 'type'));
    }


    if ( is_integer( $name_key ) ){

        $fname_id = array_search('First', array_column($form['fields'][$name_key]->inputs, 'label'));
        $fname_id = $form['fields'][$name_key]->inputs[$fname_id]['id'];

        $lname_id = array_search('Last', array_column($form['fields'][$name_key]->inputs, 'label'));
        $lname_id = $form['fields'][$name_key]->inputs[$lname_id]['id'];

        $first_name = rgar( $entry, $fname_id );
        $last_name = rgar( $entry, $lname_id );

        $subscriber_data['first_name'] = $first_name;
        $subscriber_data['last_name'] = $last_name;

    }

    // extract mailpoet list ids
    $mp_key = array_search('mailpoet', array_column($form['fields'], 'type'));
    if ( false === $mp_key ){
        $mp_key = array_search('mailpoet', array_column(array_map('get_object_vars', $form['fields']), 'type'));
    }

    if ( !is_integer( $mp_key) ){
        return;
    }

    $mp_id = (array) $form['fields'][$mp_key];
    $mp_id = array_column($mp_id['inputs'], 'id');


    $mp_list = [];

    foreach ($mp_id as $key => $value) {
        $lst = rgar( $entry, $value );

        if ( !empty($lst) ){
            
            if ( is_integer($lst) || is_numeric($lst) ){
                
                $mp_list[] = $lst;

            } else {

                $list = Segment::where('name', $lst)->findArray();

                if ( !empty($list) ){
                    $list = array_shift($list);
                    $mp_list[] = isset($list['id']) ? $list['id'] : null;
                }

            }
        }
        
    }

    // subscribe to 
    if ( !empty($mp_list) ){
        
        Subscriber::subscribe( $subscriber_data , $mp_list );

    }
}
