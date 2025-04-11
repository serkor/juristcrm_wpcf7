<?php
/**
 * @package JuristCRM
 * @version 2.0.0
 */

/*
Plugin Name: JuristCRM: Contact Form 7
Plugin URI: https://juristcrm.com/opportunities/restapi
Description: Extended integration of JuristCRM with Contact Form 7. Allows you to customize the field mapping individually for each Contact Form 7.
Author: SERKOR
Version: 1.0.1
Author URI: https://github.com/serkor
*/

if (!defined('ABSPATH')) {
    exit;
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');

add_action('admin_init', function () {
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>JuristCRM:</strong> To work, you need the Contact Form 7 plugin installed.</p></div>';
        });
    }
});

// Страница настроек
//add_action('admin_menu', function () {
//    add_menu_page(
//        'JuristCRM Settings',
//        'JuristCRM',
//        'manage_options',
//        'juristcrm-settings',
//        'juristcrm_settings_page'
//    );
//});

// Добавление в меню CF7
add_action('admin_menu', function () {
    if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        add_submenu_page(
            'wpcf7',
            'JuristCRM Integration',
            'JuristCRM',
            'manage_options',
            'juristcrm-cf7',
            'juristcrm_cf7_settings_page'
        );
    }
});

function juristcrm_cf7_settings_page()
{
    $forms = WPCF7_ContactForm::find();
    ?>
    <div class="wrap">
        <h1>Integration settings with JuristCRM</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('juristcrm-settings-group');
            do_settings_sections('juristcrm-settings-group');
            ?>
            <div class="wp-admin-columns">
                <div class="wp-admin-column">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">URL CRM</th>
                            <td><input type="text" name="juristcrm_url" value="<?php
                                echo esc_attr(get_option('juristcrm_url')); ?>" size="50"/><br/>
                                <small>For example: https://company.juristcrm.com</small>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">API key</th>
                            <td><input type="text" name="juristcrm_api_key" value="<?php
                                echo esc_attr(get_option('juristcrm_api_key')); ?>" size="50"/><br/>
                                <small>For example:
                                    5ClaGZ36qP2U437BtGyJP6At8LfMR9VmMLWW0T7i8YgjXaOFhy2zHeodwy89</small>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="wp-admin-column">
                    <div>
                        <b>JuristCRM Parameters and Values</b> -
                        <a href="<?php
                        echo esc_attr(get_option('juristcrm_url')).'/crm#/doc/api-appeals'; ?>" target="_blank">see
                            in Documentation</a>
                        <ul>
                            <li>1) <b>title</b> (VARCHAR, max:50) - Last Name, First Name (Required)</li>
                            <li>2) <b>phone_1</b> (VARCHAR, max:20) - Phone (Required)</li>
                            <li>3) <b>email</b> (VARCHAR, max:20) - Email (Optional)</li>
                            <li>4) <b>info</b> (TEXT, max:600) - Problem/Question (Optional)</li>
                            <li>5) <b>organization_id</b> (INT, max:2) - Organization ID (Optional)</li>
                            <li>6) <b>region_id</b> (INT, max:2) - Region ID (Optional)</li>
                            <li>7) <b>lawyer_id</b> (INT, max:20) - Lawyer (Employee ID) (Optional)</li>
                            <li>8) <b>operator_id</b> (INT, max:20) - Operator (Employee ID) (Optional)</li>
                            <li>9) <b>important</b> (INT, max:1) - Important (1) - Not important (0) (Optional)</li>
                        </ul>
                    </div>
                </div>
            </div>
            <hr>
            <h2>Customizing forms</h2>
            <?php
            foreach ($forms as $form):
                $form_id = $form->id();
                $form_title = $form->title();
                $option_key = "juristcrm_field_map_form_$form_id";
                $saved_map = get_option($option_key);
                ?>
                <h3><?php
                    echo 'Your form: '.esc_html($form_title); ?> (ID: <?php
                    echo $form_id; ?>)</h3>
                <textarea name="<?php
                echo $option_key; ?>" rows="2" cols="100"><?php
                    echo esc_textarea($saved_map); ?></textarea>
                <br><small>For example (JSON): { "title":"your-name", "email":"your-email", "phone_1":"your-phone",
                "info":"your-message" }</small>
                <hr>
            <?php
            endforeach; ?>

            <?php
            submit_button(); ?>
        </form>
    </div>

    <style>
        .wp-admin-columns {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .wp-admin-column {
            width: 48%; /* Это аналогично col-6 */
        }
    </style>

    <?php
}

add_action('admin_init', function () {
    register_setting('juristcrm-settings-group', 'juristcrm_url');
    register_setting('juristcrm-settings-group', 'juristcrm_api_key');

    $forms = WPCF7_ContactForm::find();
    foreach ($forms as $form) {
        register_setting('juristcrm-settings-group', 'juristcrm_field_map_form_'.$form->id());
    }
});

// Отправка данных в CRM
add_action('wpcf7_before_send_mail', 'juristcrm_advanced_send', 10, 3);
function juristcrm_advanced_send($contact_form, &$abort, $submission)
{
    $form_id = $contact_form->id();
    $url = get_option('juristcrm_url');
    $apiKey = get_option('juristcrm_api_key');
    $mapJson = get_option('juristcrm_field_map_form_'.$form_id);
    $map = json_decode($mapJson, true);

    if (!$url || !$apiKey || !$map || !is_array($map)) {
        return;
    }

    $data = $submission->get_posted_data();
    $crm_data = [];

    foreach ($map as $crmField => $formField) {
        if (isset($data[$formField])) {
            $crm_data[$crmField] = sanitize_text_field($data[$formField]);
        }
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => rtrim($url, '/').'/api/v1/appeals/store',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($crm_data),
        CURLOPT_HTTPHEADER => array("Authorization: $apiKey"),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
}
