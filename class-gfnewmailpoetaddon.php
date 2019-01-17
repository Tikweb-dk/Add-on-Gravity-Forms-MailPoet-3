<?php

GFForms::include_feed_addon_framework();

class GFNEWMailPoetAddOn extends GFFeedAddOn {
    protected $_version = GF_NEW_MAILPOET_ADDON_VERSION;
    protected $_min_gravityforms_version = '1.9.16';
    protected $_slug = 'add-on-gravity-forms-mailpoet';
    protected $_path = 'add-on-gravity-forms-mailpoet/gravity-forms-mailpoet.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Add-on Gravity Forms - MailPoet 3';
    protected $_short_title = 'Mailpoet';

    private static $_instance = null;

    /**
     * Get an instance of this class.
     *
     * @return GFSimpleFeedAddOn
     */
    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new GFNEWMailPoetAddOn();
        }

        return self::$_instance;
    }

    public function init() {

        parent::init();

        // Supports logging
        add_filter('gform_logging_supported', array($this, 'set_logging_supported'));

        if( basename($_SERVER['PHP_SELF']) == "plugins.php" ) {
            //loading translations
            load_plugin_textdomain('add-on-gravity-forms-mailpoet', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages');
        }

        // Hide plugin_page if already shown
        if( get_option('gf_new_mailpoet_plugin_page') ){
            add_filter('gform_addon_navigation', array($this, 'remove_plugin_page_menu'));
        }
    }

    /**
     * Create a custom page to explain the upgrade process
     */
    public function plugin_page() {
        // Set option to only display plugin page once
        update_option('gf_new_mailpoet_plugin_page', true);
        echo '<h3>'.__('Where did my feeds go?', 'add-on-gravity-forms-mailpoet').'</h3>';
        echo '<p>'.__('Your feeds for MailPoet can now be found under each form, under <strong>Form Settings -> MailPoet</strong>.', 'add-on-gravity-forms-mailpoet'). '</p>';
        echo '<p><a class="button-primary" href="'.admin_url('?page=gf_edit_forms&view=settings&subview=gravity-forms-mailpoet').'">'.__('Add your feeds now', 'add-on-gravity-forms-mailpoet').'</a></p>';
    }

    /**
     * Remove plugin page from menu
     */
    public function remove_plugin_page_menu($menu){
        foreach( $menu as $k=>$v ){
            if( $v['name'] == 'add-on-gravity-forms-mailpoet' ){
                unset($menu[$k]);
                return $menu;
            }
        }
        return $menu;
    }

    /**
     * Add subscriber info to the desired lists when submission is complete.
     */
    public function process_feed( $feed, $entry, $form ) {
        if( !$this->is_mailpoet_installed() ){
            return;
        }

        $feedName  = $feed['meta']['feedname'];

        // Email validation option
        $skipEmalValidation  = isset($feed['meta']['skip_mailpoet_email_validation']) && $feed['meta']['skip_mailpoet_email_validation'] == '1';
        
        // Get out of here if no lists are specified
        if( !is_array($feed['meta']['mailpoetlist']) ){
            return;
        }
        $mailpoetlists = array_keys(array_filter($feed['meta']['mailpoetlist']));

        // Retrieve the name => value pairs for all fields mapped in the 'mappedfields' field map.
        $field_map = $this->get_field_map_fields( $feed, 'mappedfields' );

        // Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
        $merge_vars = array();
        foreach ( $field_map as $name => $field_id ) {

            // Get the field value for the specified field id
            $merge_vars[ $name ] = $this->get_field_value( $form, $entry, $field_id );

        }

        if( empty($merge_vars['email']) ){
            return;
        }

        $subscriber_data = array(
          'email' => $merge_vars['email'],
          'first_name' => $merge_vars['first_name'],
          'last_name' => $merge_vars['last_name'],
        );

        $options = array();
        
        if ($skipEmalValidation) {
            $options['send_confirmation_email'] = false;
            $subscriber_data['status'] = 'subscribed';
        }

        try {
          $subscriber_data = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, $mailpoetlists, $options);
        } catch(Exception $exception) {
            
            if ( 'This subscriber already exists.' == $exception->getMessage() ){
            
                try {

                    $subscriber = \MailPoet\API\API::MP('v1')->subscribeToLists($subscriber_data['email'], $mailpoetlists, $options);
                  
                } catch(Exception $exception) {
                    
                }

            } else {
                
            }
        }

    }

    /**
     * Configures the settings which should be rendered on the feed edit page in the Form Settings > Simple Feed Add-On area.
     *
     * @return array
     */
    public function feed_settings_fields() {
        $lists = $this->setup_mailpoet_lists_array();

        return array(
            array(
                'title'  => esc_html__( 'MailPoet Feed Settings', 'add-on-gravity-forms-mailpoet' ),
                'fields' => array(
                    array(
                        'label'   => esc_html__( 'Feed name', 'add-on-gravity-forms-mailpoet' ),
                        'type'    => 'text',
                        'name'    => 'feedname',
                        'class'   => '',
                    ),
                    array(
                        'name'      => 'mappedfields',
                        'label'     => esc_html__( 'Map Fields', 'add-on-gravity-forms-mailpoet' ),
                        'type'      => 'field_map',
                        'tooltip'   => esc_html__( 'Associate your MailPoet newsletter questions to the appropriate Gravity Form fields by selecting.', 'add-on-gravity-forms-mailpoet'),
                        'field_map' => array(
                            array(
                                'name'     => 'first_name',
                                'label'    => esc_html__( 'First Name', 'add-on-gravity-forms-mailpoet' ),
                                'required' => 0,
                            ),
                            array(
                                'name'       => 'last_name',
                                'label'      => esc_html__( 'Last Name', 'add-on-gravity-forms-mailpoet' ),
                                'required'   => 0,
                            ),
                            array(
                                'name'       => 'email',
                                'label'      => esc_html__( 'Email', 'add-on-gravity-forms-mailpoet' ),
                                'required'   => 0,
                                'field_type' => array('email', 'hidden'),
                            ),
                        ),
                    ),
                    array(
                        'label'   => esc_html__( 'Email validation', 'add-on-gravity-forms-mailpoet' ),
                        'type'   => 'checkbox',
                        'name'  => 'email_validation',
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'Do not send MailPoet email validation and set directly contacts as subscribed', 'add-on-gravity-forms-mailpoet' ),
                                'name'  => 'skip_mailpoet_email_validation',
                                'tooltip'   => esc_html__( 'If checked, subscribers won\'t receive MailPoet confirmation email and will be automatically added to your lists with a "Subscribed" status.', 'add-on-gravity-forms-mailpoet'),
                            ),
                        )
                    ),
                    $lists,
                    array(
                        'name'           => 'condition',
                        'label'          => esc_html__( 'Condition', 'add-on-gravity-forms-mailpoet' ),
                        'type'           => 'feed_condition',
                        'checkbox_label' => esc_html__( 'Enable Condition', 'add-on-gravity-forms-mailpoet' ),
                        'instructions'   => esc_html__( 'Process this feed if', 'add-on-gravity-forms-mailpoet' ),
                    ),
                ),
            ),
        );
    }

    /**
     * Configures which columns should be displayed on the feed list page.
     *
     * @return array
     */
    public function feed_list_columns() {
        return array(
            'feedname'       => esc_html__( 'Name', 'add-on-gravity-forms-mailpoet' ),
            'mailpoetlists'  => esc_html__( 'MailPoet Lists', 'add-on-gravity-forms-mailpoet' ),
        );
    }

    /**
     * Format the value to be displayed in the mailpoetlists column.
     *
     * @param array $feed The feed being included in the feed list.
     *
     * @return string
     */
    public function get_column_value_mailpoetlists( $feed ) {
        $feed_list = rgars($feed, 'meta/mailpoetlist');
        $lists = $this->get_mailpoet_lists();
        $list_names = array();
        foreach( $lists as $l ){
            if( array_key_exists($l['list_id'], $feed_list) && $feed_list[$l['list_id']] == 1 ) {
                $list_names[] = $l['name'];
            }
        }
        return implode(', ', $list_names);
    }

    public function get_mailpoet_lists() {
        $mailpoet_lists = array();
        $subscription_lists = \MailPoet\API\API::MP('v1')->getLists();
        foreach ($subscription_lists as $list) {
            $mailpoet_lists[] = array('list_id' => $list['id'], 'name' => $list['name']);
        }
        return $mailpoet_lists;
    }

    private function setup_mailpoet_lists_array() {
        $lists = $this->get_mailpoet_lists();

        $list_array = array(
            'name'    => 'mailpoetlists',
            'label'   => esc_html__( 'MailPoet Lists', 'add-on-gravity-forms-mailpoet' ),
            'type'    => 'checkbox',
            'tooltip' => esc_html__( 'Select the MailPoet lists you would like to add your contacts to.', 'add-on-gravity-forms-mailpoet' ),
            'choices' => array(),
        );
        if( !$lists ) {
            self::log_debug("Could not load MailPoet lists.");
            $list_array['choices'][] = array('label' => esc_html__('Could not load MailPoet lists.', 'add-on-gravity-forms-mailpoet'));

        } else {
            foreach ($lists as $l){
                $list_array['choices'][] = array(
                    'label' => $l['name'],
                    'name' => 'mailpoetlist['.$l['list_id'].']',
                );
            }
        }
        return $list_array;
    }

    private function is_mailpoet_installed(){
        return class_exists('\MailPoet\API\API');
    }

}
