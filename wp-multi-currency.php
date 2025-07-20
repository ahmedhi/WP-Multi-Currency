<?php
/**
 * Plugin Name: WordPress Multi-Currency
 * Description: Allow different fixed prices per country for variable products in WooCommerce.
 * Version: 1.0.3
 * Author: Ahmed Hilali
 * Text Domain: wp-multi-currency
 */


include_once plugin_dir_path(__FILE__) . 'admin-board.php';

if (!session_id()) {
    session_start();
}

function get_client_ip() {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip_list = explode(',', $_SERVER[$key]);
            return trim($ip_list[0]);
        }
    }

    return '0.0.0.0';
}

// Enregistrer les paramètres
add_action('admin_init', function() {
    register_setting('multi_currency_group', 'multi_currency_countries');

    add_settings_section('multi_currency_section', '', null, 'multi-currency-settings');
});


function get_user_country_code_fallback() {
    // Liste dynamique des pays livrables depuis WooCommerce
    $allowed_countries = array_keys(WC()->countries->get_shipping_countries());
    
    if (
        isset($_SESSION['user_country']) &&
        isset($_SESSION['user_country_timestamp']) &&
        (time() - $_SESSION['user_country_timestamp']) < 3600 // 1h
    ) {
        return in_array($_SESSION['user_country'], $allowed_countries) ? $_SESSION['user_country'] : 'AE';
    }

    $ip = get_client_ip();
    $response = wp_remote_get('http://ip-api.com/json/' . $ip);

    if (is_wp_error($response)) {
        return 'AE';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['countryCode'])) {
        $country = $data['countryCode'];
        $_SESSION['user_country'] = $country;
        $_SESSION['user_country_timestamp'] = time();
        return in_array($country, $allowed_countries) ? $country : 'AE';
    }

    return 'AE';
}

add_filter('woocommerce_currency_symbol', function($currency_symbol, $currency) {
    $assignments = get_option('multi_currency_countries', []);

    foreach ($assignments as $data) {
        if (!is_array($data)) continue;
        if (($data['currency'] ?? '') === $currency) {
            return $data['symbol'] ?? $currency_symbol;
        }
    }

    return $currency_symbol;
}, 10, 2);

