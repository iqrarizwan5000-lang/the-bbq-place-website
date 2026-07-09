<?php

if (!defined('ABSPATH')) {
    exit;
}

class RCB_Frontend {

    public static function init(): void {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_footer', [self::class, 'render_widget']);
    }

    public static function enqueue_assets(): void {
        if (is_admin()) {
            return;
        }

        $settings = get_option('rcb_settings', []);

        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
            [],
            '6.7.2'
        );

        wp_enqueue_style(
            'rcb-chatbot',
            RCB_PLUGIN_URL . 'assets/css/chatbot.css',
            [],
            RCB_VERSION
        );

        wp_enqueue_script(
            'rcb-chatbot',
            RCB_PLUGIN_URL . 'assets/js/chatbot.js',
            [],
            RCB_VERSION,
            true
        );

        wp_localize_script('rcb-chatbot', 'rcbChatbot', [
            'restUrl'       => esc_url_raw(rest_url('restaurant-chatbot/v1/chat')),
            'nonce'         => wp_create_nonce('wp_rest'),
            'welcomeMessage'=> $settings['welcome_message'] ?? '',
            'botName'       => $settings['bot_name'] ?? 'Restaurant Assistant',
            'primaryColor'  => $settings['primary_color'] ?? '#c0392b',
        ]);
    }

    private static function headset_svg(): string {
        return '<svg class="rcb-headset-icon" width="34" height="34" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <circle cx="12" cy="7.25" r="3" fill="currentColor"/>
            <path d="M6 19.5c0-3.31 2.69-6 6-6s6 2.69 6 6" fill="currentColor"/>
            <path d="M7.25 7.75a4.75 4.75 0 0 1 9.5 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            <rect x="5" y="7.75" width="2.75" height="4.5" rx="1.375" fill="currentColor"/>
            <rect x="16.25" y="7.75" width="2.75" height="4.5" rx="1.375" fill="currentColor"/>
            <path d="M16.25 10.5h1.75c.97 0 1.75.78 1.75 1.75v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>';
    }

    private static function robot_svg(): string {
        return '
        <svg class="rcb-robot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none">
    
            <!-- Chat Bubble -->
            <path d="M14 14C14 9.5 17.5 6 22 6H42C46.5 6 50 9.5 50 14V34C50 38.5 46.5 42 42 42H30L20 50V42H22C17.5 42 14 38.5 14 34V14Z"
                  fill="currentColor"/>
    
            <!-- Three Dots -->
            <circle cx="24" cy="24" r="2.5" fill="white"/>
            <circle cx="32" cy="24" r="2.5" fill="white"/>
            <circle cx="40" cy="24" r="2.5" fill="white"/>
    
            <!-- AI Sparkle -->
            <path d="M50 10L51.8 14.2L56 16L51.8 17.8L50 22L48.2 17.8L44 16L48.2 14.2L50 10Z"
                  fill="#FFD54F"/>
    
        </svg>';
    }

    public static function render_widget(): void {
        if (is_admin()) {
            return;
        }

        $settings = get_option('rcb_settings', []);
        $bot_name = esc_html($settings['bot_name'] ?? 'Restaurant Assistant');
        ?>
        <div id="rcb-chatbot" class="rcb-chatbot" aria-live="polite">
            <button type="button" id="rcb-toggle" class="rcb-toggle" aria-expanded="false" aria-controls="rcb-panel">
                <span class="rcb-toggle-pulse" aria-hidden="true"></span>
                <span class="rcb-toggle-icon" aria-hidden="true">
                    <?php echo self::headset_svg(); ?>
                </span>
                <span class="screen-reader-text"><?php esc_html_e('Open chat', 'restaurant-chatbot'); ?></span>
            </button>

        

            <div id="rcb-panel" class="rcb-panel" hidden>
                <header class="rcb-header">
                    <div class="rcb-header-info">
                        <div class="rcb-avatar" aria-hidden="true">
                            <?php echo self::robot_svg(); ?>
                        </div>
                        <div class="rcb-header-text">
                            <strong class="rcb-header-name"><?php echo $bot_name; ?></strong>
                            <span class="rcb-status">
                                <span class="rcb-status-dot" aria-hidden="true"></span>
                                <?php esc_html_e('Online', 'restaurant-chatbot'); ?>
                            </span>
                        </div>
                    </div>
                    <button type="button" id="rcb-close" class="rcb-close" aria-label="<?php esc_attr_e('Close chat', 'restaurant-chatbot'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </header>

                <div id="rcb-messages" class="rcb-messages" role="log"></div>

                <form id="rcb-form" class="rcb-form">
                    <label class="screen-reader-text" for="rcb-input"><?php esc_html_e('Your message', 'restaurant-chatbot'); ?></label>
                    <div class="rcb-input-wrap">
                        <input type="text" id="rcb-input" class="rcb-input rcb-button" placeholder="<?php esc_attr_e('Ask about our menu, hours…', 'restaurant-chatbot'); ?>" autocomplete="off" required>
                       <button type="submit" class="rcb-send" aria-label="<?php esc_attr_e('Send message', 'restaurant-chatbot'); ?>">
                    
<svg class="rcb-send-icon" viewBox="0 0 24 24"
     xmlns="http://www.w3.org/2000/svg"
     aria-hidden="true">

   <path
d="M5 5L20 12L5 19L8 12L5 5Z"
fill="white"/>

</svg>
</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}
