<?php
/**
 * Business Card Renderer
 * Hilfsfunktion zum Rendern von Visitenkarten
 */

if ( ! function_exists( 'war_render_business_card' ) ) {
	/**
	 * Einzelne Visitenkarte rendern
	 *
	 * @param array $person Ansprechpartner-Daten
	 * @param string $startColor Gradient Start-Farbe
	 * @param string $endColor Gradient End-Farbe
	 * @return string HTML
	 */
	function war_render_business_card( $person, $startColor = '#667eea', $endColor = '#764ba2' ) {
		$html = '<div class="war-card">';
		
		// Avatar/Foto Header mit Gradient-Farben
		$gradient_style = 'style="background: linear-gradient(135deg, ' . esc_attr( $startColor ) . ' 0%, ' . esc_attr( $endColor ) . ' 100%);"';
		$html .= '<div class="war-card-header" ' . $gradient_style . '>';
		
		// Debug: Foto-Struktur als HTML-Kommentar
		$html .= '<!-- Foto-Daten: ' . esc_html( json_encode( $person['avatar_url'] ?? $person['foto'] ?? 'leer' ) ) . ' -->';
		
		// Avatar - Foto oder Fallback
		$have_foto = false;
		$foto_url = '';
		
		// Prüfe verschiedene möglich API-Feldnamen
		if ( ! empty( $person['avatar_url'] ) ) {
			$foto_url = $person['avatar_url'];
			$have_foto = true;
		} elseif ( ! empty( $person['foto'] ) ) {
			if ( is_array( $person['foto'] ) && ! empty( $person['foto']['url'] ) ) {
				$foto_url = $person['foto']['url'];
				$have_foto = true;
			} elseif ( is_string( $person['foto'] ) && ! empty( $person['foto'] ) ) {
				$foto_url = $person['foto'];
				$have_foto = true;
			}
		}
		
		if ( $have_foto && ! empty( $foto_url ) ) {
			$html .= '<div class="war-card-avatar">';
			$html .= '<img src="' . esc_url( $foto_url ) . '" alt="' . esc_attr( $person['name'] ?? 'Avatar' ) . '" class="war-avatar-image" onerror="this.style.display=\'none\'">';
			$html .= '</div>';
		}
		
		// Fallback: Initiale wenn kein Foto
		if ( ! $have_foto && ! empty( $person['name'] ) ) {
			$initiale = strtoupper( substr( $person['name'], 0, 1 ) );
			$fallback_style = 'style="background: linear-gradient(135deg, ' . esc_attr( $startColor ) . ' 0%, ' . esc_attr( $endColor ) . ' 100%);"';
			$html .= '<div class="war-card-avatar war-card-avatar-fallback" ' . $fallback_style . '>';
			$html .= '<span class="war-avatar-initiale">' . esc_html( $initiale ) . '</span>';
			$html .= '</div>';
		}
		
		$html .= '</div>';

		$html .= '<div class="war-card-content">';

		// Name
		if ( ! empty( $person['name'] ) ) {
			$html .= '<h3 class="war-card-name">' . esc_html( $person['name'] ) . '</h3>';
		}

		// Funktion
		if ( ! empty( $person['funktion'] ) ) {
			$html .= '<p class="war-card-function">' . esc_html( $person['funktion'] ) . '</p>';
		}

		// Kontaktinfos
		$html .= '<div class="war-card-contact">';

		// Email
		if ( ! empty( $person['email'] ) ) {
			$html .= '<div class="war-contact-item">';
			$html .= '<span class="war-contact-icon">📧</span> ';
			$html .= '<a href="mailto:' . esc_attr( $person['email'] ) . '">' . esc_html( $person['email'] ) . '</a>';
			$html .= '</div>';
		}

		// Telefon (verschiedene Feldnamen)
		$phone = $person['telefon'] ?? $person['phone'] ?? '';
		if ( ! empty( $phone ) ) {
			$html .= '<div class="war-contact-item">';
			$html .= '<span class="war-contact-icon">☎️</span> ';
			$html .= '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>';
			$html .= '</div>';
		}

		// Mobil
		$mobil = $person['mobil'] ?? $person['mobile'] ?? '';
		if ( ! empty( $mobil ) ) {
			$html .= '<div class="war-contact-item">';
			$html .= '<span class="war-contact-icon">📱</span> ';
			$html .= '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $mobil ) ) . '">' . esc_html( $mobil ) . '</a>';
			$html .= '</div>';
		}

		$html .= '</div>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
}
