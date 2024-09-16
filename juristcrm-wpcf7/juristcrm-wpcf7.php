<?php
/**
 * @package JuristCRM
 * @version 1.0.0
 */

/*
Plugin Name: JuristCRM: Contact Form 7
Plugin URI: http://juristcrm.com/plugin/wordpress
Description: Integration of JuristCRM with Plugin Contact Form 7
Author: SERKOR
Version: 1.0.0
Author URI: https://github.com/serkor
*/

add_action('wpcf7_before_send_mail', 'juristcrm_wpcf7', 10, 3);
function juristcrm_wpcf7($contact_form, &$abort, $submission)
{
//  $form_id = $contact_form->id();
    $title = null;
    $phone = null;
    $email = null;
    $info = null;
    $organization_id = 1;
    $operator_id = 2;
    $note = null;

    $data = $submission->get_posted_data();

    if (isset($data['title'])) {
        $title = sanitize_text_field($data['title']);
    }
    if (isset($data['email'])) {
        $email = preg_replace("/[^0-9]/", "", substr($data['email'], 3));
    }
    if (isset($data['phone'])) {
        $phone = preg_replace("/[^0-9]/", "", substr($data['phone'], 3));
    }
    if (isset($data['subject'])) {
        $info = sanitize_text_field($data['subject']);
    }

    $crm_data = array(
        //Fields for CRM system
        'title' => $title,
        'phone_1' => $phone,
        'email' => $email,
        'info' => $info,
        'organization_id' => $organization_id,
        'operator_id' => $operator_id,
        'note' => $note,
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://test.juristcrm.com/api/v1/appeals',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $crm_data,
        CURLOPT_HTTPHEADER => array(
            "Authorization: pClaGZ3mqP2U43NBtGyJP6AtWLfMRJVmMLWWxT7i8YxjXaOFoy2zHeodwySP"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
