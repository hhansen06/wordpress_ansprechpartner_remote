<?php
/**
 * REST API Endpoints für das Plugin
 */

class WAR_REST_API {

	/**
	 * REST Routes registrieren
	 */
	public static function register_routes() {
		// Sparten Route
		register_rest_route( 'war/v1', '/sparten', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_sparten_callback' ),
			'permission_callback' => '__return_true',
		) );

		// Ansprechpartner Route
		register_rest_route( 'war/v1', '/ansprechpartner', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_ansprechpartner_callback' ),
			'permission_callback' => '__return_true',
		) );

		// Ansprechpartner mit Sparte Route
		register_rest_route( 'war/v1', '/ansprechpartner/(?P<sparte>[^/]+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_ansprechpartner_callback' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Sparten abrufen
	 */
	public static function get_sparten_callback() {
		$sparten = WordPress_Ansprechpartner_Remote::get_sparten();
		return rest_ensure_response( $sparten );
	}

	/**
	 * Ansprechpartner abrufen
	 */
	public static function get_ansprechpartner_callback( $request ) {
		$sparte = $request->get_param( 'sparte' );
		$ansprechpartner = WordPress_Ansprechpartner_Remote::get_ansprechpartner( $sparte );
		return rest_ensure_response( $ansprechpartner );
	}
}

// Routes registrieren
add_action( 'rest_api_init', array( 'WAR_REST_API', 'register_routes' ) );
