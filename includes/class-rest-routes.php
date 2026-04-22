<?php
if (!defined('ABSPATH'))
    exit;

class Rallyestage_Rest_Routes
{

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('rallyestage/v1', '/refresh-cache', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'refresh_cache'],
            'permission_callback' => '__return_true',
            'args' => [
                'secret' => [
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    public function refresh_cache(WP_REST_Request $request): WP_REST_Response
    {
        $secret = $request->get_param('secret');
        $stored_secret = get_option('rallyestage_cache_secret', '');

        if (empty($stored_secret) || !hash_equals((string) $stored_secret, (string) $secret)) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Ungültiges oder fehlendes Secret.'],
                403
            );
        }

        $result = Rallyestage_API::fetch_and_cache();

        return new WP_REST_Response(
            ['success' => $result['success'], 'message' => $result['message']],
            $result['success'] ? 200 : 500
        );
    }
}
