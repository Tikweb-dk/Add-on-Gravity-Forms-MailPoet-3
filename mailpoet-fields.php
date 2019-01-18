<?php

use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;

class GF_Field_Mailpoet extends GF_Field {

    public $type = 'mailpoet';

    public function get_form_editor_field_title() {
        return esc_attr__( 'Mailpoet Subscribe', 'add-on-gravity-forms-mailpoet' );
    }

    public function get_form_editor_button() {
        return array(
            'group' => 'advanced_fields',
            'text'  => $this->get_form_editor_field_title(),
        );
    }

    public function get_form_editor_field_settings() {
        return array(
            'conditional_logic_field_setting',
            'prepopulate_field_setting',
            'error_message_setting',
            'label_setting',
            'admin_label_setting',
            'rules_setting',
            'duplicate_setting',
            'description_setting',
            'css_class_setting',
            'choices_setting'
        );
    }

    public function is_conditional_logic_supported() {
        return true;
    }

    public function list_choice( $form_id, $field_id, $choices = array() ){

        $ret = '';
        $ret .= '<ul class="gfield_checkbox" id="input_'.$form_id.'_'.$field_id.'">';

        foreach ($choices as $key => $value) {

            $key += 2;
            
            $id = $form_id.'_'.$field_id.'_'.$key;
            
            $ret .= '<li class="gchoice_'.$id.'">';
            $ret .= '<input type="checkbox"';

            $ret .= 'name="input_' . $field_id . '.'.$key.'" id="choice_'.$id.'"';
            $ret .= 'value="'.$value['value'].'"';
            $ret .= 'style="margin-right:5px;"';
            $ret .= ' >';
            $ret .= '<label for="choice_'.$id.'" id="label_'.$id.'">';
            // $ret .= '<label for="input_' . $field_id . '.'.$key.'" >';
            $ret .= $value['text'];
            $ret .= '</label>';
            $ret .= '</li>';

            

        }

        $ret .= '</ul>';

        return $ret;
    }

    public function get_field_input( $form, $value = '', $entry = null ) {

        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor  = $this->is_form_editor();

        $form_id  = $form['id'];
        $field_id = intval( $this->id );

        $first = $last = $email = $phone = '';

        if ( is_array( $value ) ) {
            $first = esc_attr( rgget( $this->id . '.1', $value ) );
            $last  = esc_attr( rgget( $this->id . '.2', $value ) );
            $email = esc_attr( rgget( $this->id . '.3', $value ) );
            $phone = esc_attr( rgget( $this->id . '.4', $value ) );
        }

        $disabled_text = $is_form_editor ? "disabled='disabled'" : '';
        $class_suffix  = $is_entry_detail ? '_admin' : '';

        $first_tabindex = GFCommon::get_tabindex();
        $last_tabindex  = GFCommon::get_tabindex();
        $email_tabindex = GFCommon::get_tabindex();
        $phone_tabindex = GFCommon::get_tabindex();

        $required_attribute = $this->isRequired ? 'aria-required="true"' : '';
        $invalid_attribute  = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

        $first_markup = '<span id="input_' . $field_id . '_' . $form_id . '.1_container" class="attendees_first">';
        $first_markup .= '<input type="text" name="input_' . $field_id . '.1" id="input_' . $field_id . '_' . $form_id . '_1" value="' . $first . '" aria-label="First Name" ' . $first_tabindex . ' ' . $disabled_text . ' ' . $required_attribute . ' ' . $invalid_attribute . '>';
        $first_markup .= '<label for="input_' . $field_id . '_' . $form_id . '_1">First Name</label>';
        $first_markup .= '</span>';

        $last_markup = '<span id="input_' . $field_id . '_' . $form_id . '.2_container" class="attendees_last">';
        $last_markup .= '<input type="text" name="input_' . $field_id . '.2" id="input_' . $field_id . '_' . $form_id . '_2" value="' . $last . '" aria-label="Last Name" ' . $last_tabindex . ' ' . $disabled_text . ' ' . $required_attribute . ' ' . $invalid_attribute . '>';
        $last_markup .= '<label for="input_' . $field_id . '_' . $form_id . '_2">Last Name</label>';
        $last_markup .= '</span>';

        $email_markup = '<span id="input_' . $field_id . '_' . $form_id . '.3_container" class="attendees_email">';
        $email_markup .= '<input type="text" name="input_' . $field_id . '.3" id="input_' . $field_id . '_' . $form_id . '_3" value="' . $email . '" aria-label="Email" ' . $email_tabindex . ' ' . $disabled_text . ' ' . $required_attribute . ' ' . $invalid_attribute . '>';
        $email_markup .= '<label for="input_' . $field_id . '_' . $form_id . '_3">Email</label>';
        $email_markup .= '</span>';

        $phone_markup = '<span id="input_' . $field_id . '_' . $form_id . '.4_container" class="attendees_phone">';
        $phone_markup .= '<input type="text" name="input_' . $field_id . '.4" id="input_' . $field_id . '_' . $form_id . '_4" value="' . $phone . '" aria-label="Phone #" ' . $phone_tabindex . ' ' . $disabled_text . ' ' . $required_attribute . ' ' . $invalid_attribute . '>';
        $phone_markup .= '<label for="input_' . $field_id . '_' . $form_id . '_4">Phone #</label>';
        $phone_markup .= '</span>';

        // $css_class = $this->get_css_class();

        $markup_asdf = $this->list_choice( $form_id, $field_id, $this->choices );

        return "<div class='ginput_container ginput_container_checkbox'>
                    {$markup_asdf}
                </div>";
    }

