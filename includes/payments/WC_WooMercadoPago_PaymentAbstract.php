<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_WooMercadoPago_Payments
 */
class WC_WooMercadoPago_PaymentAbstract extends WC_Payment_Gateway
{

    public $field_forms_order;
    public $id;
    public $method_title;
    public $title;
    public $ex_payments = array();
    public $method;
    public $method_description;
    public $auto_return;
    public $success_url;
    public $failure_url;
    public $pending_url;
    public $installments;
    public $two_cards_mode;
    public $form_fields;
    public $coupon_mode;
    public $payment_type;
    public $checkout_type;
    public $stock_reduce_mode;
    public $date_expiration;
    public $hook;
    public $supports;
    public $icon;
    public $description;
    public $mp_category_id;
    public $store_identificator;
    public $debug_mode;
    public $custom_domain;
    public $binary_mode;
    public $gateway_discount;
    public $site_data;
    public $log;
    public $sandbox;
    public $mp;
<<<<<<< HEAD
    public $checkout_credential_production;
    public $mp_public_key_test;
    public $mp_access_token_test;
    public $mp_public_key_prod; 
    public $mp_access_token_prod;
=======
    public $notification;
>>>>>>> cf0cc2ffd186f59149e80b6c3fbc59b369474e2b

    /**
     * WC_WooMercadoPago_PaymentAbstract constructor.
     * @throws WC_WooMercadoPago_Exception
     */
    public function __construct()
    {
        $this->mp_public_key_test = get_option('_mp_public_key_test', '');
        $this->mp_access_token_test = get_option('_mp_access_token_test', '');
        $this->mp_public_key_prod = $this->get_option('_mp_public_key_prod', '');
        $this->mp_access_token_prod = $this->get_option('_mp_access_token_prod', '');
        $this->checkout_credential_token_production = get_option('checkout_credential_production', 'no');
        $this->description = $this->get_option('description');
        $this->mp_category_id = get_option('_mp_category_id', 0);
        $this->store_identificator = get_option('_mp_store_identificator', 'WC-');
        $this->debug_mode = get_option('_mp_debug_mode', 'no');
        $this->custom_domain = get_option('_mp_custom_domain', '');
        // TODO: fazer logica para _mp_category_name usado na preference
        $this->binary_mode = get_option('binary_mode', 'no');
        $this->gateway_discount = get_option('gateway_discount', 0);
        $this->sandbox = get_option('_mp_sandbox_mode', false);
        $this->supports = array('products', 'refunds');
        $this->icon = $this->getMpIcon();
        $this->site_data = WC_WooMercadoPago_Module::get_site_data();
        $this->log = WC_WooMercadoPago_Log::init_mercado_pago_log();
        $this->mp = WC_WooMercadoPago_Module::getMpInstanceSingleton($this);
    }

    /**
     * @return string
     */
    public function getMpLogo()
    {
        return '<img width="200" height="52" src="' . plugins_url('../assets/images/mplogo.png', plugin_dir_path(__FILE__)) . '"><br><br>';
    }

    /**
     * @return mixed
     */
    public function getMpIcon()
    {
        return apply_filters('woocommerce_mercadopago_icon', plugins_url('../assets/images/mercadopago.png', plugin_dir_path(__FILE__)));
    }

    /**
     * @param $description
     * @return string
     */
    public function getMethodDescription($description)
    {
        return '<img width="200" height="52" src="' . plugins_url('../assets/images/mplogo.png', plugin_dir_path(__FILE__)) . '"><br><br><strong>' . __($description, 'woocommerce-mercadopago') . '</strong>';
    }

