<?php
if (!session_id()) {
    session_start();
}

// === Enregistrement des options du board ===
add_action('admin_menu', function() {
    add_menu_page('Multi-Currency Settings', 'Multi-Currency', 'manage_options', 'multi-currency-settings', 'render_multi_currency_settings');
});

function render_multi_currency_settings() {
    if (!function_exists('wc')) {
        echo '<div class="error"><p>WooCommerce must be active to use this plugin.</p></div>';
        return;
}

    $countries = wc()->countries->get_shipping_countries();
    $options = get_option('multi_currency_countries', []);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['multi_currency']) && is_array($_POST['multi_currency'])) {
        $cleaned = [];

        foreach ($_POST['multi_currency'] as $country => $data) {
            $currency = sanitize_text_field($data['currency'] ?? '');
            $symbol = sanitize_text_field($data['symbol'] ?? '');

            if ($currency !== '') {
                $cleaned[$country] = [
                    'currency' => $currency,
                    'symbol'   => $symbol
                ];
            }
        }

        update_option('multi_currency_countries', $cleaned);
        echo '<div class="updated"><p>Settings saved.</p></div>';
}


    echo '<div class="wrap"><h1>Multi-Currency Settings</h1>';
    echo '<form method="post">';

    // Liste des devises + leur symbole
    echo '<h2>Assign currencies to countries</h2>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Country</th><th>Currency</th><th>Symbol</th></tr></thead><tbody>';

    foreach ($countries as $code => $label) {
        $currency = $options[$code]['currency'] ?? '';
        $symbol = $options[$code]['symbol'] ?? '';

        echo '<tr>';
        echo "<td>$label ($code)</td>";
        echo "<td><input type='text' name='multi_currency[$code][currency]' value='" . esc_attr($currency) . "' /></td>";
        echo "<td><input type='text' name='multi_currency[$code][symbol]' value='" . esc_attr($symbol) . "' /></td>";
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<p><input type="submit" class="button-primary" value="Save changes" /></p>';
    echo '</form></div>';
}
