<?php
/*
Plugin Name: Diagram Morceau
Description: Un plugin qui affiche des accords via un shortcode basé sur un ID dans l'URL.
Version: 1.0
Author: Romain ZIEBA
*/

// Ajouter des styles personnalisés
function diagram_morceau_plugin_enqueue_styles() {
    // Enregistrer le style CSS de votre plugin
    wp_enqueue_style('diagram-morceau-plugin-styles', plugins_url('css/diagram-morceau.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'diagram_morceau_plugin_enqueue_styles');


// Ajouter une page de menu d'administration
function diagram_morceau_plugin_menu() {
    add_menu_page(
        'Diagram accords',
        'Diagram accords',
        'manage_options',
        'diagram-morceau-plugin',
        'diagram_morceau_plugin_page'
    );
}
add_action('admin_menu', 'diagram_morceau_plugin_menu');

// Ajouter une sous-page pour ajouter un nouvel accord
function diagram_morceau_plugin_submenu_add() {
    add_submenu_page(
        'diagram-morceau-plugin', // slug du menu parent
        'Ajouter un Accord', // titre de la page
        'Ajouter un Accord', // titre du menu
        'manage_options', // capacité requise pour voir la page
        'diagram-morceau-plugin-add', // slug de la page
        'diagram_morceau_plugin_add_page' // fonction de contenu de la page
    );

    add_submenu_page(
        'diagram-morceau-plugin', // slug du menu parent
        'Paramètres Shortcode',   // titre de la page
        'Paramètres Shortcode',   // titre du menu
        'manage_options',         // capacité requise pour accéder
        'diagram-morceau-plugin-settings', // slug de la page
        'diagram_morceau_plugin_settings_page' // fonction de rappel pour afficher la page
    );
    
    add_submenu_page(
        'diagram-morceau-plugin',
        'Ajouter Shortcode',
        'Ajouter Shortcode',
        'manage_options',
        'diagram-morceau-plugin-add-shortcode',
        'diagram_morceau_plugin_add_shortcode_page'
    );
}
add_action('admin_menu', 'diagram_morceau_plugin_submenu_add');


// Page principale des accords
function diagram_morceau_plugin_page() {
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['index'])) {
        $accords = get_option('diagram_morceau_plugin_accords', []);
        $index = intval($_GET['index']);
        if (isset($accords[$index])) {
            unset($accords[$index]);
            update_option('diagram_morceau_plugin_accords', array_values($accords));
            echo '<div class="updated"><p>Accord supprimé.</p></div>';
        }
    }

    $accords = get_option('diagram_morceau_plugin_accords', []);
    ?>
    <div class="wrap">
        <h1>Accords de Guitare</h1>
        <a href="admin.php?page=diagram-morceau-plugin-add" class="page-title-action">Ajouter un Accord</a>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nom de l'Accord</th>
                    <th>Diagramme</th>
                    <th>URL du Cours</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($accords)) { ?>
                    <?php foreach ($accords as $index => $accord) { ?>
                        <tr>
                            <td><?php echo esc_html($accord['nom']); ?></td>
                            <td><img src="<?php echo esc_url($accord['diagramme']); ?>" alt="<?php echo esc_attr($accord['nom']); ?>" style="max-width: 100px;"></td>
                            <td><a href="<?php echo esc_url($accord['cours']); ?>"><?php echo esc_url($accord['cours']); ?></a></td>
                            <td><a href="admin.php?page=diagram-morceau-plugin&action=delete&index=<?php echo $index; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet accord ?');">Supprimer</a></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4">Aucun accord trouvé.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Page pour ajouter un nouvel accord
function diagram_morceau_plugin_add_page() {
    if ($_POST['submit']) {
        $nom = sanitize_text_field($_POST['nom']);
        $diagramme = esc_url_raw($_POST['diagramme']);
        $cours = esc_url_raw($_POST['cours']);

        $accords = get_option('diagram_morceau_plugin_accords', []);
        $accords[] = ['nom' => $nom, 'diagramme' => $diagramme, 'cours' => $cours];
        update_option('diagram_morceau_plugin_accords', $accords);
        echo '<div class="updated"><p>Accord ajouté.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Ajouter un Accord</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="nom">Nom de l'Accord</label></th>
                    <td><input name="nom" type="text" id="nom" value="" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="diagramme">URL du Diagramme</label></th>
                    <td>
                        <input name="diagramme" type="text" id="diagramme" value="" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cours">URL du Cours</label></th>
                    <td><input name="cours" type="text" id="cours" value="" class="regular-text" /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Ajouter Accord">
            </p>
        </form>
    </div>
    <?php
}

// Fonction pour afficher la page de configuration du shortcode
function diagram_morceau_plugin_settings_page() {
    $shortcode_params_list = get_option('diagram_morceau_plugin_shortcode_params_list', array());
    $accords = get_option('diagram_morceau_plugin_accords', []);

    if (isset($_POST['submit'])) {
        $new_shortcode_params = array();
        for ($i = 1; $i <= 6; $i++) {
            if (isset($_POST['accord_' . $i]) && $_POST['accord_' . $i] != '') {
                $new_shortcode_params['accords'][] = intval($_POST['accord_' . $i]);
            }
        }
        $new_shortcode_params['yootabs_id'] = isset($_POST['yootabs_id']) ? intval($_POST['yootabs_id']) : 0;

        $shortcode_params_list[] = $new_shortcode_params;
        update_option('diagram_morceau_plugin_shortcode_params_list', $shortcode_params_list);
        echo '<div class="updated"><p>Paramètres du shortcode ajouté.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Paramètres des Shortcodes</h1>
        <a href="admin.php?page=diagram-morceau-plugin-add-shortcode" class="page-title-action">Ajouter un Shortcode</a>
        <?php if (!empty($shortcode_params_list)) { ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Accords</th>
                        <th>ID Yootabs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shortcode_params_list as $index => $params) { ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if (!empty($params['accords'])) {
                                    foreach ($params['accords'] as $accord_id) {
                                        if (isset($accords[$accord_id])) {
                                            echo esc_html($accords[$accord_id]['nom']) . ', ';
                                        }
                                    }
                                } ?>
                            </td>
                            <td><?php echo esc_html($params['yootabs_id']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p>Aucun shortcode configuré.</p>
        <?php } ?>
    </div>
    <?php
}

// Fonction pour ajouter un nouveau shortcode
function diagram_morceau_plugin_add_shortcode_page() {
    $accords = get_option('diagram_morceau_plugin_accords', []);

    if ($_POST['submit']) {
        $new_shortcode_params = array();
        for ($i = 1; $i <= 6; $i++) {
            if (isset($_POST['accord_' . $i]) && $_POST['accord_' . $i] != '') {
                $new_shortcode_params['accords'][] = intval($_POST['accord_' . $i]);
            }
        }
        $new_shortcode_params['yootabs_id'] = isset($_POST['yootabs_id']) ? intval($_POST['yootabs_id']) : 0;

        $shortcode_params_list = get_option('diagram_morceau_plugin_shortcode_params_list', array());
        $shortcode_params_list[] = $new_shortcode_params;
        update_option('diagram_morceau_plugin_shortcode_params_list', $shortcode_params_list);
        echo '<div class="updated"><p>Paramètres du shortcode ajouté.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Ajouter un Shortcode</h1>
        <form method="post" action="">
            <table class="form-table">
                <tbody>
                    <?php for ($i = 1; $i <= 6; $i++) { ?>
                        <tr>
                            <th scope="row">Accord <?php echo $i; ?></th>
                            <td>
                                <select name="accord_<?php echo $i; ?>">
                                    <option value="">Sélectionnez un accord</option>
                                    <?php foreach ($accords as $key => $accord) { ?>
                                        <option value="<?php echo $key; ?>"><?php echo esc_html($accord['nom']); ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <th scope="row">ID Yootabs</th>
                        <td>
                            <input type="text" name="yootabs_id" value="" placeholder="ID Yootabs" class="regular-text">
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Ajouter Shortcode">
            </p>
        </form>
    </div>
    <?php
}
// Initialiser les paramètres du plugin
function diagram_morceau_plugin_settings() {
    register_setting('diagram_morceau_plugin_options', 'diagram_morceau_plugin_selected_accords');
    register_setting('diagram_morceau_plugin_options', 'diagram_morceau_plugin_yootab_id');
    
    add_settings_section(
        'diagram_morceau_plugin_section',
        'Sélectionner les accords de guitare et saisir l\'ID de Yootab',
        null,
        'diagram-morceau-plugin'
    );
    
    add_settings_field(
        'diagram_morceau_plugin_selected_accords_field',
        'Accords Sélectionnés',
        'diagram_morceau_plugin_selected_accords_field_callback',
        'diagram-morceau-plugin',
        'diagram_morceau_plugin_section'
    );
    
    add_settings_field(
        'diagram_morceau_plugin_yootab_id_field',
        'ID de Yootab',
        'diagram_morceau_plugin_yootab_id_field_callback',
        'diagram-morceau-plugin',
        'diagram_morceau_plugin_section'
    );
}
add_action('admin_init', 'diagram_morceau_plugin_settings');

// Afficher le champ de sélection des accords
function diagram_morceau_plugin_selected_accords_field_callback() {
    $accords = get_option('diagram_morceau_plugin_accords', []);
    $selected_accords = get_option('diagram_morceau_plugin_selected_accords', []);
    ?>
    <select name="diagram_morceau_plugin_selected_accords[]" multiple style="width: 100%; height: 200px;">
        <?php foreach ($accords as $index => $accord) { ?>
            <option value="<?php echo $index; ?>" <?php echo in_array($index, $selected_accords) ? 'selected' : ''; ?>>
                <?php echo esc_html($accord['nom']); ?>
            </option>
        <?php } ?>
    </select>
    <p>Sélectionnez les accords à afficher.</p>
    <?php
}
// Créer le shortcode
function diagram_morceau_plugin_shortcode($atts) {
    // Récupérer l'ID de l'URL si présent
    if (isset($_GET['id'])) {
        $url_id = intval($_GET['id']);

        // Récupérer les paramètres de shortcode enregistrés
        $shortcode_params_list = get_option('diagram_morceau_plugin_shortcode_params_list', []);

        // Parcourir la liste des paramètres de shortcode
        foreach ($shortcode_params_list as $params) {
            // Vérifier si l'ID Yootabs correspond à l'ID de l'URL
            if (isset($params['yootabs_id']) && intval($params['yootabs_id']) === $url_id) {
                // Récupérer les IDs des accords associés à ce shortcode
                $accord_ids = isset($params['accords']) ? $params['accords'] : [];

                // Récupérer les accords paramétrés dans le plugin
                $accords = get_option('diagram_morceau_plugin_accords', []);

                // Générer le HTML des diagrammes d'accord associés
                $output = '<div class="accords-diagrammes">';
                foreach ($accord_ids as $accord_id) {
                    if (isset($accords[$accord_id])) {
                        $accord = $accords[$accord_id];
                        $output .= '<div class="accord-diagramme">';
                        $output .= '<a href="' . esc_url($accord['cours']) . '">';
                        $output .= '<img src="' . esc_url($accord['diagramme']) . '" alt="' . esc_attr($accord['nom']) . '">';
                        $output .= '</a>';
                        $output .= '</div>';
                    }
                }
                $output .= '</div>'; // Fermeture du div accords-diagrammes

                // Si des diagrammes ont été trouvés, retourner le HTML
                if (!empty($output)) {
                    return $output;
                }
            }
        }
    }

    return 'Aucun diagramme d\'accord trouvé pour cet ID.';
}
add_shortcode('diagram-morceau', 'diagram_morceau_plugin_shortcode');



// Ajouter des styles personnalisés (optionnel)
function diagram_morceau_plugin_styles() {
    wp_enqueue_style('diagram-morceau-plugin-styles', plugins_url('diagram-morceau-plugin.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'diagram_morceau_plugin_styles');
?>