    /**
     * @param $label
     * @return array
     */
    public function getFormFields($label)
    {
        $this->init_form_fields();
        $this->init_settings();
        $_site_id_v1 = get_option('_site_id_v1', '');
        $form_fields = array();
        if (empty($_site_id_v1)) {
            $form_fields['no_credentials_title'] = $this->field_no_credentials();
            return $form_fields;
        }

        $form_fields['enabled'] = $this->field_enabled($label);
        if (empty($this->settings['enabled']) || 'no' == $this->settings['enabled']) {
            $form_fields_enable = array();
            $form_fields_enable['enabled'] = $form_fields['enabled'];
            return $form_fields_enable;
        }

        $form_fields['description'] = $this->field_description();
        $valid_credentials = false;
        if (!$valid_credentials) { }

        $form_fields['checkout_credential_title'] = $this->field_checkout_credential_title();
        $form_fields['checkout_credential_subtitle'] = $this->field_checkout_credential_subtitle();
        $form_fields['checkout_credential_production'] = $this->field_checkout_credential_production();
        $form_fields['_mp_public_key_test'] = $this->field_checkout_credential_publickey_test();
        $form_fields['_mp_access_token_test'] = $this->field_checkout_credential_accesstoken_test();
        $form_fields['_mp_public_key_prod'] = $this->field_checkout_credential_publickey_prod();
        $form_fields['_mp_access_token_prod'] = $this->field_checkout_credential_accesstoken_prod();
        $form_fields['checkout_credential_description'] = $this->field_checkout_credential_description();
        $form_fields['_mp_category_id'] = $this->field_category_store();
        $form_fields['_mp_store_identificator'] = $this->field_mp_store_identificator();
        $form_fields['checkout_advanced_settings'] = $this->field_checkout_advanced_settings();
        $form_fields['_mp_debug_mode'] = $this->field_debug_mode();
        $form_fields['_mp_custom_domain'] = $this->field_custom_url_ipn();
        $form_fields['binary_mode'] = $this->field_binary_mode();
        $form_fields['gateway_discount'] = $this->field_gateway_discount();
        $form_fields['checkout_ready_title'] = $this->field_checkout_ready_title();
        $form_fields['checkout_ready_description'] = $this->field_checkout_ready_description();

        return $form_fields;
    }

    /**
     * @param $formFields
     * @param $ordenation
     * @return array
     */
    public function sortFormFields($formFields, $ordenation)
    {
        $array = array();
        foreach ($ordenation as $order => $key) {
            $array[$key] = $formFields[$key];
            unset($formFields[$key]);
        }
        return array_merge_recursive($array, $formFields);
    }

    /**
     * @param $label
     * @return array
     */
    public function field_enabled($label)
    {
        $enabled = array(
            'title' => __('Habilitar', 'woocommerce-mercadopago'),
            'type' => 'checkbox',
            'label' => __('Habilitar', 'woocommerce-mercadopago'),
            'default' => 'no',
            'description' => __('Activa la experiencia de Mercado Pago en el checkout de tu tienda.', 'woocommerce-mercadopago')
        );
        return $enabled;
    }

    /**
     * @return array
     */
    public function field_checkout_credential_title()
    {
        $field_checkout_credential_title = array(
            'title' => __('Ahora tus credenciales están activas', 'woocommerce-mercadopago'),
            'type' => 'title'
        );
        return $field_checkout_credential_title;
    }

    /**
     * @return array
     */
    public function field_checkout_credential_subtitle()
    {
        $field_checkout_credential_subtitle = array(
            'title' => __('Elegí cómo vas a operar', 'woocommerce-mercadopago'),
            'type' => 'title'
        );
        return $field_checkout_credential_subtitle;
    }

    /**
     * @return array
     */
    public function field_checkout_credential_production()
    {
        $checkout_credential_production = array(
            'title' => __('Producción', 'woocommerce-mercadopago'),
            'type' => 'checkbox',
            'label' => __('NO / SI', 'woocommerce-mercadopago'),
            'default' => 'no',
            'description' => __('SÍ: cuando estés listo para vender.', 'woocommerce-mercadopago'),
        );
        return $checkout_credential_production;
    }

    /**
     * @return array
     */
    public function field_checkout_credential_publickey_test()
    {
        $mp_public_key_test = array(
            'title' => __('Public key de Prueba', 'woocommerce-mercadopago'),
            'type' => 'text',
            'description' => __('Haz las pruebas que quieras.', 'woocommerce-mercadopago'),
            'default' => ''
        );

        return $mp_public_key_test;
    }

    /**
     * @return array
     */
    public function field_checkout_credential_accesstoken_test()
    {
        $mp_access_token_test = array(
            'title' => __('Credenciales de Prueba', 'woocommerce-mercadopago'),
            'type' => 'text',
            'description' => __('Haz las pruebas que quieras.', 'woocommerce-mercadopago'),
            'default' => ''
        );

        return $mp_access_token_test;
    }

    /**
     * @return array
     */
    public function field_checkout_credential_publickey_prod()
    {
        $mp_public_key_prod = array(
            'title' => __('Public key de Producción', 'woocommerce-mercadopago'),
            'type' => 'text',
            'description' => __('Empieza a recibir pagos.', 'woocommerce-mercadopago'),
            'default' => ''
        );

        return $mp_public_key_prod;
    }

