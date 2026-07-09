<?php

if (!defined('ABSPATH')) {
    exit;
}

class RCB_Admin {

    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function add_menu(): void {
        add_options_page(
            __('Restaurant Chatbot', 'restaurant-chatbot'),
            __('Restaurant Chatbot', 'restaurant-chatbot'),
            'manage_options',
            'restaurant-chatbot',
            [self::class, 'render_page']
        );
    }

    public static function register_settings(): void {
        register_setting('rcb_settings_group', 'rcb_settings', [
            'type'              => 'array',
            'sanitize_callback' => [self::class, 'sanitize_settings'],
        ]);
    }

    public static function sanitize_settings(array $input): array {
        $existing = get_option('rcb_settings', []);

        return [
            'api_key'          => sanitize_text_field($input['api_key'] ?? ($existing['api_key'] ?? '')),
            'model'            => sanitize_text_field($input['model'] ?? ($existing['model'] ?? 'llama-3.3-70b-versatile')),
            'restaurant_name'  => sanitize_text_field($input['restaurant_name'] ?? ''),
            'hours'            => sanitize_textarea_field($input['hours'] ?? ''),
            'address'          => sanitize_text_field($input['address'] ?? ''),
            'phone'            => sanitize_text_field($input['phone'] ?? ''),
            'menu_summary'     => sanitize_textarea_field($input['menu_summary'] ?? ''),
            'welcome_message'  => sanitize_textarea_field($input['welcome_message'] ?? ''),
            'bot_name'         => sanitize_text_field($input['bot_name'] ?? ''),
            'primary_color'    => sanitize_hex_color($input['primary_color'] ?? '#c0392b') ?: '#c0392b',
        ];
    }

    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('rcb_settings', []);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Restaurant Chatbot Settings', 'restaurant-chatbot'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('rcb_settings_group'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="rcb_api_key"><?php esc_html_e('Groq API Key', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <input type="password" id="rcb_api_key" name="rcb_settings[api_key]"
                                   value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" class="regular-text" autocomplete="off">
                            <p class="description"><?php esc_html_e('Required for AI responses. Get one at https://console.groq.com/keys', 'restaurant-chatbot'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rcb_model"><?php esc_html_e('Model', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <input type="text" id="rcb_model" name="rcb_settings[model]"
                                   value="<?php echo esc_attr($settings['model'] ?? 'llama-3.3-70b-versatile'); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rcb_restaurant_name"><?php esc_html_e('Restaurant Name', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <input type="text" id="rcb_restaurant_name" name="rcb_settings[restaurant_name]"
                                   value="<?php echo esc_attr($settings['restaurant_name'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rcb_hours"><?php esc_html_e('Hours', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <textarea id="rcb_hours" name="rcb_settings[hours]" rows="4" class="large-text"><?php echo esc_textarea($settings['hours'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rcb_address"><?php esc_html_e('Address', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <input type="text" id="rcb_address" name="rcb_settings[address]"
                                   value="<?php echo esc_attr($settings['address'] ?? ''); ?>" class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rcb_phone"><?php esc_html_e('Phone', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <input type="text" id="rcb_phone" name="rcb_settings[phone]"
                                   value="<?php echo esc_attr($settings['phone'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rcb_menu_summary"><?php esc_html_e('Menu Summary', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <textarea id="rcb_menu_summary" name="rcb_settings[menu_summary]" rows="8" class="large-text"><?php echo esc_textarea($settings['menu_summary'] ?? ''); ?></textarea>
                            <p class="description"><?php esc_html_e('Describe your dishes, prices, and dietary options so the bot can answer menu questions.', 'restaurant-chatbot'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rcb_welcome_message"><?php esc_html_e('Welcome Message', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <textarea id="rcb_welcome_message" name="rcb_settings[welcome_message]" rows="3" class="large-text"><?php echo esc_textarea($settings['welcome_message'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rcb_bot_name"><?php esc_html_e('Bot Name', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <input type="text" id="rcb_bot_name" name="rcb_settings[bot_name]"
                                   value="<?php echo esc_attr($settings['bot_name'] ?? 'Restaurant Assistant'); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rcb_primary_color"><?php esc_html_e('Primary Color', 'restaurant-chatbot'); ?></label></th>
                        <td>
                            <input type="color" id="rcb_primary_color" name="rcb_settings[primary_color]"
                                   value="<?php echo esc_attr($settings['primary_color'] ?? '#c0392b'); ?>">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
