<?php
/*
Plugin name: Birthday Message
Description: plugin to send birthday message for customer.
Author: Webgensis Team
*/
if (!defined('ABSPATH')) exit;
// Add Script and Style
function birthday_message_enqueue_script()
	{
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('birthday-message-script', plugin_dir_url(__FILE__) . 'assets/js/birthday-message.js');
		wp_enqueue_style('datepicker-styles', plugin_dir_url(__FILE__) . 'assets/css/jquery-ui-datepicker.min.css');
	}
add_action('wp_enqueue_scripts', 'birthday_message_enqueue_script');
// Set cron job for birthday email send
if (!wp_next_scheduled('mid_night_birthday_action_hook'))
	{
		wp_schedule_event(strtotime('tomorrow') , 'daily', 'mid_night_birthday_action_hook');
	}
add_action('mid_night_birthday_action_hook', 'send_birthday_email');
function send_birthday_email()
	{
		$args = array(
				'role' => 'Customer',
		);
		$data_user = get_users($args);
		foreach($data_user as $data_users)
			{
				$user_email = $data_users->user_email;
				$user_name = $data_users->user_nicename;
				$user_info = get_user_meta($data_users->id);
				$user_dob = date('Ymd', strtotime($user_info[dob][0]));
				$currnt_date = date('Ymd');
				if ($currnt_date == $user_dob)
					{
						$to = $user_email;
						$subject = "Happy Birthday - " . $user_name;
						$body = get_option('birthday_message_info');
						$headers = array(
								'Content-Type: text/html; charset=UTF-8'
						);
						wp_mail($to, $subject, $body, $headers);
					}
			}
	}
// Add Date of birth filed on customer register page
function woocommer_birthday_field()
	{
		echo '<p class="form-row form-row-wide">';
		echo '<label for="reg_dob">' . __('Date Of Birth', 'woocommerce') . '<span class="required">*</span></label>';
		echo '<input type="text" class="input-text" name="dob" id="reg_dob" value="' . esc_attr($_POST['dob']) . '" />';
		echo '</p>';
	}
add_action('woocommerce_register_form_start', 'woocommer_birthday_field');
function wooc_validate_extra_register_fields($username, $email, $validation_errors)
	{
		if (isset($_POST['dob']) && empty($_POST['dob']))
			{
				$validation_errors->add('dob_error', __('Please Enter Date Of Birth.', 'woocommerce'));
			}
	}
add_action('woocommerce_register_post', 'wooc_validate_extra_register_fields', 10, 3);
function wooc_save_extra_register_fields($customer_id)
	{
		if (isset($_POST['dob']))
			{
				update_user_meta($customer_id, 'dob', date('Ymd', strtotime($_POST['dob'])));
			}
	}
add_action('woocommerce_created_customer', 'wooc_save_extra_register_fields');
// Plugin option page for message content
add_action('admin_menu', 'birthday_message_menu');
if (!function_exists("birthday_message_menu"))
	{
		function birthday_message_menu()
			{
				$page_title = 'Birthday Message';
				$menu_title = 'Birthday Message';
				$capability = 'manage_options';
				$menu_slug = 'birthday_message_info';
				$function = 'birthday_message_page';
				$icon_url = 'dashicons-media-code';
				$position = 4;
				add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
				add_action('admin_init', 'update_birthday_message_info');
			}
	}
if (!function_exists("update_birthday_message_info"))
	{
		function update_birthday_message_info()
			{
				register_setting('birthday_message_info-settings', 'birthday_message_info');
			}
	}
function birthday_message_page()
	{
		echo '<div class="wrap">';
		echo '<h1>' . __('Birthday Message Content', 'woocommerce') . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields('birthday_message_info-settings');
		do_settings_sections('birthday_message_info-settings');
		$content = get_option('birthday_message_info');
		$editor_id = 'birthday_message_info';
		wp_editor($content, $editor_id);
		submit_button();
		echo '</form>';
		echo '</div>';
	}
// Add Date of birth filed in woocommerce my account page
add_action('woocommerce_edit_account_form', 'birthday_message_woocommerce_edit_account_form');
add_action('woocommerce_save_account_details', 'birthday_message_woocommerce_save_account_details');
function birthday_message_woocommerce_edit_account_form()
	{
		$user_id = get_current_user_id();
		$user = get_userdata($user_id);
		if (!$user) return;
		$dob = get_user_meta($user_id, 'dob', true);
		echo '<fieldset>';
		echo '<legend>' . __('Date of birth', 'woocommerce') . '</legend><p>' . __('Fill in this information about your date of birth.', 'woocommerce') . '</p>';
		echo '<p class="form-row form-row-thirds"><input type="text" name="dob" value="' . esc_attr($dob) . '" class="input-text"  id="reg_dob"/></p>';
		echo '</fieldset>';
	}
function birthday_message_woocommerce_save_account_details($user_id)
	{
		update_user_meta($user_id, 'dob', date('Ymd', strtotime($_POST['dob'])));
	}