 /**
     * @return array
     */
    public function field_checkout_credential_accesstoken_prod()
    {
        $mp_public_key_prod = array(
            'title' => __('Credenciales de Producción', 'woocommerce-mercadopago'),
            'type' => 'text',
            'description' => __('Empieza a recibir pagos.', 'woocommerce-mercadopago'),
            'default' => ''
        );

        return $mp_public_key_prod;
    }

      /**
     * @return array
     */
    public function field_checkout_credential_description()
    {
        $checkout_credential_description = array(
            'title' => sprintf(
                __('<b>Atención:</b> Crea una cuenta en Mercado Pago para obtener tus credenciales. %s en Mercado Pago para ir a Producción y cobrar en tu tienda.', 'woocommerce-mercadopago'),
                '<a href="' . esc_url(admin_url('admin.php?page=mercado-pago-settings')) . '">' . __('Homologa tu cuenta', 'woocommerce-mercadopago') .
                    '</a>'
            ),
            'type' => 'title'
        );
        return $checkout_credential_description;
    }

    /**
     * @return array
     */
    public function field_description()
    {
        $description = array(
            'title' => __('Descripción de la tienda', 'woocommerce-mercadopago'),
            'type' => 'text',
            'description' => __('Este nombre aparecerá en la factura de tus clientes.', 'woocommerce-mercadopago'),
            'default' => __('Pay with Mercado Pago', 'woocommerce-mercadopago')
        );
        return $description;
    }

    /**
     * @return array
     */
    public function field_category_store()
    {
        $category_store = WC_WooMercadoPago_Configs::getCategories();
        $option_category = array();
        for ($i = 0; $i < count($category_store['store_categories_id']); $i++) {
            $option_category[$category_store['store_categories_id'][$i]] = __($category_store['store_categories_id'][$i], 'woocommerce-mercadopago');
        }
        $field_category_store = array(
            'title' => __('Categoría de la tienda', 'woocommerce-mercadopago'),
            'type' => 'select',
            'description' => __('¿A qué categoría pertenecen tus productos? Elige la que mejor los caracteriza (elige “otro” si tu producto es demasiado específico).', 'woocommerce-mercadopago'),
            'default' => __('Categrorías', 'woocommerce-mercadopago'),
            'options' => $option_category
        );
        return $field_category_store;
    }

    /**
     * @return array
     */
    public function field_mp_store_identificator()
    {
        $store_identificator = array(
            'title' => __('ID de la tienda', 'woocommerce-mercadopago'),
            'type' => 'text',
            'description' => __('Usa un número o prefijo para identificar pedidos y pagos provenientes de esta tienda.', 'woocommerce-mercadopago'),
            'default' => __('WC-', 'woocommerce-mercadopago')
        );
        return $store_identificator;
    }

    /**
     * @return array
     */
    public function field_checkout_advanced_settings()
    {
        $checkout_options_explanation = array(
            'title' => __('Ajustes avanzados', 'woocommerce-mercadopago'),
            'type' => 'title'
        );
        return $checkout_options_explanation;
    }

    /**
     * @return array
     */
    public function field_debug_mode()
    {
        $debug_mode = array(
            'title' => __('Modo Debug y Log', 'woocommerce-mercadopago'),
            'type' => 'checkbox',
            'label' => __('Enable debug mode', 'woocommerce-mercadopago'),
            'default' => 'no',
            'description' => __('Graba las acciones de tu tienda en nuestro archivo de cambios para tener más información de soporte.', 'woocommerce-services'),
            'desc_tip' => __('Depuramos la información de nuestro archivo de cambios.', 'woocommerce-services')
        );
        return $debug_mode;
    }

    /**
     * @return array
     */
    public function field_custom_url_ipn()
    {
        $custom_url_ipn = array(
            'title' => __('URL para IPN', 'woocommerce-mercadopago'),
            'type' => 'text',
            'description' => __('Ingresá una URL para recibir  notificaciones de pagos.', 'woocommerce-mercadopago'),
            'default' => '',
            'desc_tip' => __('IPN (Instant Payment Notification) es una notificación de eventos que se realizan en tu plataforma y que se envía de un servidor a otro mediante una llamada HTTP POST. Consulta más información en nuestras guías.', 'woocommerce-services')
        );
        return $custom_url_ipn;
    }

