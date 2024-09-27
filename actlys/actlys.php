<?php

class ActlysLicenses {

    public function __construct() {
        add_shortcode('actlys_license', array($this, 'render_shortcode'));
		add_action( 'wp_enqueue_scripts', array($this, "frontend_js"));
        add_action('wp_ajax_actlys_licences_reset', array($this, "licences_reset"));
        add_action( 'arm_after_add_new_user', array($this, "arm_after_add_new_user_func"), 10, 2);
    }

    public function arm_after_add_new_user_func(int $user_id = 0, array $posted_register_data = array()) : void {
        
        $user_info = get_userdata( $user_id );
        $user_email = $user_info->user_email;
        self::generate_license_from_api( $user_id, $user_email );

    }

    public function licences_reset() {

        if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'licences-actlys' ) && is_user_logged_in() ) {
            
            $current_user = wp_get_current_user();
            $user_email = $current_user->user_email;
            $user_id = $current_user->ID;
            $actlys_client_id = get_user_meta($user_id, 'actlys_client_id', true);

            $toReturn = array(
                'error' => false,
                'api_key' => '',
                'message' => '',
            );

            $url = SBW_API_URL . '?api_key_inner=' . SBW_INNER_API_KEY;
            $json_data = array(
                "email" => $user_email,
                "activated" => 1,
                "reset_api_key" => 1,
                "client_id" => $actlys_client_id,
            );
            $args = array(
                'body'        => json_encode( $json_data ),
                'method'      => 'PUT',
                'headers'     => array(
                    'Content-Type' => 'application/json'
                ),
            );
            $response = wp_remote_request( $url, $args );
            
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $toReturn['error'] = true;
                $toReturn['message'] = $error_message;
            } else {
                $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
                if ( $response_body['success'] ){
                    
                    update_user_meta( $user_id, 'actlys_api_key', $response_body['data']['api_secret_key'] );
                    $toReturn['api_key'] = $response_body['data']['api_secret_key'];
                    
                } else {
                    $toReturn['error'] = true;
                    $error_message = 'Oops! Something went wrong. Please try again later.';
                    if ( isset($response_body['data'][0]['message']) ) {
                        $error_message = $response_body['data'][0]['message'];
                    }
                    if ( isset($response_body['data'][0]['code']) && $response_body['data'][0]['code'] == 'server_under_maintenance' ) {
                        $error_message = "We're currently sprucing things up on our server to make your experience even better. Please check back soon! Thank you for your patience.";
                    }
                    $toReturn['message'] = $error_message;
                }
            }

            echo json_encode($toReturn);

        }

        exit;

    }

    public static function generate_license_from_api( int $user_id, string $email ) {

        $toReturn = array(
            'error' => false,
            'api_key' => '',
            'message' => '',
        );

        $url = SBW_API_URL . '?api_key_inner=' . SBW_INNER_API_KEY;
        $json_data = array(
            "email" => $email,
            "activated" => 1,
            "package_id" => 1,
            "domain" => ''
        );

        $args = array(
            'body'        => json_encode( $json_data ),
            'method'      => 'POST',
            'headers'     => array(
                'Content-Type' => 'application/json'
            ),
        );
        $response = wp_remote_request( $url, $args );
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $toReturn['error'] = true;
            $toReturn['message'] = $error_message;
        } else {
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if ( $response_body['success'] ){
                
                update_user_meta( $user_id, 'actlys_api_key', $response_body['data']['api_secret_key'] );
                update_user_meta( $user_id, 'actlys_client_id', $response_body['data']['id'] );
                $toReturn['api_key'] = $response_body['data']['api_secret_key'];
                
            } else {
                $toReturn['error'] = true;
                $error_message = 'Oops! Something went wrong. Please try again later.';
                if ( isset($response_body['data'][0]['message']) ) {
                    $error_message = $response_body['data'][0]['message'];
                }
                if ( isset($response_body['data'][0]['code']) && $response_body['data'][0]['code'] == 'server_under_maintenance' ) {
                    $error_message = "We're currently sprucing things up on our server to make your experience even better. Please check back soon! Thank you for your patience.";
                }
                $toReturn['message'] = $error_message;
            }
        }

        return $toReturn;

    }

    private function get_license_status() {

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        $toReturn = array(
            'error' => false,
            'activated' => 0,
            'message' => '',
        );

        $actlys_client_id = get_user_meta($user_id, 'actlys_client_id', true);

        if ( $actlys_client_id ) {

            $url = SBW_API_URL . '?api_key_inner=' . SBW_INNER_API_KEY;
            $json_data = array(
                "client_id" => $actlys_client_id,
            );
            $args = array(
                'body'        => $json_data,
                'headers'     => array(
                    'Content-Type' => 'application/json'
                ),
            );
            $response = wp_remote_get( $url, $args );
            
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $toReturn['error'] = true;
                $toReturn['message'] = $error_message;
            } else {
                $response_body = json_decode(wp_remote_retrieve_body($response), true);

                if ( $response_body['success'] ){
                    
                    $toReturn['activated'] = $response_body['data']['activated'];
                    
                } else {
                    $toReturn['error'] = true;
                    $error_message = 'Oops! Something went wrong. Please try again later.';
                    if ( isset($response_body['data'][0]['message']) ) {
                        $error_message = $response_body['data'][0]['message'];
                    }
                    if ( isset($response_body['data'][0]['code']) && $response_body['data'][0]['code'] == 'server_under_maintenance' ) {
                        $error_message = "We're currently sprucing things up on our server to make your experience even better. Please check back soon! Thank you for your patience.";
                    }
                    $toReturn['message'] = $error_message;
                }
            }

        } else {
            $toReturn['error'] = true;
            $toReturn['message'] = 'Client ID is missing';
        }

        return $toReturn;

    }

    public function frontend_js() {

        wp_enqueue_script( 'actlys-licenses-js', get_stylesheet_directory_uri().'/actlys/actlys-licenses.js', array( 'jquery' ), filemtime(__DIR__.'/actlys-licenses.js'));
        wp_localize_script('actlys-licenses-js', 'actlys_licenses_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));

        wp_enqueue_style('actlys-licenses-css', get_stylesheet_directory_uri().'/actlys/actlys-licenses.css', false, filemtime(__DIR__.'/actlys-licenses.css'));

    }

    public function render_shortcode() {

        $license_data = $this->get_license_status();
        $is_disabled = $license_data['activated'] == 0 && !$license_data['error'];
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'actlys_api_key', true);

        $nonce = wp_create_nonce('licences-actlys');

        $html_content = '';
        $html_content .= '<div class="licenses-wrapper">';
            $html_content .= '<h1>Licenses</h1>';
            $html_content .= '<div class="licenses '.($api_key && !$is_disabled ? '--active' : '').'">';

                $html_content .= '<div class="licenses-row">';
                    $html_content .= '<span class="licenses-row-title">License Status</span>';
                    $html_content .= '<span class="license-status">';
                    if ( $api_key && !$is_disabled ) {
                        $html_content .= 'Active';
                    } else {
                        $html_content .= 'Inactive';
                    }
                    $html_content .= '</span>';
                $html_content .= '</div>';

                if ( !$is_disabled ) {
                    $html_content .= '<div class="licenses-row">';
                        $html_content .= '<span class="licenses-row-title">License Key</span>';
                        $html_content .= '<div class="license-input-row">';
                            $html_content .= '<div class="license-input">';
                                $html_content .= '<input type="text" id="license-key" value="'.$api_key.'" readonly>';
                                if ( $api_key ) {
                                    $html_content .= '<button type="button" class="license-copy">Copy</button>';
                                    $html_content .= '<span class="license-copied">Copied</span>';
                                }
                            $html_content .= '</div>';
                            if ( $api_key ) {
                                $html_content .= '<button type="button" class="license-reset">Reset</button>';
                            }
                        $html_content .= '</div>';
                        $html_content .= '<input type="hidden" value="'.$nonce.'" id="licenses-nonce">';
                    $html_content .= '</div>';
                }
            $html_content .= '</div>';
        $html_content .= '</div>';

        return $html_content;
    }
}

