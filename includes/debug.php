<?php
/**
 * Debug: Alle registrierten REST Routes anzeigen
 */

// Nur im Admin sichtbar
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

// REST Server von WordPress holen
$rest_server = rest_get_server();

if ( ! $rest_server ) {
	return;
}

$routes = $rest_server->get_routes();

// In die Seite ausgeben
echo '<div style="margin-top: 20px; padding: 20px; background: #f5f5f5; border-radius: 4px;">';
echo '<h3>WAR Plugin - Registrierte REST Routes:</h3>';

$war_routes = array_filter( $routes, function( $route ) {
	return strpos( $route, 'war' ) !== false;
} );

if ( empty( $war_routes ) ) {
	echo '<p style="color: red;"><strong>Keine WAR Routes gefunden!</strong></p>';
} else {
	echo '<ul>';
	foreach ( $war_routes as $route => $route_data ) {
		echo '<li><code>' . esc_html( $route ) . '</code></li>';
	}
	echo '</ul>';
}

echo '</div>';
