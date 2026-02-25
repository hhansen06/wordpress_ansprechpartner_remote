<?php
/**
 * Hauptklasse für das WordPress Ansprechpartner Remote Plugin
 */

class WordPress_Ansprechpartner_Remote {

	private static $instance = null;
	const DEFAULT_API_BASE = 'https://www.msg-sulingen.de/wp-json/vereinsverwaltung/v1';
	const API_URL_OPTION = 'war_api_base_url';
	const CACHE_KEY_SPARTEN = 'war_cache_sparten';
	const CACHE_KEY_ANSPRECHPARTNER = 'war_cache_ansprechpartner_';
	const CACHE_DURATION = 86400; // 24 Stunden

	/**
	 * Singleton: Instanz holen
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'wp_ajax_war_get_sparten', array( $this, 'ajax_get_sparten' ) );
		add_action( 'wp_ajax_nopriv_war_get_sparten', array( $this, 'ajax_get_sparten' ) );
		add_action( 'wp_ajax_war_get_ansprechpartner', array( $this, 'ajax_get_ansprechpartner' ) );
		add_action( 'wp_ajax_nopriv_war_get_ansprechpartner', array( $this, 'ajax_get_ansprechpartner' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_post_war_clear_cache', array( $this, 'clear_cache_action' ) );
		add_action( 'admin_post_war_save_settings', array( $this, 'save_settings_action' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Block registrieren
	 */
	public function register_block() {
		// Block über metadata registrieren
		register_block_type_from_metadata( WAR_PLUGIN_DIR . 'blocks/ansprechpartner' );
	}

	/**
	 * REST API Routes registrieren
	 */
	public function register_rest_routes() {
		// Sparten Route
		register_rest_route( 'war/v1', '/sparten', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_sparten_rest' ),
			'permission_callback' => '__return_true',
			'args'                => array(),
		) );

		// Ansprechpartner Route (alle)
		register_rest_route( 'war/v1', '/ansprechpartner', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_ansprechpartner_rest' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'sparte' => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		// Ansprechpartner Route mit Sparte
		register_rest_route( 'war/v1', '/ansprechpartner/(?P<sparte>[^/]+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_ansprechpartner_rest' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'sparte' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );
	}

	/**
	 * REST Callback: Sparten
	 */
	public function get_sparten_rest() {
		$sparten = self::get_sparten();
		return rest_ensure_response( $sparten );
	}

	/**
	 * REST Callback: Ansprechpartner
	 */
	public function get_ansprechpartner_rest( $request ) {
		$sparte = $request->get_param( 'sparte' );
		$ansprechpartner = self::get_ansprechpartner( $sparte );
		return rest_ensure_response( $ansprechpartner );
	}

	/**
	 * AJAX Callback: Sparten
	 */
	public function ajax_get_sparten() {
		header( 'Content-Type: application/json' );
		$sparten = self::get_sparten();
		wp_send_json( $sparten );
	}

	/**
	 * AJAX Callback: Ansprechpartner
	 */
	public function ajax_get_ansprechpartner() {
		$sparte = isset( $_POST['sparte'] ) ? sanitize_text_field( $_POST['sparte'] ) : '';
		
		error_log( 'AJAX Request für Sparte: ' . $sparte );
		
		header( 'Content-Type: application/json' );
		$ansprechpartner = self::get_ansprechpartner( $sparte );
		
		error_log( 'Ansprechpartner Count: ' . count( $ansprechpartner ) );
		
		wp_send_json( $ansprechpartner );
	}

	/**
	 * Assets-Version berechnen (IMMER aktuell für Entwicklung)
	 */
	private function get_asset_version() {
		// Immer aktuelle Version - kein Caching
		return time();
	}

	/**
	 * Assets für Block-Editor laden
	 */
	public function enqueue_block_editor_assets() {
		$version = $this->get_asset_version();

		// Block Editor Script registrieren und laden
		wp_register_script(
			'war-block-editor',
			WAR_PLUGIN_URL . 'assets/js/block-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch' ),
			$version,
			true
		);

		wp_enqueue_script( 'war-block-editor' );

		// Editor CSS
		wp_enqueue_style(
			'war-block-editor-css',
			WAR_PLUGIN_URL . 'blocks/ansprechpartner/editor.css',
			array(),
			$version
		);