new ActlysLicenses();

class ActlysUserRestAPI {

    // Constructor to initialize the REST API route registration
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    // Register custom REST API routes
    public function register_routes() {
        // Register a POST route: /wp-json/scwriter/v1/connect
        register_rest_route('scwriter/v1', '/connect/', array(
            'methods' => 'POST',
            'callback' => array($this, 'connect_endpoint'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));
        // Register a POST route: /wp-json/scwriter/v1/connect
        register_rest_route('scwriter/v1', '/add_domain/', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_domain_endpoint'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));
    }

    // Verify API key
    public function verify_api_key($request) {
        // Get API key from URL parameter
        $api_key = $request->get_param('api_key');

        // Check if the provided API key matches the predefined one
        if ($api_key && $api_key === SBW_CONNECTION_API_KEY) {
            return true; // API key is valid, allow the request
        }

        $error = new \WP_Error( 'actlys_rest_api_validation', 'Unauthorized: Invalid Connection API Key' );
        wp_send_json_error( $error, 503 );

        return false;
    }

    // Callback for the POST /connect endpoint
    public function connect_endpoint($request) {

        $toReturn = array(
            'success' => true,
            'data' => [
                'message' => ''
            ]
        );

        $params = $request->get_json_params();
        
        $email = isset($params['email']) ? sanitize_text_field($params['email']) : '';
        $domain = isset($params['domain']) ? sanitize_text_field($params['domain']) : '';
        $first_name = isset($params['first_name']) ? sanitize_text_field($params['first_name']) : '';
        $last_name = isset($params['last_name']) ? sanitize_text_field($params['last_name']) : '';

        if ( $email && $domain ) {

            $user = get_user_by('email', $email);

            if ( $user ) {
                $toReturn['success'] = false;
                $link = '<a href="'.get_home_url().'" target="_blank">here</a>';
                $toReturn['data']['message'] = 'You already have an SCwriter account. Please log in '.$link.' to copy your API Key.';
            } else {

                $user_id = $this->create_user( $email, $first_name, $last_name );

                if ( !$user_id ) {
                    $toReturn['success'] = false;
                    $toReturn['data']['message'] = 'We\'re unable to connect to your website at the moment due to an issue with creating user. Please try again later.';
                } else {
                    $update_domain = $this->update_user_domain( $email, $domain );
                    $api_key = get_user_meta($user_id, 'actlys_api_key', true);

                    if ( $api_key ) {
                        $toReturn['success'] = true;
                        $toReturn['data']['api_key'] = $api_key;
                    }
                }

            }

        } else {

            $toReturn['success'] = false;
            $toReturn['data']['message'] = 'We\'re unable to connect to your website at the moment due to an issue with the incoming parameters. Please try again later.';

        }

        return new WP_REST_Response( $toReturn, 200 );

    }

    // Callback for the POST /add_domain_endpoint endpoint
    public function add_domain_endpoint($request) {

        $toReturn = array(
            'success' => true,
        );

        $params = $request->get_json_params();
        
        $email = isset($params['email']) ? sanitize_text_field($params['email']) : '';
        $domain = isset($params['domain']) ? sanitize_text_field($params['domain']) : '';

        if ( $email && $domain ) {

            $user = get_user_by('email', $email);

            if ( $user ) {
                
                $update_domain = $this->update_user_domain( $email, $domain );

                if ( $update_domain['error'] ) {

                    $toReturn['success'] = false;
                    $toReturn['data']['message'] = $update_domain['error_message'];

                }

            } else {

                $toReturn['success'] = false;
                $toReturn['data']['message'] = 'No user found with the provided email address.';
                
            }

        } else {

            $toReturn['success'] = false;
            $toReturn['data']['message'] = 'We\'re unable to connect to your website at the moment due to an issue with the incoming parameters. Please try again later.';

        }

        return new WP_REST_Response( $toReturn, 200 );

    }

    private function update_user_domain( string $email, string $domain ) : array {

        $toReturn = array(
            'error' => false,
            'error_message' => ''
        );

        $user = get_user_by('email', $email);

        if ( is_wp_error($user) ) {
            $toReturn['error'] = true;
            $toReturn['error_message'] = 'User with the provided email does not exist.';
        } else {
            $actlys_client_id = get_user_meta($user->ID, 'actlys_client_id', true);

            $url = SBW_API_URL . '?api_key_inner=' . SBW_INNER_API_KEY;
            $json_data = array(
                "client_id" => $actlys_client_id,
            );
            $args = array(
                'body'        => $json_data,
                'method'      => 'GET',
                'headers'     => array(
                    'Content-Type' => 'application/json'
                ),
            );

            $response = wp_remote_request( $url, $args );

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $toReturn['error'] = true;
                $toReturn['error_message'] = $error_message;
            } else {
                $response_body = json_decode(wp_remote_retrieve_body($response), true);

                if ( !$response_body['success'] ){
                    $toReturn['error'] = true;
                    $error_message = 'Oops! Something went wrong with getting client\'s data.';
                    if ( isset($response_body['data'][0]['message']) ) {
                        $error_message = $response_body['data'][0]['message'];
                    }
                    if ( isset($response_body['data'][0]['code']) && $response_body['data'][0]['code'] == 'server_under_maintenance' ) {
                        $error_message = "We're currently sprucing things up on our server to make your experience even better. Please check back soon! Thank you for your patience.";
                    }
                    $toReturn['error_message'] = $error_message;
                } else {

                    $need_update = false;
                    $update_data = array(
                        'client_id'     => $actlys_client_id,
                        'email'         => $response_body['data']['email'],
                        'domains'       => '',
                        "package_id"    => $response_body['data']['package_id'],
                        "activated"     => $response_body['data']['activated'],
                    );

                    $domains = $response_body['data']['domains'];
                    $domains_array = array();
                    if ( $domains !== 'null' && !is_null($domains) ) {
                        $domains_array = json_decode( $domains, true );
                    }
                    
                    if ( !in_array( $domain, $domains_array ) ) {
                        $domains_array[] = $domain;
                        $need_update = true;
                    }
                    $update_data['domains'] = $domains_array;
                    
                    if ( $need_update ) {

                        $update_args = array(
                            'body'        => wp_json_encode( $update_data, JSON_UNESCAPED_UNICODE ),
                            'method'      => 'PUT',
                            'headers'     => array(
                                'Content-Type' => 'application/json'
                            ),
                        );
                        $update_response = wp_remote_request( $url, $update_args );

                        if (is_wp_error($update_response)) {
                            $error_message = $update_response->get_error_message();
                            $toReturn['error'] = true;
                            $toReturn['error_message'] = $error_message;
                        } else {
                            $update_response_body = json_decode(wp_remote_retrieve_body($update_response), true);
                            
                            if ( !$update_response_body['success'] ){
                                
                                $toReturn['error'] = true;
                                $error_message = 'Oops! Something went wrong with getting client\'s data.';
                                if ( isset($update_response_body['data'][0]['message']) ) {
                                    $error_message = $update_response_body['data'][0]['message'];
                                }
                                if ( isset($update_response_body['data'][0]['code']) && $update_response_body['data'][0]['code'] == 'server_under_maintenance' ) {
                                    $error_message = "We're currently sprucing things up on our server to make your experience even better. Please check back soon! Thank you for your patience.";
                                }
                                $toReturn['error_message'] = $error_message;
                            }
                        }

                    }

                }
            }

        }

        return $toReturn;

    }

    private function create_user( string $email, string $first_name, string $last_name ) : int|bool {

        $password = wp_generate_password(12, true);

        $arm_member_forms = new ARM_member_forms_Lite();
        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'user_nicename' => '',
            'user_url' => '',
            'display_name' => $first_name . ' ' . $last_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'subscription_plan' => 1,
        );
        
        $user_id = $arm_member_forms->arm_register_new_member($user_data);

        if ( !is_wp_error( $user_id ) ) {

            update_user_meta($user_id, 'first_name', $user_data['first_name']);
            update_user_meta($user_id, 'last_name', $user_data['last_name']);
            update_user_meta($user_id, 'display_name', $user_data['display_name']);

            update_user_meta( $user_id, 'arm_user_future_plan_ids', [] );
            update_user_meta( $user_id, 'arm_user_suspended_plan_ids', [] );
            update_user_meta( $user_id, 'arm_user_plan_ids', [1] );
            update_user_meta( $user_id, 'arm_user_last_plan', 1 );
            
            return $user_id;

        } else {
            return false;
        }
    }
}

new ActlysUserRestAPI();