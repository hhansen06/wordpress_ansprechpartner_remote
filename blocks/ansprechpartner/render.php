<?php
/**
 * Server-side Rendering für den Ansprechpartner Block
 */

$display_mode = isset( $attributes['displayMode'] ) ? sanitize_key( $attributes['displayMode'] ) : 'single';
$sparte = isset( $attributes['sparte'] ) ? sanitize_text_field( $attributes['sparte'] ) : '';
$funktionen = isset( $attributes['funktionen'] ) && is_array( $attributes['funktionen'] ) ? array_map( 'sanitize_text_field', $attributes['funktionen'] ) : array();
$startColor = isset( $attributes['startColor'] ) ? sanitize_text_field( $attributes['startColor'] ) : '#667eea';
$endColor = isset( $attributes['endColor'] ) ? sanitize_text_field( $attributes['endColor'] ) : '#764ba2';

// Wenn keine Sparte ausgewählt, zeige Hinweis
if ( empty( $sparte ) ) {
	echo '<div class="war-placeholder"><p>Bitte wählen Sie eine Sparte aus</p></div>';
	return;
}

// Ansprechpartner abrufen
$ansprechpartner = WordPress_Ansprechpartner_Remote::get_ansprechpartner( $sparte, $funktionen );

if ( empty( $ansprechpartner ) ) {
	echo '<div class="war-placeholder"><p>Keine Ansprechpartner gefunden</p></div>';
	return;
}

// HTML rendern
$html = '<div class="wp-block-war-ansprechpartner war-display-' . esc_attr( $display_mode ) . '">';

// Wenn mehrere Funktionen ausgewählt sind, zeige alle Karten
if ( count( $funktionen ) > 1 ) {
	// Multiple functions - zeige alle Karten
	$html .= '<div class="war-cards-container">';
	foreach ( $ansprechpartner as $person ) {
		$html .= war_render_business_card( $person, $startColor, $endColor );
	}
	$html .= '</div>';
} else if ( 'single' === $display_mode || ( 'all' === $display_mode && count( $ansprechpartner ) === 1 ) ) {
	// Single Card Mode
	$person = reset( $ansprechpartner );
	$html .= war_render_business_card( $person, $startColor, $endColor );
} else {
	// All Mode - mehrere Karten
	$html .= '<div class="war-cards-container">';
	foreach ( $ansprechpartner as $person ) {
		$html .= war_render_business_card( $person, $startColor, $endColor );
	}
	$html .= '</div>';
}

$html .= '</div>';

echo $html;
