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

// Doppelte Personen zusammenfassen und Funktionen sammeln
$merged = array();
foreach ($ansprechpartner as $person) {
	$key = '';
	if (!empty($person['email'])) {
		$key = 'email:' . strtolower($person['email']);
	} else {
		$phone_key = $person['telefon'] ?? $person['phone'] ?? '';
		if (!empty($phone_key)) {
			$key = 'phone:' . preg_replace('/[^0-9+]/', '', $phone_key);
		} elseif (!empty($person['name'])) {
			$key = 'name:' . strtolower($person['name']);
		} else {
			$key = 'row:' . md5(wp_json_encode($person));
		}
	}

	if (!isset($merged[$key])) {
		$merged[$key] = $person;
		$merged[$key]['funktionen'] = array();
	}

	$incoming_funktionen = array();
	if (!empty($person['funktion'])) {
		$incoming_funktionen[] = $person['funktion'];
	}
	if (isset($person['funktionen']) && is_array($person['funktionen'])) {
		$incoming_funktionen = array_merge($incoming_funktionen, $person['funktionen']);
	}

	foreach ($incoming_funktionen as $funktion) {
		if (!in_array($funktion, $merged[$key]['funktionen'], true)) {
			$merged[$key]['funktionen'][] = $funktion;
		}
	}
}

$ansprechpartner = array_values($merged);

// Funktionen je Person nach Auswahl-Reihenfolge sortieren
if (!empty($funktionen)) {
	foreach ($ansprechpartner as &$person) {
		if (!empty($person['funktionen']) && is_array($person['funktionen'])) {
			$ordered = array_values(array_intersect($funktionen, $person['funktionen']));
			$remaining = array_values(array_diff($person['funktionen'], $ordered));
			$person['funktionen'] = array_merge($ordered, $remaining);
		}
	}
	unset($person);

	usort($ansprechpartner, function ($a, $b) use ($funktionen) {
		$func_a = is_array($a['funktionen'] ?? null) ? ($a['funktionen'][0] ?? '') : ($a['funktion'] ?? '');
		$func_b = is_array($b['funktionen'] ?? null) ? ($b['funktionen'][0] ?? '') : ($b['funktion'] ?? '');

		$pos_a = array_search($func_a, $funktionen);
		$pos_b = array_search($func_b, $funktionen);

		if ($pos_a === false) {
			$pos_a = PHP_INT_MAX;
		}
		if ($pos_b === false) {
			$pos_b = PHP_INT_MAX;
		}

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
	$html .= '<div class="war-cards-container">';
	foreach ($ansprechpartner as $person) {
		$html .= war_render_business_card($person, $startColor, $endColor);
	}
	$html .= '</div>';
}

$html .= '</div>';

echo $html;
