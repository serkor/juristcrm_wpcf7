<?php
/**
 * @package JuristCRM
 * @version 1.0.3
 */

/*
Plugin Name: JuristCRM: Contact Form 7
Plugin URI: https://github.com/serkor/juristcrm_wpcf7/releases
Description: Extended integration of JuristCRM with Contact Form 7. Allows you to customize the field mapping individually for each Contact Form 7.
Author: SERKOR
Version: 1.0.3
Author URI: https://github.com/serkor
*/

if (!defined('ABSPATH')) {
    exit;
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');

add_action('admin_init', function () {
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>JuristCRM:</strong> Для продовження налаштування необхідно встановити плагін Contact Form 7.</p><p>Плагін працює тільки якщо форми на сайті працюють через Contact Form 7. В іншому випадку використовуйте скрипт для надсилання даних. Отримати скрипт можна в CRM, <strong>Система->Налаштування->Документація</strong></p>
</div>';
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
        <h1>Налаштування інтеграції ваших форм із JuristCRM</h1>
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
                                <small>Наприклад: https://company.juristcrm.com</small>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">API KEY</th>
                            <td><input type="text" name="juristcrm_api_key" value="<?php
                                echo esc_attr(get_option('juristcrm_api_key')); ?>" size="80"/><br/>
                                <small>Наприклад:
                                    5ClaGZ36qP2U437BtGyJP6At8LfMR9VmMLWW0T7i8YgjXaOFhy2zHeodwy89</small><br/>
                                <small>* Узнать свой API KEY можно в настройках CRM (Система->Налаштування->API)</small>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="wp-admin-column">
                    <div>
                        <b>Параметри та значення для надсилання форм у CRM</b> -
                        <a href="<?php
                        echo esc_attr(get_option('juristcrm_url')).'/crm#/doc/api-appeals'; ?>" target="_blank">
                            див. у документації детально</a>
                        <ul>
                            <li>-><b>title</b> (VARCHAR, max:30) - Прізвище, Ім'я (Обов'язково)</li>
                            <li>-><b>phone_1</b> (VARCHAR, max:20, format: 380931234567) - Телефон UA (Не обов'язково)
                            </li>
                            <li>-><b>phone_2</b> (VARCHAR, max:20, format: 480931234544) - Телефон EN (Не обов'язково)
                            </li>
                            <li>-><b>email</b> (VARCHAR, max:30) - Email (Не обов'язково)</li>
                            <li>-><b>info</b> (TEXT, max:600) - Проблема/Питання (Не обов'язково)</li>
                            <li>-><b>note</b> (VARCHAR, max:191) - Нотатка/URL сайту (Не обов'язково)</li>
                            <li>-><b>organization_id</b> (INT, max:2) - ID организации (Не обов'язково)</li>
                            <li>-><b>region_id</b> (INT, max:2) - ID региона (Не обов'язково)</li>
                            <li>-><b>lawyer_id</b> (INT, max:5) - Юрист (Співробітник ID) (Не обов'язково)</li>
                            <li>-><b>operator_id</b> (INT, max:5) - Оператор (Співробітник ID) (Не обов'язково)</li>
                            <li>-><b>important</b> (INT, max:1) - Важливо (1) - Не важливо (0) (Не обов'язково)
                            <li>-><b>source_id</b> (INT, max:10) - Джерело (Не обов'язково)
                            <li>-><b>status_id</b> (INT, max:10) - Статус (Не обов'язково)
                            <li>-><b>state_id</b> (INT, max:10) - Стан (Не обов'язково)
                            <li>-><b>start</b> (VARCHAR, max:20, format: 2023-09-12 11:00) - Дата запису (Не
                                обов'язково)
                            <li>-><b>utms[source]</b> (VARCHAR, max:50) - Джерело трафіку (Не обов'язково)
                            <li>-><b>utms[medium]</b> (VARCHAR, max:50) - Тип реклами: cpc, banner, email (Не
                                обов'язково)
                            <li>-><b>utms[campaign]</b> (VARCHAR, max:50) - Назва кампанії (Не обов'язково)
                            <li>-><b>utms[term]</b> (VARCHAR, max:50) - Ключове слово (Не обов'язково)
                            <li>-><b>utms[content]</b> (VARCHAR, max:50) - Оголошення (Не обов'язково)
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <hr>
            <h2>Налаштування форм (Синтаксис JSON)</h2>
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
                <br><small>Наприклад (JSON): { "title":"your-name", "email":"your-email", "phone_1":"your-phone",
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
