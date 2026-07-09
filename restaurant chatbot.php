<?php
/*
Plugin Name: Restaurant Chatbot
Description: AI-powered restaurant chatbot plugin for WordPress.
Version: 1.0
Author: Iqra
*/

if (!defined('ABSPATH')) {
    exit;
}

define('RCB_VERSION', '1.0');
define('RCB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RCB_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once RCB_PLUGIN_DIR . 'includes/class-rcb-admin.php';
require_once RCB_PLUGIN_DIR . 'includes/class-rcb-api.php';
require_once RCB_PLUGIN_DIR . 'includes/class-rcb-frontend.php';

final class Restaurant_Chatbot {

    private static ?Restaurant_Chatbot $instance = null;

    public static function instance(): Restaurant_Chatbot {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);

        RCB_Admin::init();
        RCB_API::init();
        RCB_Frontend::init();
    }

    public function activate(): void {
        $defaults = [
            'api_key'          => '',
            'model'            => 'llama-3.3-70b-versatile',
            'restaurant_name'  => get_bloginfo('name'),
            'hours'            => '',
            'address'          => '',
            'phone'            => '',
            'menu_summary'     => '',
            'welcome_message'  => 'Hi! I can help with our menu, hours, location, and reservations. What would you like to know?',
            'bot_name'         => 'Restaurant Assistant',
            'primary_color'    => '#c0392b',
        ];

        if (get_option('rcb_settings') === false) {
            add_option('rcb_settings', $defaults);
        }
    }
}

Restaurant_Chatbot::instance();
