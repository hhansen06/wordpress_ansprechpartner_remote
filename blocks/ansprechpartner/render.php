<?php
/**
 * Server-side Rendering für den Ansprechpartner Block
 */

$display_mode = isset($attributes['displayMode']) ? sanitize_key($attributes['displayMode']) : 'single';
$sparte = isset($attributes['sparte']) ? sanitize_text_field($attributes['sparte']) : '';
$funktionen = isset($attributes['funktionen']) && is_array($attributes['funktionen']) ? array_map('sanitize_text_field', $attributes['funktionen']) : array();
$startColor = isset($attributes['startColor']) ? sanitize_text_field($attributes['startColor']) : '#667eea';
$endColor = isset($attributes['endColor']) ? sanitize_text_field($attributes['endColor']) : '#764ba2';

// Wenn keine Sparte ausgewählt, zeige Hinweis
if (empty($sparte)) {
    echo '<div class="war-placeholder"><p>Bitte wählen Sie eine Sparte aus</p></div>';
    return;
}

// Ansprechpartner abrufen
$ansprechpartner = WordPress_Ansprechpartner_Remote::get_ansprechpartner($sparte, $funktionen);

// Sortiere Ansprechpartner nach Reihenfolge der ausgewählten Funktionen
if (!empty($funktionen) && count($funktionen) > 1) {
    usort($ansprechpartner, function ($a, $b) use ($funktionen) {
        $pos_a = array_search($a['funktion'] ?? '', $funktionen);
        $pos_b = array_search($b['funktion'] ?? '', $funktionen);

        // Wenn Position nicht gefunden, ans Ende
        if ($pos_a === false)
            $pos_a = PHP_INT_MAX;
        if ($pos_b === false)
            $pos_b = PHP_INT_MAX;

        return $pos_a - $pos_b;
    });
}

if (empty($ansprechpartner)) {
    echo '<div class="war-placeholder"><p>Keine Ansprechpartner gefunden</p></div>';
    return;
}

// HTML rendern
$html = '<div class="wp-block-war-ansprechpartner war-display-' . esc_attr($display_mode) . '">';

// Wenn Funktionen gefiltert sind (egal wie viele), zeige alle gefilterten Karten
if (!empty($funktionen)) {
	// Funktionen gefiltert - zeige alle Ergebnisse
	$html .= '<div class="war-cards-container">';
	foreach ($ansprechpartner as $person) {
		$html .= war_render_business_card($person, $startColor, $endColor);
	}
	$html .= '</div>';
} else if ('single' === $display_mode || ('all' === $display_mode && count($ansprechpartner) === 1)) {
	// Single Card Mode - ohne Funktionsfilter
	$person = reset($ansprechpartner);
	$html .= war_render_business_card($person, $startColor, $endColor);
} else {
	// All Mode - ohne Funktionsfilter
    }
    $html .= '</div>';
}

$html .= '</div>';

echo $html;