    /**
     * @return array
     */
    public function field_no_credentials()
    {
        $noCredentials = array(
            'title' => sprintf(
                __('It appears that your credentials are not properly configured.<br/>Please, go to %s and configure it.', 'woocommerce-mercadopago'),
                '<a href="' . esc_url(admin_url('admin.php?page=mercado-pago-settings')) . '">' . __('Mercado Pago Settings', 'woocommerce-mercadopago') .
                    '</a>'
            ),
            'type' => 'title'
        );
        return $noCredentials;
    }

    /**
     * @return array
     */
    public function field_title()
    {
        $title = array(
            'title' => __('Title', 'woocommerce-mercadopago'),
            'type' => 'text',
            'description' => __('Title shown to the client in the checkout.', 'woocommerce-mercadopago'),
            'default' => __('Mercado Pago', 'woocommerce-mercadopago')
        );

        return $title;
    }

    /**
     * @return array
     */
    public function field_binary_mode()
    {
        $binary_mode = array(
            'title' => __('Modo binario', 'woocommerce-mercadopago'),
            'type' => 'checkbox',
            'label' => __('Activar modo binario', 'woocommerce-mercadopago'),
            'default' => 'no',
            'description' => __('Acepta y rechaza pagos de forma automática. ¿Quieres que lo activemos?', 'woocommerce-mercadopago'),
            'desc_tip' => __('DSi activas el modo binario no podrás dejar pagos pendientes. Esto puede afectar la prevención de fraude. Dejalo inactivo para estar respaldado por nuestra propia herramienta.', 'woocommerce-services')
        );
        return $binary_mode;
    }

    /**
     * @return array
     */
    public function field_gateway_discount()
    {
        $gateway_discount = array(
            'title' => __('Descuentos Gateway', 'woocommerce-mercadopago'),
            'type' => 'number',
            'description' => __('Elige un valor porcentual que quieras descontara tus clientes por pagar con Mercado Pago.', 'woocommerce-mercadopago'),
            'default' => '0',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '-99',
                'max' => '99'
            )
        );
        return $gateway_discount;
    }

    /**
     * @return array
     */
    public function field_checkout_ready_title()
    {
        $checkout_options_title = array(
            'title' => __('¿Todo listo para el despegue de tus ventas?', 'woocommerce-mercadopago'),
            'type' => 'title'
        );
        return $checkout_options_title;
    }

    /**
     * @return array
     */
    public function field_checkout_ready_description()
    {
        $checkout_options_subtitle = array(
            'title' => __('Visita tu tienda como si fueras uno de tus mejores cliente y revisa que todo esté bien. Si ya saliste a Producción, trae a tus mejores clientes y aumenta tus ventas con la mejor experiencia de compra online.', 'woocommerce-mercadopago'),
            'type' => 'title'
        );
        return $checkout_options_subtitle;
    }

    /**
     * Mensage credentials not configured.
     *
     * @return string Error Mensage.
     */
    public function credential_missing_message()
    {
        echo '<div class="error"><p><strong> Mercado Pago: </strong>' . sprintf(__('It appears that your credentials are not properly configured.<br/>Please, go to %s and configure it.', 'woocommerce-mercadopago'), '<a href="' . esc_url(admin_url('admin.php?page=mercado-pago-settings')) . '">' . __('Mercado Pago Settings', 'woocommerce-mercadopago') . '</a>') . '</p></div>';
    }


    /**
     * @return bool
     */
    public function is_available()
    {
        if (!did_action('wp_loaded')) {
            return false;
        }
        global $woocommerce;
        $w_cart = $woocommerce->cart;
        // Check for recurrent product checkout.
        if (isset($w_cart)) {
            if (WC_WooMercadoPago_Module::is_subscription($w_cart->get_cart())) {
                return false;
            }
        }

        $_mp_public_key = get_option('_mp_public_key');
        $_mp_access_token = get_option('_mp_access_token');
        $_site_id_v1 = get_option('_site_id_v1');

        return ('yes' == $this->settings['enabled']) && !empty($_mp_public_key) && !empty($_mp_access_token) && !empty($_site_id_v1);
    }


    /**
     * @return mixed
     */
    public function admin_url()
    {
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=')) {
            return admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $this->id);
        }
        return admin_url('admin.php?page=woocommerce_settings&tab=payment_gateways&section=' . get_class($this));
    }
}