		// Localize data mit AJAX und REST URLs
		wp_localize_script( 'war-block-editor', 'warBlockData', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'restUrl'  => rest_url( 'war/v1' ),
			'apiBase'  => rest_url( 'vereinsverwaltung/v1' ),
		) );
	}

	/**
	 * Frontend Assets laden
	 */
	public function enqueue_frontend_assets() {
		$version = $this->get_asset_version();

		wp_enqueue_style(
			'war-styles',
			WAR_PLUGIN_URL . 'assets/css/ansprechpartner.css',
			array(),
			$version
		);

		// Frontend Script
		wp_enqueue_script(
			'war-frontend',
			WAR_PLUGIN_URL . 'blocks/ansprechpartner/render.js',
			array(),
			$version,
			true
		);
	}

	/**
	 * Admin Menü hinzufügen
	 */
	public function add_admin_menu() {
		add_options_page(
			'Ansprechpartner Einstellungen',
			'Ansprechpartner',
			'manage_options',
			'war-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Einstellungsseite rendern
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Zugriff verweigert' );
		}

		// Cache-Größe berechnen
		$cache_size = $this->get_cache_size();
		$api_base = self::get_api_base();
		?>
		<div class="wrap">
			<h1>Ansprechpartner Remote - Einstellungen</h1>
			
			<div class="card">
				<h2>API Einstellungen</h2>
				<?php
				if ( isset( $_GET['settings_saved'] ) ) {
					echo '<div class="notice notice-success"><p>Einstellungen gespeichert!</p></div>';
				}
				?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'war_save_settings_nonce', 'war_nonce' ); ?>
					<input type="hidden" name="action" value="war_save_settings">
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="war_api_base_url">API Base URL</label>
							</th>
							<td>
								<input type="url" id="war_api_base_url" name="war_api_base_url" 
									value="<?php echo esc_attr( $api_base ); ?>" 
									class="regular-text code" required>
								<p class="description">Standard: <code><?php echo esc_html( self::DEFAULT_API_BASE ); ?></code></p>
							</td>
						</tr>
					</table>
					
					<?php submit_button( 'Speichern' ); ?>
				</form>
			</div>

			<div class="card">
				<h2>Cache Management</h2>
				<?php
				if ( isset( $_GET['cache_cleared'] ) ) {
					echo '<div class="notice notice-success"><p>Cache erfolgreich geleert!</p></div>';
				}
				?>
				<p>Aktuelle Cache-Größe: <strong><?php echo esc_html( $cache_size ); ?> Einträge</strong></p>
				
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'war_clear_cache_nonce', 'war_nonce' ); ?>
					<input type="hidden" name="action" value="war_clear_cache">
					<button type="submit" class="button button-primary">Cache leeren</button>
				</form>
			</div>

			<div class="card">
				<h2>Informationen</h2>
				<p>Aktuelle API Base: <code><?php echo esc_html( $api_base ); ?></code></p>
				<p>Cache Duration: <strong><?php echo esc_html( self::CACHE_DURATION / 3600 ); ?> Stunden</strong></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Cache-Größe berechnen
	 */
	private function get_cache_size() {
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE %s",
				'war_cache_%'
			)
		);
		return intval( $count );
	}

	/**
	 * Einstellungen speichern Action
	 */
	public function save_settings_action() {
		check_admin_referer( 'war_save_settings_nonce', 'war_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Zugriff verweigert' );
		}

		if ( isset( $_POST['war_api_base_url'] ) ) {
			$api_url = sanitize_url( $_POST['war_api_base_url'] );
			update_option( self::API_URL_OPTION, $api_url );
			$this->clear_all_cache(); // Cache leeren wenn API URL geändert wird
		}

		wp_redirect( admin_url( 'options-general.php?page=war-settings&settings_saved=1' ) );
		exit;
	}

	/**
	 * Cache leeren Action
	 */
	public function clear_cache_action() {
		check_admin_referer( 'war_clear_cache_nonce', 'war_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Zugriff verweigert' );
		}

		$this->clear_all_cache();

		wp_safe_remote_post( admin_url( 'admin.php' ), array(
			'blocking' => false,
		) );

		wp_redirect( admin_url( 'options-general.php?page=war-settings&cache_cleared=1' ) );
		exit;
	}

	/**
	 * API Base URL abrufen
	 */
	public static function get_api_base() {
		$api_base = get_option( self::API_URL_OPTION );
		if ( empty( $api_base ) ) {
			$api_base = self::DEFAULT_API_BASE;
		}
		return rtrim( $api_base, '/' ); // Trailing slash entfernen
	}

	/**
	 * Alle Sparten abrufen (OHNE Cache)
	 */
	public static function get_sparten() {
		$api_base = self::get_api_base();
		$response = wp_remote_get( $api_base . '/sparten' );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}

	/**
	 * Ansprechpartner abrufen (OHNE Cache)
	 *
	 * @param string $sparte Optional: Sparte oder Sparten-ID
	 * @param string $funktion Optional: Funktion
	 */
	public static function get_ansprechpartner( $sparte = '', $funktion = '' ) {
		$api_base = self::get_api_base();
		$url = $api_base . '/ansprechpartner';

		if ( ! empty( $sparte ) ) {
			$url .= '/' . urlencode( $sparte );
		}

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		// Debug: Log der API Antwort
		error_log( 'WAR API Response für Sparte: ' . $sparte );
		error_log( 'Anzahl der Ansprechpartner: ' . count( $data ) );
		error_log( 'Daten: ' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) );

		// Nach Funktion filtern, falls angegeben
		if ( ! empty( $funktion ) && is_array( $data ) ) {
			$data = array_filter( $data, function ( $item ) use ( $funktion ) {
				return isset( $item['funktion'] ) && $item['funktion'] === $funktion;
			} );
		}

		return $data;
	}

	/**
	 * Ganzen Cache leeren
	 */
	public function clear_all_cache() {
		global $wpdb;

		// Alle transients mit war_cache_ prefix löschen
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
				'war_cache_%'
			)
		);

		wp_cache_flush();
	}

	/**
	 * Plugin aktivieren
	 */
	public static function activate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deaktivieren
	 */
	public static function deactivate() {
		// Cache leeren
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
				'war_cache_%'
			)
		);
		flush_rewrite_rules();
	}
}