    public function get_css_class() {
        $first_input = GFFormsModel::get_input( $this, $this->id . '.1' );
        $last_input  = GFFormsModel::get_input( $this, $this->id . '.2' );
        $email_input = GFFormsModel::get_input( $this, $this->id . '.3' );
        $phone_input = GFFormsModel::get_input( $this, $this->id . '.4' );

        $css_class           = '';
        $visible_input_count = 0;

        if ( $first_input && ! rgar( $first_input, 'isHidden' ) ) {
            $visible_input_count ++;
            $css_class .= 'has_first_name ';
        } else {
            $css_class .= 'no_first_name ';
        }

        if ( $last_input && ! rgar( $last_input, 'isHidden' ) ) {
            $visible_input_count ++;
            $css_class .= 'has_last_name ';
        } else {
            $css_class .= 'no_last_name ';
        }

        if ( $email_input && ! rgar( $email_input, 'isHidden' ) ) {
            $visible_input_count ++;
            $css_class .= 'has_email ';
        } else {
            $css_class .= 'no_email ';
        }

        if ( $phone_input && ! rgar( $phone_input, 'isHidden' ) ) {
            $visible_input_count ++;
            $css_class .= 'has_phone ';
        } else {
            $css_class .= 'no_phone ';
        }

        $css_class .= "gf_mailpoet_has_{$visible_input_count} ginput_container_attendees ";

        return trim( $css_class );
    }

    public function get_form_editor_inline_script_on_page_render() {

        // set the default field label for the field
        $script = sprintf( "function SetDefaultValues_%s(field) {
        field.label = '%s';
        field.inputs = [new Input(field.id + '.1', '%s'), new Input(field.id + '.2', '%s'), new Input(field.id + '.3', '%s'), new Input(field.id + '.4', '%s'),new Input(field.id + '.5', '%s'),new Input(field.id + '.6', '%s'),new Input(field.id + '.7', '%s'),new Input(field.id + '.8', '%s'),new Input(field.id + '.9', '%s'),new Input(field.id + '.10', '%s')];
        }", $this->type, $this->get_form_editor_field_title(), 'checkbox', 'checkbox', 'checkbox', 'checkbox','checkbox','checkbox','checkbox','checkbox','checkbox','checkbox' ) . PHP_EOL;

        return $script;
    }

    public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
        
        $ret = '<ul>';
        
        foreach ($value as $key => $value) {
            if ( !empty($value) ){

                $ret .= '<li>';
                $ret .= '<strong>'.$value.'</strong>';
                $ret .= '</li>';

            }
            
        }

        $ret .= '</ul>'; 

        return $ret;

    }

}

GF_Fields::register( new GF_Field_Mailpoet() );
