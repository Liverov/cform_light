<?php
/*
Plugin Name: Contact Form Light
Plugin URI: https://админсайта.рф/cform_light
Description: Так всем нужная, простая контактная форма из двух полей 
Version: 1.0
Author: Антон Ливеров
Author URI: https://админсайта.рф
*/

/*  Copyright 2020  Anton Liverov  (email: liverov@yandex.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function cform_light_get_the_ip() {
  if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
    return $_SERVER["HTTP_X_FORWARDED_FOR"];
  } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
    return $_SERVER["HTTP_CLIENT_IP"];
  } else {
    return $_SERVER["REMOTE_ADDR"];
  }
}

function cform_light_sc( $atts ) {
  extract( shortcode_atts( array(
    // if you don't provide an e-mail address, the shortcode will pick the e-mail address of the admin:
    "email" => get_bloginfo( 'admin_email' ),
    "subject" => "",
    "label_name" => "Your Name",
    "label_email" => "Your E-mail Address",
    "label_subject" => "Subject",
    "label_message" => "Your Message",
    "label_submit" => "Submit",
    // the error message when at least one of the required fields are empty:
    "error_empty" => "Please fill in all the required fields.",
    // the error message when the e-mail address is not valid:
    "error_noemail" => "Please enter a valid e-mail address.",
    // and the success message when the e-mail is sent:
    "success" => "Thanks for your e-mail! We'll get back to you as soon as we can."
  ), $atts ) );

  // if the <form> element is POSTed, run the following code
  if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    $error = false;
    // set the "required fields" to check
    $required_fields = array( "your_name", "email", "message", "subject" );

    // this part fetches everything that has been POSTed, sanitizes them and lets us use them as $form_data['subject']
    foreach ( $_POST as $field => $value ) {
        if ( get_magic_quotes_gpc() ) {
            $value = stripslashes( $value );
        }
        $form_data[$field] = strip_tags( $value );
    }

    // if the required fields are empty, switch $error to TRUE and set the result text to the shortcode attribute named 'error_empty'
    foreach ( $required_fields as $required_field ) {
        $value = trim( $form_data[$required_field] );
        if ( empty( $value ) ) {
            $error = true;
            $result = $error_empty;
        }
    }

    // and if the e-mail is not valid, switch $error to TRUE and set the result text to the shortcode attribute named 'error_noemail'
    if ( ! is_email( $form_data['email'] ) ) {
        $error = true;
        $result = $error_noemail;
    }

    if ( $error == false ) {
        $email_subject = "[" . get_bloginfo( 'name' ) . "] " . $form_data['subject'];
        $email_message = $form_data['message'] . "\n\nIP: " . wptuts_get_the_ip();
        $headers  = "From: " . $form_data['name'] . " <" . $form_data['email'] . ">\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\n";
        $headers .= "Content-Transfer-Encoding: 8bit\n";
        wp_mail( $email, $email_subject, $email_message, $headers );
        $result = $success;
        $sent = true;
    }
    // but if $error is still FALSE, put together the POSTed variables and send the e-mail!
    if ( $error == false ) {
        // get the website's name and puts it in front of the subject
        $email_subject = "[" . get_bloginfo( 'name' ) . "] " . $form_data['subject'];
        // get the message from the form and add the IP address of the user below it
        $email_message = $form_data['message'] . "\n\nIP: " . wptuts_get_the_ip();
        // set the e-mail headers with the user's name, e-mail address and character encoding
        $headers  = "From: " . $form_data['your_name'] . " <" . $form_data['email'] . ">\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\n";
        $headers .= "Content-Transfer-Encoding: 8bit\n";
        // send the e-mail with the shortcode attribute named 'email' and the POSTed data
        wp_mail( $email, $email_subject, $email_message, $headers );
        // and set the result text to the shortcode attribute named 'success'
        $result = $success;
        // ...and switch the $sent variable to TRUE
        $sent = true;
    }
  }

  // if there's no $result text (meaning there's no error or success, meaning the user just opened the page and did nothing) there's no need to show the $info variable
  if ( $result != "" ) {
    $info = '<div class="info">' . $result . '</div>';
  }
  // anyways, let's build the form! (remember that we're using shortcode attributes as variables with their names)
  $email_form = '<form class="contact-form" method="post" action="' . get_permalink() . '">
    <div>
        <label for="cf_name">' . $label_name . ':</label>
        <input type="text" name="your_name" id="cf_name" size="50" maxlength="50" value="' . $form_data['your_name'] . '" />
    </div>
    <div>
        <label for="cf_email">' . $label_email . ':</label>
        <input type="text" name="email" id="cf_email" size="50" maxlength="50" value="' . $form_data['email'] . '" />
    </div>
    <div>
        <label for="cf_subject">' . $label_subject . ':</label>
        <input type="text" name="subject" id="cf_subject" size="50" maxlength="50" value="' . $subject . $form_data['subject'] . '" />
    </div>
    <div>
        <label for="cf_message">' . $label_message . ':</label>
        <textarea name="message" id="cf_message" cols="50" rows="15">' . $form_data['message'] . '</textarea>
    </div>
    <div>
        <input type="submit" value="' . $label_submit . '" name="send" id="cf_send" />
    </div>
  </form>';

  if ( $sent == true ) {
      return $info;
  } else {
      return $info . $email_form;
  }
}
add_shortcode( 'cform_light', 'cform_light_sc' );
?>