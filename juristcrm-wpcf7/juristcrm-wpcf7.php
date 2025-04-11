<?php
/**
 * @package JuristCRM
 * @version 1.0.0
 */
/*
Plugin Name: JuristCRM: Contact Form 7
Plugin URI: https://juristcrm.com/plugin/wordpress
Description: Интеграция JuristCRM с Contact Form 7
Author: SERKOR
Version: 1.0.0
Author URI: https://github.com/serkor
*/

if (!defined('ABSPATH')) {
    exit; // Защита от прямого доступа
}

// Проверка наличия Contact Form 7
add_action('admin_init', function () {
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>JuristCRM:</strong> Для работы требуется установленный плагин Contact Form 7.</p></div>';
        });
    }
});

// Страница настроек
add_action('admin_menu', function () {
    add_options_page('JuristCRM Settings', 'JuristCRM', 'manage_options', 'juristcrm-settings', 'juristcrm_settings_page');
});

function juristcrm_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Настройки интеграции с JuristCRM</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('juristcrm-settings-group');
            do_settings_sections('juristcrm-settings-group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">URL CRM</th>
                    <td><input type="text" name="juristcrm_url" value="<?php echo esc_attr(get_option('juristcrm_url')); ?>" size="50"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">API ключ</th>
                    <td><input type="text" name="juristcrm_api_key" value="<?php echo esc_attr(get_option('juristcrm_api_key')); ?>" size="50"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Соответствие полей (JSON)</th>
                    <td>
                        <textarea name="juristcrm_field_map" rows="10" cols="80"><?php echo esc_textarea(get_option('juristcrm_field_map')); ?></textarea><br>
                        <small>Пример: {"title":"your-name","email":"your-email","phone_1":"your-phone","info":"your-message"}</small>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    register_setting('juristcrm-settings-group', 'juristcrm_url');
    register_setting('juristcrm-settings-group', 'juristcrm_api_key');
    register_setting('juristcrm-settings-group', 'juristcrm_field_map');
});

// Отправка данных после отправки формы
add_action('wpcf7_before_send_mail', 'juristcrm_wpcf7_send', 10, 3);
function juristcrm_wpcf7_send($contact_form, &$abort, $submission)
{
    $url = get_option('juristcrm_url');
    $apiKey = get_option('juristcrm_api_key');
    $mapJson = get_option('juristcrm_field_map');
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
        CURLOPT_URL => rtrim($url, '/') . '/api/v1/appeals',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($crm_data),
        CURLOPT_HTTPHEADER => array(
            "Authorization: " . $apiKey
        ),
    ));

    curl_exec($curl);
    curl_close($curl);
}
