# WordPress Ansprechpartner Remote

Ein WordPress-Plugin, das Ansprechpartner von einer Remote-API in eleganten Visitenkarten anzeigt.

## Features

- **Block Widget** für den Gutenberg Block Editor
- **Zwei Anzeigemodi**:
  - **Einzelne Karte**: Zeigt einen spezifischen Ansprechpartner basierend auf Sparte und Funktion
  - **Alle untereinander**: Zeigt alle Ansprechpartner einer Sparte untereinander
- **AJAX-basierte Datenladung** für zuverlässige Datenabfrage
- **Responsive Design** - Mobile-freundlich
- **REST API Integration** mit MSG Sulingen API
- **Emoji-Icons** für Kontaktdaten (📧 Email, ☎️ Telefon, 📱 Mobil)

## Installation

1. Plugin in den `/wp-content/plugins/wordpress_ansprechpartner_remote/` Ordner hochladen
2. Plugin im WordPress Admin aktivieren
3. Block "Ansprechpartner" in Gutenberg Block Editor finden und verwenden

## Verwendung

### Im Block Editor

1. Einen neuen Block hinzufügen
2. Nach "Ansprechpartner" suchen
3. Block auswählen
4. In der Sidebar "Block-Einstellungen":
   - **Anzeigemodus** wählen (Einzelne Karte oder Alle untereinander)
   - **Sparte** aus der Liste wählen
   - Bei "Einzelne Karte": Optional **Funktion** wählen

### Verfügbare Sparten und Funktionen

Die Sparten werden automatisch von der API geladen:
- https://www.msg-sulingen.de/wp-json/vereinsverwaltung/v1/sparten

## Cache Management

### Hinweis zu Caching

Das Plugin wurde für die Entwicklung mit deaktiviertem Caching konfiguriert, um schnelle Änderungen an API-Daten zu ermöglichen. Das Caching kann später bei Bedarf für die Produktion aktiviert werden.

In der Entwicklung werden Assets (JavaScript/CSS) mit dynamischer Versionierung geladen, um Browser-Caching zu vermeiden.

## API Integration

### Unterstützte API-Endpunkte

```
GET /wp-json/vereinsverwaltung/v1/sparten
    Alle Sparten abrufen

GET /wp-json/vereinsverwaltung/v1/ansprechpartner
    Alle Ansprechpartner abrufen

GET /wp-json/vereinsverwaltung/v1/ansprechpartner/{sparte}
    Ansprechpartner einer Sparte abrufen

GET /wp-json/vereinsverwaltung/v1/ansprechpartner/sparte-{id}
    Ansprechpartner einer Sparte (mit ID) abrufen
```

### API Response Format

```json
[
  {
    "name": "Max Mustermann",
    "funktion": "Trainer",
    "sparte_name": "Volleyball",
    "sparte_id": "spart_123456",
    "email": "max@example.com",
    "phone": "+49 123 456789",
    "mobile": "+49 160 123456",
    "avatar_url": "https://example.com/foto.jpg",
    "address": "Straße 1, 12345 Stadt"
  }
]
```

**Hinweis:** Das Plugin unterstützt mehrere Feldnamen-Varianten für Flexibilität:
- Foto: `avatar_url` oder `foto`
- Telefon: `phone` oder `telefon`
- Mobil: `mobile` oder `mobil`
- Sparte: `sparte_name` oder `sparte`

## Customization

### Visitenkarten-Style anpassen

Bearbeiten Sie die CSS-Datei: `/assets/css/ansprechpartner.css`

Wichtigste CSS-Klassen:
- `.war-card` - Visitenkarten Container (max-width: 400px)
- `.war-card-header` - Header mit Gradient-Hintergrund
- `.war-card-avatar` - Avatar im Kreis (120x120px)
- `.war-card-content` - Inhalts-Section
- `.war-card-name` - Name
- `.war-card-function` - Funktionsbezeichnung
- `.war-contact-item` - Kontaktinformation mit Icon
- `.war-contact-icon` - Icon für Kontakttyp

### Renderierung anpassen

Die Server-side Renderierung erfolgt in: `/blocks/ansprechpartner/render.php`

Sie können die `render_business_card()` Funktion modifizieren, um das Layout anzupassen.

## Technische Details

- **Plugin Type**: Gutenberg Block Plugin
- **Rendering**: Server-side Rendering (SSR)
- **Datenladung**: Admin AJAX API (primär) + REST API (Fallback)
- **Asset-Versionierung**: Dynamisch mit `time()` für Entwicklung
- **Caching**: Deaktiviert für Entwicklung (kann für Production aktiviert werden)
- **Admin Panel**: WordPress Settings API

## Requirements

- WordPress 5.0+
- PHP 7.2+
- Internetverbindung für API-Zugriff

## Support

Für Fragen oder Bugs: https://www.msg-sulingen.de

## License

GPL2 oder höher

## Changelog

### Version 1.1.0
- Emoji-Icons für Kontaktdaten (📧 Email, ☎️ Telefon, 📱 Mobil)
- Sparte-Anzeige aus Visitenkarten entfernt
- Card-Breite erhöht auf 400px
- Email-Label entfernt, nur Link angezeigt
- Caching für Entwicklung deaktiviert
- AJAX-basierte Datenladung implementiert
- Mehrere API-Feldnamen-Varianten unterstützen

### Version 1.0.0
- Initial Release
- Block Widget mit zwei Anzeigemodi
- Caching-System
- Admin-Panel
