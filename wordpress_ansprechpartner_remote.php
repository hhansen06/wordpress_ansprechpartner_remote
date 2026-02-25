<?php
/**
 * Plugin Name: Ansprechpartner Remote
 * Description: Zeigt Ansprechpartner von einer Remote-API in Visitenkarten an
 * Version: @VERSION@
 * Author: Henrik Hansen
 * License: GPL2
 * Text Domain: ansprechpartner-remote
 */

// Sicherheit: Direkter Zugriff auf die Datei verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Konstanten definieren
define('WAR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAR_PLUGIN_FILE', __FILE__);

// Hilfsfunktionen laden
require_once WAR_PLUGIN_DIR . 'includes/business-card-renderer.php';

// Hauptklasse laden
require_once WAR_PLUGIN_DIR . 'includes/class-main.php';

// Plugin aktivieren/deaktivieren
register_activation_hook(WAR_PLUGIN_FILE, array('WordPress_Ansprechpartner_Remote', 'activate'));
register_deactivation_hook(WAR_PLUGIN_FILE, array('WordPress_Ansprechpartner_Remote', 'deactivate'));

// Plugin initialisieren
WordPress_Ansprechpartner_Remote::init();
