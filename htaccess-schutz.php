<?php
/*
Plugin Name: .htaccess-Schutz
Plugin URI: https://mvarken.de
Description: Erstellt eine .htaccess-Datei mit Schutz für deine Website.
Version: 1.0
Author: mvarken
Author URI: https://github.com/mvarken
*/

// Füge ein Menü im Backend hinzu
add_action('admin_menu', 'htaccess_schutz_menu');

function htaccess_schutz_menu() {
    add_options_page('htaccess-Schutz', 'htaccess-Schutz', 'manage_options', 'htaccess-schutz', 'htaccess_schutz_einstellungen');
}

function htaccess_schutz_einstellungen() {
    // Überprüfe, ob der Benutzer über ausreichende Berechtigungen verfügt
    if (!current_user_can('manage_options')) {
        wp_die('Du hast keine ausreichenden Berechtigungen, um auf diese Seite zuzugreifen.');
    }

    // Aktualisiere die Einstellungen, wenn das Formular gesendet wurde
    if (isset($_POST['submit'])) {
        $aktiviert = isset($_POST['aktiviert']) ? '1' : '0';
        $benutzername = sanitize_text_field($_POST['benutzername']);
        $passwort = $_POST['passwort'];

        // Aktualisiere die Einstellungen in der Datenbank
        update_option('htaccess_schutz_aktiviert', $aktiviert);
        update_option('htaccess_schutz_benutzername', $benutzername);
        update_option('htaccess_schutz_passwort', $passwort);

        // Erstelle die .htaccess-Datei mit den aktualisierten Einstellungen
        htaccess_schutz_erstellen($aktiviert, $benutzername, $passwort);
    }

    // Hole die aktuellen Einstellungen aus der Datenbank
    $aktiviert = get_option('htaccess_schutz_aktiviert', '0');
    $benutzername = get_option('htaccess_schutz_benutzername', '');
    $passwort = get_option('htaccess_schutz_passwort', '');

    ?>
    <div class="wrap">
        <h1>htaccess-Schutz</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Schutz aktivieren</th>
                    <td>
                        <label for="aktiviert">
                            <input type="checkbox" id="aktiviert" name="aktiviert" value="1" <?php checked($aktiviert, '1'); ?> />
                            Aktivieren
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Benutzername</th>
                    <td>
                        <input type="text" id="benutzername" name="benutzername" value="<?php echo esc_attr($benutzername); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Passwort</th>
                    <td>
                        <input type="password" id="passwort" name="passwort" value="<?php echo esc_attr($passwort); ?>" />
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" class="button-primary" value="Einstellungen speichern" />
            </p>
        </form>
    </div>
    <?php
}

// Funktion zum Erstellen der .htaccess-Datei
function htaccess_schutz_erstellen($aktiviert, $benutzername, $passwort) {
    $htaccess = '';

    if ($aktiviert) {
        // Erstelle die .htaccess-Datei mit Schutz
        $htaccess .= 'AuthType Basic' . PHP_EOL;
        $htaccess .= 'AuthName "Geschützter Bereich"' . PHP_EOL;
        $htaccess .= 'AuthUserFile ' . ABSPATH . '.htpasswd' . PHP_EOL;
        $htaccess .= 'Require valid-user' . PHP_EOL;

        // Erstelle die .htpasswd-Datei mit Benutzername und Passwort
        $htpasswd = $benutzername . ':' . password_hash($passwort, PASSWORD_DEFAULT) . PHP_EOL;
        file_put_contents(ABSPATH . '.htpasswd', $htpasswd);
    } else {
        // Lösche die .htaccess- und .htpasswd-Datei
        if (file_exists(ABSPATH . '.htaccess')) {
            unlink(ABSPATH . '.htaccess');
        }
        if (file_exists(ABSPATH . '.htpasswd')) {
            unlink(ABSPATH . '.htpasswd');
        }
    }

    // Aktualisiere die .htaccess-Datei
    file_put_contents(ABSPATH . '.htaccess', $htaccess);
}

// Aktiviere den Schutz beim Plugin-Aktivierung
register_activation_hook(__FILE__, 'htaccess_schutz_aktivieren');

function htaccess_schutz_aktivieren() {
    $aktiviert = get_option('htaccess_schutz_aktiviert', '0');
    $benutzername = get_option('htaccess_schutz_benutzername', '');
    $passwort = get_option('htaccess_schutz_passwort', '');

    htaccess_schutz_erstellen($aktiviert, $benutzername, $passwort);
}

// Deaktiviere den Schutz beim Plugin-Deaktivierung
register_deactivation_hook(__FILE__, 'htaccess_schutz_deaktivieren');

function htaccess_schutz_deaktivieren() {
    htaccess_schutz_erstellen(false, '', '');
}