add_action('wp_footer', function() {

    if (function_exists('get_user_country_code_fallback')) {
        $country = get_user_country_code_fallback();
    } else {
        echo '⚠️ Fonction get_user_country_code_fallback() non disponible';
    }

    echo 'Footer Country : ' . $country;

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const userCountry = <?= json_encode($country) ?>;
        document.querySelectorAll('[class*="geo-only-"]').forEach(el => {
            const classes = el.className.split(/\s+/);
            const geoClasses = classes.filter(cls => cls.startsWith('geo-only-'));
            const allowedCountries = geoClasses.map(cls => cls.replace('geo-only-', ''));
    
            // Si le pays de l'utilisateur n'est pas dans la liste des pays autorisés → on masque
            if (!allowedCountries.includes(userCountry)) {
                el.style.display = 'none';
            }
        });
    });
    </script>
    <?php

    if (!is_cart() && !is_checkout()) return;

    $currency = html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8');
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const yithPrice = document.querySelector('.ywsbs-price .woocommerce-Price-amount.amount');
        if (!yithPrice) {
            console.warn('YITH Price Element not found');
            return;
        }
        if (yithPrice) {
            const customPrice = <?php echo json_encode(wc_price( get_custom_price_by_country( WC()->cart->get_cart()[array_key_first(WC()->cart->get_cart())]['data'] ?? null ), ['currency' => $currency] )); ?>;
            if (customPrice) {
                yithPrice.innerHTML = customPrice.replace(/<[^>]*>/g, ''); // nettoyage HTML éventuel
            }
        }
    });
    </script>
    <?php

    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    $variation_price = 0;
    $currency = html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8');
    $country = get_user_country_code_fallback();
    
    $variation_price = null;

    // On va tenter de détecter le bon prix
    global $woocommerce;
    if ($woocommerce->cart && sizeof($woocommerce->cart->get_cart()) > 0) {
        foreach ($woocommerce->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if ($product->is_type('variation')) {
                $custom_price = get_custom_price_by_country($product);
                if ($custom_price !== '') {
                    // Calculer le prix TTC à partir du prix personnalisé
                    $variation_price = wc_get_price_including_tax($product, [
                        'qty'   => $cart_item['quantity'],
                        'price' => $custom_price
                    ]);
                    break;
                }
            }
        }
    }

    if (!$variation_price) return; // Pas de prix personnalisé, ne rien faire

    $country = get_user_country_code_fallback();
    $assignments = get_option('multi_currency_countries', []);
    $currency = $assignments[$country]['currency'] ?? get_option('woocommerce_currency');
    $symbol = $assignments[$country]['symbol'] ?? '';


    $group_gcc = ['AE', 'QA', 'SA'];
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userCountry = '<?= esc_js($country) ?>';
            const groupGCC = <?= json_encode($group_gcc) ?>;
            const allowedCountries = groupGCC.includes(userCountry) ? groupGCC : [userCountry];
        
            const observer = new MutationObserver(() => {
                const select = document.querySelector('select[autocomplete="country"]');
                if (!select) return;
        
                // Stop observing après première apparition
                observer.disconnect();
        
                console.log('User country : ' + userCountry);
                console.log('Allowed countries : ' + allowedCountries);
                // Supprimer toutes les options sauf celles autorisées
                Array.from(select.options).forEach(option => {
                    console.log('4 . ' + option.value );
                    if (!allowedCountries.includes(option.value)) {
                        console.log('Remove option !');
                        option.remove();
                    }
                });
                console.log('5');
        
                // Sélectionner automatiquement le bon pays
                if (allowedCountries.includes(userCountry)) {
                    select.value = userCountry;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
                console.log('6');
            });
        
           observer.observe(document.body, { childList: true, subtree: true });
        });

        
        (function() {
            let observer;
            const userCurrency = <?= json_encode($currency) ?>;
            const userCurrencySymbol = <?= json_encode($symbol) ?>;

            const formatAndInject = () => {
                const priceBlocks = document.querySelectorAll('.wc-block-cart-item__prices span.price, .recurring-totals .price');
                const yithTarget = document.querySelector('.ywsbs-price');

                if (!yithTarget || priceBlocks.length === 0) return;

                let total = 0;
                let currency = '';

                priceBlocks.forEach(el => {
                    if (el.textContent.includes('/ 3 months')) {
                        const match = el.textContent.match(/([\d,.\s]+)\s*([A-Z€]+)/i);
                        if (match) {
                            const amount = parseFloat(match[1].replace(/\s/g, '').replace('.', '').replace(',', '.'));
                            total += amount;
                            currency = match[2];
                        }
                    }
                });

                priceBlocks.forEach(el => {
                    if (el.textContent.includes('/ 3 months')) {
                        const formatted = new Intl.NumberFormat('fr-FR', {
                            style: 'currency',
                            currency: userCurrency,
                            minimumFractionDigits: 2
                        }).format(total);

                        el.textContent = `${formatted} / 3 months`;
                    }
                });

                if (currency && total > 0 ) {
                    const formatted = new Intl.NumberFormat('fr-FR', {
                        style: 'currency',
                        currency: userCurrency,
                        minimumFractionDigits: 2
                    }).format(total);

                    // ✋ Stop observing to avoid loop
                    observer.disconnect();
                    yithTarget.innerHTML = `${formatted} / 3 months`;
                    observer.observe(document.body, { childList: true, subtree: true });
                }
            };

            // Création de l'observer
            observer = new MutationObserver(() => {
                formatAndInject();
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Appel initial avec un léger délai
            setTimeout(() => {
                formatAndInject();
            }, 200);
        })();


        (function waitForCheckoutPrices() {
            const selectors = {
                individual: '.wc-block-components-order-summary-item__individual-prices',
                total: '.wc-block-components-order-summary-item__total-price',
                recurring: '.ywsbs-price'
            };

            const allReady = Object.values(selectors).every(sel => document.querySelector(sel));
            if (!allReady) {
                setTimeout(waitForCheckoutPrices, 300);
                return;
            }

            const formattedPrice = '<?= esc_js(number_format($variation_price, 2, ',', ' ')) ?>';
            const currency = '<?= esc_js($currency) ?>';
            const finalText = `${formattedPrice} ${currency} / 3 months`;

            // ✅ Mise à jour des blocs produit
            [selectors.individual, selectors.total].forEach(sel => {
                const el = document.querySelector(sel);
                if (el && el.textContent.includes('/ 3 months')) {
                    el.innerHTML = finalText;
                }
            });

            // ✅ Mise à jour de la récurrence
            const recurringEl = document.querySelector(selectors.recurring);
            if (recurringEl) {
                recurringEl.innerHTML = `${finalText} <small class="tax_label">(incl. VAT)</small><br><small class="tax_label">(ex. shipping)</small>`;
            }
        })();
    </script>

    <?php
});


