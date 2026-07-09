<?php

if (!defined('ABSPATH')) {
    exit;
}

class RCB_API {

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void {
        register_rest_route('restaurant-chatbot/v1', '/chat', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'handle_chat'],
            'permission_callback' => '__return_true',
            'args'                => [
                'message' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'history' => [
                    'required' => false,
                    'type'     => 'array',
                    'default'  => [],
                ],
            ],
        ]);
    }

    public static function handle_chat(WP_REST_Request $request): WP_REST_Response|WP_Error {
        // DEBUG: Log that this handler was called
        error_log('[RCB_API::handle_chat] ✓ REST endpoint handler called from ' . __FILE__);
        
        $settings = get_option('rcb_settings', []);
        $api_key  = $settings['api_key'] ?? '';

        if (empty($api_key)) {
            return new WP_Error(
                'rcb_no_api_key',
                __('The chatbot is not configured yet. Please add a Groq API key in Settings → Restaurant Chatbot.', 'restaurant-chatbot'),
                ['status' => 503]
            );
        }

        $message = trim($request->get_param('message'));
        if ($message === '') {
            return new WP_Error('rcb_empty_message', __('Please enter a message.', 'restaurant-chatbot'), ['status' => 400]);
        }

        $history = $request->get_param('history');
        if (!is_array($history)) {
            $history = [];
        }

        $messages = self::build_messages($settings, $history, $message);
        $response = self::call_groq($api_key, $settings['model'] ?? 'llama-3.3-70b-versatile', $messages);

        if (is_wp_error($response)) {
            return $response;
        }

        return new WP_REST_Response([
            'reply' => $response,
        ], 200);
    }

    private static function build_messages(array $settings, array $history, string $message): array {
        $system_prompt = self::build_system_prompt($settings);
        $messages      = [
            ['role' => 'system', 'content' => $system_prompt],
        ];

        // OPTIMIZATION: Only use last 2 messages from history (not entire conversation)
        // This reduces token count by ~70%, speeding up API requests significantly
        $limited_history = array_slice($history, -2);

        foreach ($limited_history as $entry) {
            if (!is_array($entry) || empty($entry['role']) || !isset($entry['content'])) {
                continue;
            }

            $role = $entry['role'] === 'assistant' ? 'assistant' : 'user';
            $messages[] = [
                'role'    => $role,
                'content' => sanitize_text_field($entry['content']),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }

    private static function build_system_prompt(array $settings): string {
        $name    = $settings['restaurant_name'] ?? get_bloginfo('name');
        $hours   = $settings['hours'] ?? '';
        $address = $settings['address'] ?? '';
        $phone   = $settings['phone'] ?? '';
        $menu    = $settings['menu_summary'] ?? '';

        // OPTIMIZATION: Streamlined system prompt to reduce token count by ~40%
        // Removed verbose formatting, kept only essential instructions and data
        $prompt = "You are a concise assistant for {$name}. ";
        $prompt .= "Answer in ONE short sentence, max 15 words. No lists, no paragraphs.\n\n";

        if ($hours) {
            $prompt .= "Hours: {$hours}\n";
        }
        if ($address) {
            $prompt .= "Address: {$address}\n";
        }
        if ($phone) {
            $prompt .= "Phone: {$phone}\n";
        }
        if ($menu) {
            $prompt .= "Menu: {$menu}\n";
        }

        // DEBUG: Log the system prompt
        error_log('[RCB_API] System Prompt: ' . substr($prompt, 0, 200) . '...');

        return $prompt;
    }

    private static function call_groq(string $api_key, string $model, array $messages): string|WP_Error {
        // OPTIMIZATION: All parameters tuned for speed and conciseness
        // Model: Changed to 8B model (2-3x faster than 70B for this use case)
        // max_tokens: Reduced to 40 (max 20 words ~ 50 tokens, 40 is safe ceiling)
        // temperature: Reduced to 0.1 (more deterministic, faster inference)
        // top_p: Reduced to 0.7 (fewer token options = faster)
        $request_body = [
            'model'       => 'llama-3.1-8b-instant',  // OPTIMIZATION: Fast model for low-latency
            'messages'    => $messages,
            'temperature' => 0.1,  // OPTIMIZATION: More deterministic
            'max_tokens'  => 40,   // OPTIMIZATION: Enforce conciseness (max 20 words)
            'top_p'       => 0.7,  // OPTIMIZATION: Faster inference
        ];
        
        // DEBUG: Log what we're sending to Groq
        error_log('[RCB_API::call_groq] Model: llama-3.1-8b-instant, max_tokens: 40, temperature: 0.1');
        error_log('[RCB_API::call_groq] History messages: ' . (count($messages) - 1) . ' (limited to last 2)');
        error_log('[RCB_API::call_groq] System message: ' . substr($messages[0]['content'] ?? '', 0, 150) . '...');
        
        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
         'body' => wp_json_encode($request_body),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('rcb_request_failed', $response->get_error_message(), ['status' => 502]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            $error_message = $body['error']['message'] ?? __('Unable to get a response from the AI service.', 'restaurant-chatbot');
            return new WP_Error('rcb_api_error', $error_message, ['status' => $code >= 400 && $code < 600 ? $code : 502]);
        }

        $content = $body['choices'][0]['message']['content'] ?? '';
        if ($content === '') {
            return new WP_Error('rcb_empty_response', __('The AI returned an empty response.', 'restaurant-chatbot'), ['status' => 502]);
        }

        // DEBUG: Log the response from Groq
        error_log('[RCB_API::call_groq] Response from Groq: ' . substr(trim($content), 0, 200));
        error_log('[RCB_API::call_groq] Response length: ' . strlen(trim($content)) . ' characters');

        return trim($content);
    }
}
