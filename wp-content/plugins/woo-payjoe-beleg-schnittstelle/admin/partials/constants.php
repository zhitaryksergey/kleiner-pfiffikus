<?php

function get_payjoe_log_path()
{
    $UPLOAD_DIR = wp_upload_dir();
    $PAYJOE_LOG_FILE_NAME = 'payjoe-' . time() . '-' . rand(60, 998999) . '.log'; //secure filename with salt.
    return $UPLOAD_DIR['basedir'] . '/payjoe/' . $PAYJOE_LOG_FILE_NAME;
}

const PAYJOE_STATUS_PENDING = 10;
const PAYJOE_STATUS_OK = 20;
const PAYJOE_STATUS_ERROR = 30;
const PAYJOE_STATUS_RESEND = 9989;

function get_payjoe_status_list()
{
    return array(
        PAYJOE_STATUS_PENDING => __('Not yet transferred', 'weslink-payjoe-opbeleg')
    , PAYJOE_STATUS_OK => __('Order has been transferred, all is fine', 'weslink-payjoe-opbeleg')
    , PAYJOE_STATUS_ERROR => __('<div title="%s">There has been an error</div>', 'weslink-payjoe-opbeleg')
    , PAYJOE_STATUS_RESEND => __('Order is planned to be resend', 'weslink-payjoe-opbeleg')
    );
}
