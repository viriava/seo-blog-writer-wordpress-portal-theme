<?php

class ActlysLicenses {

    const INNER_API_KEY = 'anxc321lksajd8923dqSADAPOSDJ1alskdjklasd';
    // const API_URL = 'http://localhost.loc/bloger/wp-json/v1/inner/users/';
    const API_URL = 'https://seoblogwriterserver.sparkignitepro.com/wp-json/v1/inner/users/';

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

    private function generate_license_from_api( int $user_id, string $email ) {

        $toReturn = array(
            'error' => false,
            'api_key' => '',
            'message' => '',
        );

        $url = SBW_API_URL . '?api_key_inner=' . SBW_INNER_API_KEY;
        $json_data = array(
            "email" => $email,
            "activated" => 1
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

        // $license_data = $this->get_license_status();
        // $is_disabled = $license_data['activated'] == 0 && !$license_data['error'];
        $is_disabled = false;
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