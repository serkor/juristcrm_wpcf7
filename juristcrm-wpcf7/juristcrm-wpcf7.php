<?php
/**
 * @package JuristCRM
 * @version 2.0.0
 */

/*
Plugin Name: JuristCRM: Contact Form 7
Plugin URI: https://github.com/serkor/juristcrm_wpcf7/releases
Description: Extended integration of JuristCRM with Contact Form 7. Allows you to customize the field mapping individually for each Contact Form 7.
Author: SERKOR
Version: 1.0.2
Author URI: https://github.com/serkor
*/

if (!defined('ABSPATH')) {
    exit;
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');

add_action('admin_init', function () {
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>JuristCRM:</strong> Для работы вам необходимо установить плагин Contact Form 7.</p></div>';
        });
    }
});

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
        <h1>Настройки интеграции ваших форм с JuristCRM</h1>
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
                                <small>Например: https://company.juristcrm.com</small>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">API KEY</th>
                            <td><input type="text" name="juristcrm_api_key" value="<?php
                                echo esc_attr(get_option('juristcrm_api_key')); ?>" size="80"/><br/>
                                <small>Например:
                                    5ClaGZ36qP2U437BtGyJP6At8LfMR9VmMLWW0T7i8YgjXaOFhy2zHeodwy89</small><br/>
                                <small>* Узнать свой API KEY можно в настройках CRM (Система->Налаштування)</small>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="wp-admin-column">
                    <div>
                        <b>Параметры и значения для отправки форм в CRM</b> -
                        <a href="<?php
                        echo esc_attr(get_option('juristcrm_url')).'/crm#/doc/api-appeals'; ?>" target="_blank">
                            см. в документации детально</a>
                        <ul>
                            <li>1) <b>title</b> (VARCHAR, max:30) - Фамилия, Имя (обязательно)</li>
                            <li>2) <b>phone_1</b> (VARCHAR, max:16) - Телефон (обязательно)</li>
                            <li>3) <b>email</b> (VARCHAR, max:30) - Email (Не обязательно)</li>
                            <li>4) <b>info</b> (TEXT, max:600) - Проблема/Вопрос (Не обязательно)</li>
                            <li>4) <b>note</b> (VARCHAR, max:191) - Примечание/URL-адрес сайта (Не обязательно)</li>
                            <li>5) <b>organization_id</b> (INT, max:2) - ID организации (Не обязательно)</li>
                            <li>6) <b>region_id</b> (INT, max:2) - ID региона (Не обязательно)</li>
                            <li>7) <b>lawyer_id</b> (INT, max:5) - ID сотрудника (Не обязательно)</li>
                            <li>8) <b>operator_id</b> (INT, max:5) - ID сотрудника (Не обязательно)</li>
                            <li>9) <b>important</b> (INT, max:1) - Важное (1) - Не важное (0) (Не обязательно)
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <hr>
            <h2>Настройка форм (Синтакси JSON)</h2>
            <?php
            foreach ($forms as $form):
                $form_id = $form->id();
                $form_title = $form->title();
                $option_key = "juristcrm_field_map_form_$form_id";
                $saved_map = get_option($option_key);
                ?>
                <h5><?php
                    echo 'Ваша форма: '.esc_html($form_title); ?> (ID: <?php
                    echo $form_id; ?>)</h5>
                <textarea name="<?php
                echo $option_key; ?>" rows="2" cols="100"><?php
                    echo esc_textarea($saved_map); ?></textarea>
                <br><small>Например (JSON): { "title":"your-name", "email":"your-email", "phone_1":"your-phone",
                "info":"your-message","note":"yoursite.com" }</small>
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
            width: 48%;
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
    error_log("POSTED DATA: ".print_r($data, true));
    $crm_data = [];

    foreach ($map as $crmField => $formField) {
        if (is_string($formField) && isset($data[$formField])) {
            $value = $data[$formField];

            if (is_array($value)) {
                $value = reset($value);
            }

            $crm_data[$crmField] = sanitize_text_field($value);
        } elseif (!is_array($formField) && !is_object($formField)) {
            $crm_data[$crmField] = $formField;
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
    error_log("RESPONSE FROM CRM: ".$response);
    curl_close($curl);
}
