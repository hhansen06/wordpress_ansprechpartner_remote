<?php
/**
 * Server-side Rendering für den Ansprechpartner Block
 */

$display_mode = isset( $attributes['displayMode'] ) ? sanitize_key( $attributes['displayMode'] ) : 'single';
$sparte = isset( $attributes['sparte'] ) ? sanitize_text_field( $attributes['sparte'] ) : '';
$funktion = isset( $attributes['funktion'] ) ? sanitize_text_field( $attributes['funktion'] ) : '';

// Wenn keine Sparte ausgewählt, zeige Hinweis
if ( empty( $sparte ) ) {
	echo '<div class="war-placeholder"><p>Bitte wählen Sie eine Sparte aus</p></div>';
	return;
}

// Ansprechpartner abrufen
$ansprechpartner = WordPress_Ansprechpartner_Remote::get_ansprechpartner( $sparte, $funktion );

if ( empty( $ansprechpartner ) ) {
	echo '<div class="war-placeholder"><p>Keine Ansprechpartner gefunden</p></div>';
	return;
}

// HTML rendern
$html = '<div class="wp-block-war-ansprechpartner war-display-' . esc_attr( $display_mode ) . '">';

if ( 'single' === $display_mode || ( 'all' === $display_mode && count( $ansprechpartner ) === 1 ) ) {
	// Single Card Mode
	$person = reset( $ansprechpartner );
	$html .= war_render_business_card( $person );
} else {
	// All Mode - mehrere Karten
	$html .= '<div class="war-cards-container">';
	foreach ( $ansprechpartner as $person ) {
		$html .= war_render_business_card( $person );
	}
	$html .= '</div>';
}

$html .= '</div>';

echo $html;