add_action('init', function() {
    $country = get_user_country_code_fallback();
    
    //WC()->customer->set_country($country);
    //WC()->customer->set_shipping_country($country);
});

// 1. Ajouter des champs personnalisés à chaque variation
add_action('woocommerce_variation_options_pricing', function($loop, $variation_data, $variation) {
    $assignments = get_option('multi_currency_countries', []);

    foreach ($assignments as $country => $data) {
        if (!isset($data['currency'])) continue;

        $currency_code = strtolower($data['currency']);
        $meta_key = "_price_{$currency_code}";
        $label = "Prix {$country} ({$data['currency']})";

        woocommerce_wp_text_input([
            'id' => "{$meta_key}[{$loop}]",
            'label' => __($label, 'woocommerce'),
            'desc_tip' => true,
            'description' => __('Prix personnalisé pour ce pays.', 'woocommerce'),
            'value' => get_post_meta($variation->ID, $meta_key, true),
            'type' => 'number',
            'custom_attributes' => [
                'step' => 'any',
                'min' => '0'
            ]
        ]);
    }
}, 10, 3);

// 2. Enregistrer les champs personnalisés
add_action('woocommerce_save_product_variation', function($variation_id, $i) {
    $assignments = get_option('multi_currency_countries', []);

    foreach ($assignments as $country => $data) {
        if (!isset($data['currency'])) continue;

        $currency_code = strtolower($data['currency']);
        $meta_key = "_price_{$currency_code}";

        if (isset($_POST[$meta_key][$i])) {
            update_post_meta($variation_id, $meta_key, wc_clean($_POST[$meta_key][$i]));
        }
    }
}, 10, 2);


// 3. Déterminer le pays de l'utilisateur (fallback = UAE)
function get_user_country_code() {
    if (function_exists('get_user_country_code_fallback')) {
        $info = get_user_country_code_fallback();
        return $info['country'] ?? 'AE';
    }
    return 'AE';
}

// 4. Obtenir le prix personnalisé
function get_custom_price_by_country($variation) {
    $country = get_user_country_code_fallback();
    $assignments = get_option('multi_currency_countries', []);

    if (!isset($assignments[$country]['currency'])) {
        return ''; // Aucun mapping trouvé
    }

    $currency_code = strtolower($assignments[$country]['currency']); // ex: mad, eur
    $meta_key = "_price_{$currency_code}";

    return get_post_meta($variation->get_id(), $meta_key, true);
}

// 5. Remplacer les prix affichés dynamiquement
add_filter('woocommerce_product_variation_get_price', function($price, $variation) {
    $custom = get_custom_price_by_country($variation);
    return ($custom !== '') ? $custom : $price;
}, 10, 2);

add_filter('woocommerce_product_variation_get_regular_price', function($price, $variation) {
    return apply_filters('woocommerce_product_variation_get_price', $price, $variation);
}, 10, 2);

// 6. Forcer devise par pays
add_filter('woocommerce_currency', function($currency) {
    $country = get_user_country_code_fallback();
    $assignments = get_option('multi_currency_countries', []);
    return $assignments[$country]['currency'] ?? get_option('woocommerce_currency');
});