<?php

class Entegrasyon
{

    private $registry;
    private $db;
    private $session;
    private $url;
    private $config;
    private $log;
    private $customer;
    private $currency;
    private $tax;
    private $installed_markets = array();

    public $marketPlaces = array('n11' => 'N11', 'gg' => 'Gittigidiyor', 'hb' => 'Hepsiburada', 'ty' => 'Trendyol', 'eptt' => 'EpttAvm', 'amz' => 'Amazon', 'cs' => 'Çiçeksepeti');

    public function __construct($registry)
    {

        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->session = $registry->get('session');
        $this->url = $registry->get('url');
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
        $this->customer = $registry->get('customer');
        $this->currency = $registry->get('currency');
        $this->tax = $registry->get('tax');
        $this->registry = $registry;

        $log = new \Log('easyentegre.log');


        $this->getInstalled();

    }

    public function getProductForUpdate2($code, $product_id, $catalog_url = '')
    {


        $product_info = $this->getProduct($product_id);

        $category_setting = $this->getMarketPlaceCategory($product_id, $code);

        $manufacturer_setting = isset($product_info['manufacturer_id']) ? $this->getMarketPlaceManufacturer($product_info['manufacturer_id'], $code) : array();
        $product_setting = $this->getSettingData($code, 'product', $product_id);
        $defaults = $this->getDefaults($category_setting, $manufacturer_setting, $product_setting, $code);


        $product_info['product_setting'] = $product_setting;
        $product_info['have_discount'] = $product_info['special'] && $defaults['product_special'] ? true : false;
        if ($this->config->get("easy_setting_price_place") && $product_info[$this->config->get("easy_setting_price_place")]) {

            $product_info['price'] = $product_info[$this->config->get("easy_setting_price_place")];
            $product_info['spacial'] = $product_info[$this->config->get("easy_setting_price_place")];

        }


        if (isset($product_setting[$code . '_product_sale_price']) || isset($product_setting[$code . '_product_sale_price']) && !isset($product_setting[$code . '_product_list_price'])) {
            $product_data['sale_price'] = $product_setting[$code . '_product_sale_price'];

            if (isset($product_setting[$code . '_product_list_price'])) {

                if ($product_setting[$code . '_product_list_price'] > $product_setting[$code . '_product_sale_price']) {
                    $product_info['have_discount'] = true;
                }
                $product_info['list_price'] = $product_setting[$code . '_product_list_price'];

            }

        } else {
            $product_info['list_price'] = $this->calculatePrice($product_info['price'], $defaults, $product_info['tax_class_id'], $code, $product_info);
        }


        if (isset($product_setting[$code . '_product_sale_price']) || isset($product_setting[$code . '_product_sale_price']) && !isset($product_setting[$code . '_product_list_price'])) {
            $product_info['sale_price'] = $product_setting[$code . '_product_sale_price'];
            if (!isset($product_setting[$code . '_product_list_price'])) {
                $product_info['list_price'] = $product_info['sale_price'];
                $product_info['have_discount'] = false;

            }
        } else {
            $product_info['sale_price'] = $product_info['special'] && $defaults['product_special'] ? $this->calculatePrice($product_info['special'], $defaults, $product_info['tax_class_id'], $code, $product_info) : $product_info['list_price'];
        }


        // $product_info['list_price'] = $this->calculatePrice($product_info['price'], $defaults, $product_info['tax_class_id'], $code);
        //$product_info['sale_price'] = $product_info['special'] && $defaults['product_special'] ? $this->calculatePrice($product_info['special'], $defaults, $product_info['tax_class_id'], $code) : $product_info['list_price'];


        $product_info['defaults'] = $defaults;
        $product_info['kdv'] = $this->getKdvRange($product_info['tax_class_id']);

        $product_info['quantity'] = $product_info['quantity'] > 9999 ? 100 : $product_info['quantity'];

        if (isset($category_setting[$code . '_category_id'])) {
            $category_info = explode('|', $category_setting[$code . '_category_id']);

            $product_info['category_id'] = $category_info[0];
        } else {

            $product_info['category_id'] = false;
        }


        if ($this->config->get($code . '_setting_variant')) {

            if ($this->isVarianterProduct($product_info['product_id'])) {


                $product_variants = $this->getPoductVariants($product_info['product_id']);

                if ($code == 'n11' || $code == 'gg') {
                    $variants = $this->getMarketVariant($product_variants, $code, $category_info[0], $product_info['product_id'], $this->config->get($code . '_setting_model_prefix') . $product_info['model'], $catalog_url, array('tax_class_id' => $product_info['tax_class_id'], 'defaults' => $defaults));

                } else {

                    $variants = $this->getMarketVariantNotNeedMatchOption($product_variants, $code, $product_id, '', $catalog_url);

                }
                // $variants = $this->getVariants($code, $product_info['product_id'], $category_info[0]);

                $product_info['variants'] = $variants;
            } else {

                $product_info['variants'] = false;

            }


        } else {

            $product_info['variants'] = false;

        }

        if (!$product_info['variants']) {


            if ($this->config->get('easy_setting_critical_stock')) {
                $product_info['quantity'] = $product_info['quantity'] <= $this->config->get('easy_setting_critical_stock') ? 0 : $product_info['quantity'];
            }

        }


        if ($this->config->get('easy_setting_list_price') && $this->config->get($code . '_setting_product_special')) {

            $product_info['list_price'] = $product_info['sale_price'];
            $product_info['special'] = false;

        }

        return $product_info;


    }

    public function getSelectedAttributes($code, $product_setting, $category_setting)
    {


        if (isset($product_setting['selected_attributes'])) {
            $selected_attributes = $product_setting['selected_attributes'];

        } else if (isset($category_setting['selected_attributes'])) {
            $selected_attributes = $category_setting['selected_attributes'];

        } else {
            $selected_attributes = false;

        }


        $attributes = array();
        if ($selected_attributes) {

            foreach ($selected_attributes as $selected_attribute) {
                if ($selected_attribute['value']) {
                    $attributes[] = $selected_attribute;
                }
            }
        }

        return $attributes;

    }


    public function log($code, $message, $bulk = false)
    {

        $bulk = $bulk ? 'Bulk' : 'Manual';

        $date = date("d.m.y");

        $log = new Log('easyentegre-' . $date . '.log');
        $log->write($code . ':' . $bulk . '-' . $message);

    }

    public function priceFormat($price)
    {

        return number_format((float)$price, 2, '.', '');
    }


    public function clientConnect($post_data, $service, $code, $mode = false, $apidebug = false)
    {

        if (!defined('HTTP_CATALOG')) {
            $catalog_url = HTTP_SERVER;
        } else {

            $catalog_url = HTTP_CATALOG;
        }

        if ($service == 'login' || $service == 'register') {

            $phone = isset($post_data['phone']) ? $post_data['phone'] : 0;
            $user_data = array('domain' => $catalog_url, 'ip' => $_SERVER['SERVER_ADDR'], 'username' => $post_data['username'], 'phone' => $phone, 'password' => $post_data['password']);

        } else {

            $user_data = array('domain' => $catalog_url, 'ip' => '', 'username' => $this->config->get('mir_username'), 'password' => $this->config->get('mir_password'));
            if (!isset($post_data['api_info'])) {
                $post_data['api_info'] = $this->getSavedApiInfos($code);
            }

        }
        $post_data['user_data'] = $user_data;

        $json_data = json_encode(array('code' => $code, 'debug' => $apidebug, 'service' => $service, 'data' => $post_data));
        //return addslashes($json_data);

        $url = 'https://www.opencart.gen.tr/entegrasyon-service';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
        //curl_setopt($curl, CURLOPT_TIMEOUT_MS, 5000);
        // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);


        if ($mode) {
            print_r($result);
        }
        // print_r($result);

        if (curl_error($curl)) {


            // echo  "CURL Error: " . curl_error($curl);
            return json_encode(array('status' => false, 'message' => curl_error($curl), 'result' => array()));

        } else {

            return json_decode($result, true);

        }


    }

    public function updateCronRecycle()
    {
        $url = 'https://www.opencart.gen.tr/index.php?route=api/cron/update_cron_recycle&domain_id=' . $this->config->get('mir_domain_id');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($curl, CURLOPT_TIMEOUT_MS, 5000);
        // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
    }

    public function sendNotification($data, $debug = false)
    {

        if (!defined('HTTP_CATALOG')) {
            $catalog_url = HTTP_SERVER;
        } else {

            $catalog_url = HTTP_CATALOG;
        }
        $data['domain'] = $catalog_url;

        $data['email'] = $this->config->get('easy_setting_email') ? $this->config->get('easy_setting_email') : $this->config->get('config_email');
        $data['easy_visibility'] = $this->config->get('easy_visibility');
        $url = 'https://www.easyentegre.com/notification';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
        //curl_setopt($curl, CURLOPT_TIMEOUT_MS, 5000);
        // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        if ($debug) {
            print_r($result);
        }


    }

    protected function getSavedApiInfos($code)
    {

        if ($code == 'gg') {

            $params = array(
                'appKey' => $this->config->get('gg_api_anahtari'),
                'appSecret' => $this->config->get('gg_api_sifre'),
                'nick' => $this->config->get('gg_site_kullanici_adi'),
                'password' => $this->config->get('gg_site_kullanici_sifresi'),
                'auth_user' => $this->config->get('gg_role_kullanici_adi'),
                'auth_pass' => $this->config->get('gg_role_kullanici_sifresi'),
            );
        } else if ($code == 'n11') {

            $params = array(
                'appKey' => $this->config->get('n11_api_key'),
                'appSecret' => $this->config->get('n11_api_secret'),

            );

        } else if ($code == 'hb') {

            $params = array(
                'merchant_id' => $this->config->get('hb_merchant_id'),
            );

        } else if ($code == 'ty') {


            $params = array(
                'username' => $this->config->get('ty_api_anahtari'),
                'password' => $this->config->get('ty_api_sifresi'),
                'shopId' => $this->config->get('ty_satici_numarasi')
            );

        } else if ($code == 'cs') {


            $params = array(
                'apiKey' => $this->config->get('cs_api_anahtari')
                // 'shopId' => $this->config->get('cs_satici_numarasi')
            );

        } else if ($code == 'eptt') {

            $params = array(
                'username' => $this->config->get('eptt_kullanici_adi'),
                'password' => $this->config->get('eptt_api_parola'),
                'shopId' => $this->config->get('eptt_magaza_id')
            );

        }


        return $params;

    }

    public function getCustomerGroups($data = array())
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "customer_group cg LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (cg.customer_group_id = cgd.customer_group_id) WHERE cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $sort_data = array(
            'cgd.name',
            'cg.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY cgd.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function checkOrderByMarketPlaceOrderId($orderNumber)
    {


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "es_order where market_order_id='" . $orderNumber . "'");
        return $query->num_rows;
    }

    public function getManufacturer($manufacturer_id)
    {

        $query = $this->db->query("select * from " . DB_PREFIX . "manufacturer where manufacturer_id='" . $manufacturer_id . "'");
        return $query->row;

    }

    public function getManufacturerNameByProductId($product_id)
    {

        $query = $this->db->query("select m.name from " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) where p.product_id='" . $product_id . "'");
        if ($query->num_rows) {
            return $query->row['name'];
        } else {
            return '';
        }

    }

    public function getManufacturerNameByProductId2($product_id)
    {

        $query = $this->db->query("select m.name,m.manufacturer_id from " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) where p.product_id='" . $product_id . "'");
        if ($query->num_rows) {
            return $query->row;
        } else {
            return '';
        }

    }

    private function getInstalled()
    {


        foreach ($this->marketPlaces as $key => $marketPlace) {
            if ($this->config->get($key . '_status')) {

                $this->installed_markets[] = $key;
            }

        }

    }

    public function getMarkets()
    {
        return $this->marketPlaces;

    }

    public function deleteSetting($code, $store_id = 0)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");
    }

    public function editSetting($code, $data, $store_id = 0)
    {

        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

        foreach ($data as $key => $value) {
            if (substr($key, 0, strlen($code)) == $code) {
                if (!is_array($value)) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
                }
            }
        }
    }

    public function getMatchedOptions($category_id)
    {
        $matched = array();
        $query = $this->db->query("select * from " . DB_PREFIX . "es_option where category_id='" . $category_id . "'");

        foreach ($query->rows as $row) {
            $matched[$row['market_option_id']] = $row['oc_option_id'];
        }
        return $matched;

    }

    public function isOptionsMatched($market_category_id, $code)
    {
        $need_match = array();


        $query = $this->db->query("select * from " . DB_PREFIX . "es_attribute where category_id='" . $market_category_id . "'");

        $attributes = unserialize($query->row['attribute']);

        foreach ($attributes['variants'] as $variant) {

            $need_match[] = $variant['name'];
        }


        $query = $this->db->query("select * from " . DB_PREFIX . "es_option where category_id='" . $market_category_id . "'");

        foreach ($query->rows as $key => $row) {

            if (in_array($row['market_option_name'], $need_match)) {

                if (($key = array_search($row['market_option_name'], $need_match)) !== false) {
                    unset($need_match[$key]);
                }
            }
        }

        return $need_match;
    }


    public function getMarketOptions($category_id, $product_id)
    {
        $sql = "SELECT * from oc_product_option po LEFT JOIN oc_product_option_value pov ON(pov.product_option_id=po.product_option_id) LEFT JOIN oc_option_description od ON(od.option_id=po.option_id) LEFT JOIN oc_option_value_description ovd ON(pov.option_value_id=ovd.option_value_id) left join oc_es_option eo ON(eo.oc_option_id=po.option_id) LEFT JOIN oc_es_option_value eov ON(eov.oc_option_value_id=ovd.option_value_id) where po.product_id='" . $product_id . "' and eo.category_id='" . $category_id . "' and eov.market_option_value_id!='' ";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getMatchedOptionValues($matched_option_id)
    {
        $matched = array();
        $query = $this->db->query("select * from " . DB_PREFIX . "es_option_value where matched_option_id='" . $matched_option_id . "'");

        foreach ($query->rows as $row) {
            $matched[$row['oc_option_value_id']] = $row['market_option_value_id'];
        }
        return $matched;
    }


    private function findOption($values, $find)
    {
        foreach ($values as $value) {

            if ($value['oc_option_value_id'] = $find) return $value;


        }

    }


    public function getProductByMarketId($productCode, $code)
    {
        $sql = "select product_id from `" . DB_PREFIX . "es_product_to_marketplace` where  $code like '%" . $productCode . "%'";


        $query = $this->db->query("select product_id from `" . DB_PREFIX . "es_product_to_marketplace` where  $code like '%" . $productCode . "%' ");


        if ($query->num_rows) {

            return $this->getProduct($query->row['product_id']);

        } else {

            return false;
        }

    }


    public function getMarketVariants($product_id, $code)
    {

        $query = $this->db->query("select * from " . DB_PREFIX . "es_market_product where oc_product_id='" . $product_id . "' and code='" . $code . "'");

        return $query->rows;

    }

    public function getProduct($product_id)
    {
        if ($this->config->get('easy_setting_customer_group')) {
            $customer_group_id = $this->config->get('easy_setting_customer_group');
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        //echo $this->config->get($code.'_setting_customer_group');

        $sql = "SELECT DISTINCT *,p.manufacturer_id,m.name as manufacturer,p.image,pd.name,(SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$customer_group_id . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) left join " . DB_PREFIX . "manufacturer m on(p.manufacturer_id=m.manufacturer_id) LEFT JOIN " . DB_PREFIX . "es_product_to_marketplace p2m ON(p.product_id=p2m.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $query = $this->db->query($sql);
        if ($query->row) {
            $query->row['quantity'] = $query->row['quantity'] > 9999 ? 9999 : $query->row['quantity'];
            $query->row['name'] = htmlspecialchars_decode($query->row['name']);
        }

        return $query->row;
    }

    public function getMarketPlace($code, $catalog_url = '')
    {
        $markets = unserialize($this->config->get('mir_marketplaces'));
        foreach ($markets as $market) {
            if ($market['code'] == $code) {
                return array(
                    'name' => $market['marketname'],
                    'status' => $market['status'],
                    'member_type' => $market['usergroupname'],
                    'code' => $market['code'],
                    'domain_id' => $market['domain_id'],
                    'domain_marketplace_id' => $market['domain_marketplace_id'],
                    'logo' => $catalog_url . 'image/deneme.jpg'
                );
            }
        }


    }


    public function updateMarketplaceProdutcsAfterOrder($products, $catalog_url, $mode = false)
    {
        $updated = array();

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        foreach ($products as $product) {

            foreach ($this->marketPlaces as $code => $marketPlace) {
                $product_info = $this->getProduct($product, $code);
                $marketPlace_info = $product_info[$code] ? unserialize($product_info[$code]) : array();

                if ($marketPlace_info) {

                    $product_info = $this->getProductForUpdate($code, $product_info, 0, $mode, $catalog_url);
                    $product_info['model'] = $this->config->get($code . '_setting_model_prefix') . $product_info['model'];

                    $post_data['request_data'] = $product_info;
                    $post_data['market'] = $this->getMarketPlace($code, $catalog_url);
                    $marketplace_data = unserialize($product_info[$code]);


                    if ($this->config->get('easy_setting_critical_stock')) {
                        $product_info['quantity'] = $product_info['quantity'] <= $this->config->get('easy_setting_critical_stock') ? 0 : $product_info['quantity'];
                    }

                    if ($product_info['quantity'] <= 0) {
                        $product_info['quantity'] = 0;
                        $post_data['request_data']['quantity'] = 0;

                        if ($code == "n11"){
                            $n11_close_sale = 1;
                        }else{
                            $n11_close_sale = 0;
                        }
                    }




                    if (!$marketplace_data['sale_status'] && $code != 'hb') {


                        $result['message'] = 'Ürün Satışa kapalı olduğu için güncellenmedi! ';
                        $updated[$product_info['product_id']][$code] = array('status' => false, 'message' => $result['message']);

                    } else {



                        if ($n11_close_sale){
                            $result = $this->clientConnect($post_data, 'close_for_sale', $code, false);

                        }else{
                            $result = $this->clientConnect($post_data, 'update_basic', $code, false);

                        }

                        if ($result['status']) {

                            $marketplace_data = unserialize($product_info[$code]);
                            $marketplace_data['commission'] = $product_info['defaults']['commission'];
                            $marketplace_data['price'] = $product_info['sale_price'];

                            $this->addMarketplaceProduct($product_info['product_id'], $marketplace_data, $code);

                            $updated[$product_info['product_id']][$code] = array('status' => true);
                            //echo $i . ' : ' . $marketPlace['name'] . ' : ' . $product_info['name'] . '<strong style="color: #0baf5c"> Güncellendi</strong><br>';


                        } else {

                            $updated[$product_info['product_id']][$code] = array('status' => false, 'message' => $result['message']);


                            $error = $this->getError($product_info['product_id'], $code);
                            if ($error) {
                                $this->updateError($product_info['product_id'], $code, 3, $result['message']);
                            } else {
                                $this->addError($product_info['product_id'], $code, 3, $result['message']);
                            }

                        }
                    }

                    if ($product_info['quantity'] <= 0) {
                        $marketplace_data['sale_status'] = 0;
                        $marketplace_data['approval_status'] = 1;
                        $this->addMarketplaceProduct($product_info["product_id"], $marketplace_data, $code);
                    }


                    $logmesage = $product_info['model'] . ' Action:Update After Order';;
                    if ($mode) {
                        $logmesage .= '- Otomatik - Cron';
                    } else {
                        $logmesage .= '- Manuel';
                    }

                    $logmesage .= ' - Update content:' . 'Stok & Fiyat';

                    $logmesage .= '- Stock - :' . $product_info['quantity'] . ' - Sale Price:' . $product_info['sale_price'] . ' - List Price:' . $product_info['list_price'];

                    $logmesage .= '- Result:' . $result['message'];

                    $this->log($code, $logmesage, false);


                }

            }

        }


        return $updated;

    }

    public function getProductForUpdate($code, $product_info, $commission = 0, $mode = true, $catalog_url = '')
    {


        //include_once('././config.php');

        $category_setting = $this->getMarketPlaceCategory($product_info['product_id'], $code);
        $manufacturer_setting = isset($product_info['manufacturer_id']) ? $this->getMarketPlaceManufacturer($product_info['manufacturer_id'], $code) : array();
        $product_setting = $this->getSettingData($code, 'product', $product_info['product_id']);
        $defaults = $this->getDefaults($category_setting, $manufacturer_setting, $product_setting, $code);

        if ($commission) {

            $defaults['commission'] = $commission;
        } else {

            /* if($product_info[$code]){
                    $defaults['commission']=unserialize($product_info[$code])['commission'];
                }
            */

        }

        $product_info['product_setting'] = $product_setting;
        $product_info['have_discount'] = $product_info['special'] && $defaults['product_special'] ? true : false;
        if ($this->config->get("easy_setting_price_place") && $product_info[$this->config->get("easy_setting_price_place")]) {

            $product_info['price'] = $product_info[$this->config->get("easy_setting_price_place")];
            $product_info['spacial'] = $product_info[$this->config->get("easy_setting_price_place")];

        }


        if (isset($product_setting[$code . '_product_sale_price']) || isset($product_setting[$code . '_product_sale_price']) && !isset($product_setting[$code . '_product_list_price'])) {
            $product_data['sale_price'] = $product_setting[$code . '_product_sale_price'];

            if (isset($product_setting[$code . '_product_list_price'])) {

                if ($product_setting[$code . '_product_list_price'] > $product_setting[$code . '_product_sale_price']) {
                    $product_info['have_discount'] = true;
                }
                $product_info['list_price'] = $product_setting[$code . '_product_list_price'];

            }

        } else {
            $product_info['list_price'] = $this->calculatePrice($product_info['price'], $defaults, $product_info['tax_class_id'], $code, $product_info);
        }


        if (isset($product_setting[$code . '_product_sale_price']) || isset($product_setting[$code . '_product_sale_price']) && !isset($product_setting[$code . '_product_list_price'])) {
            $product_info['sale_price'] = $product_setting[$code . '_product_sale_price'];
            if (!isset($product_setting[$code . '_product_list_price'])) {
                $product_info['list_price'] = $product_info['sale_price'];
                $product_info['have_discount'] = false;

            }
        } else {
            $product_info['sale_price'] = $product_info['special'] && $defaults['product_special'] ? $this->calculatePrice($product_info['special'], $defaults, $product_info['tax_class_id'], $code, $product_info) : $product_info['list_price'];
        }


        // $product_info['list_price'] = $this->calculatePrice($product_info['price'], $defaults, $product_info['tax_class_id'], $code);
        //$product_info['sale_price'] = $product_info['special'] && $defaults['product_special'] ? $this->calculatePrice($product_info['special'], $defaults, $product_info['tax_class_id'], $code) : $product_info['list_price'];


        $product_info['defaults'] = $defaults;
        $product_info['title'] = $product_info['name'];
        $product_info['kdv'] = $this->getKdvRange($product_info['tax_class_id']);

        $product_info['quantity'] = $product_info['quantity'] > 9999 ? 100 : $product_info['quantity'];



        if (isset($category_setting[$code . '_category_id'])) {
            $category_info = explode('|', $category_setting[$code . '_category_id']);
            $product_info['category_id'] = $category_info[0];
        } else {

            $product_info['category_id'] = false;
        }


        if ($this->config->get($code . '_setting_barkod_place')) {

            $product_info['product_setting'][$code . '_barcode'] = $product_info[$this->config->get($code . '_setting_barkod_place')];

        }

        if (isset($category_info[0])) {
            $is_category_varianter = $this->isCategoryVarianter($category_info[0]);

        } else {

            $is_category_varianter = true;

        }
        if($code=='n11' || $code=='eptt'){

            $is_category_varianter = true;
        }


        $product_info['images'] = $mode ? $this->getImagesByMarketPlace($product_info['product_id'], $product_info['image'], $code, $catalog_url) : array();

        if ($this->config->get($code . '_setting_variant')) {
            if ($this->isVarianterProduct($product_info['product_id']) && $is_category_varianter ) {

                $product_variants = $this->getPoductVariants($product_info['product_id']);

                if (($code == 'ty' || $code == 'hb' || $code == 'cs') && !$mode) {

                    $variants = $this->getMarketVariantNotNeedMatchOption($product_variants, $code, $product_info['product_id'], $this->config->get($code . '_setting_model_prefix') . $product_info['model'], $catalog_url, array('tax_class_id' => $product_info['tax_class_id'], 'defaults' => $defaults));

                } else {

                    $category_id=$category_info[0]?$category_info[0]:0;

                        $variants = $this->getMarketVariant($product_variants, $code,$category_id , $product_info['product_id'], $this->config->get($code . '_setting_model_prefix') . $product_info['model'], $catalog_url, array('tax_class_id' => $product_info['tax_class_id'], 'defaults' => $defaults));



                }
                // $variants = $this->getVariants($code, $product_info['product_id'], $category_info[0]);


                $product_info['variants'] = $variants;
            } else {

                $product_info['variants'] = false;

            }


        } else {

            $product_info['variants'] = false;

        }
        $message = '';
        if (!$product_info['variants']) {


            if ($this->config->get('easy_setting_critical_stock')) {
                $product_info['quantity'] = $product_info['quantity'] <= $this->config->get('easy_setting_critical_stock') ? 0 : $product_info['quantity'];

                $message .= ' Ürün kritik stoğun altında kaldığı için stoğu 0 olarak belirlendi. kritik stok ayarınızı ayarlar sayfasından güncelleyebilirsiniz..';


            }

        }

        if ($this->config->get('easy_setting_list_price') && $this->config->get($code . '_setting_product_special')) {

            $product_info['list_price'] = $product_info['sale_price'];
            $product_info['special'] = false;

        }

        if ($mode) {

            if ($defaults['additional_content']) {
                $information = $this->getInformationDescriptions($defaults['additional_content']);

                $product_info['description'] .= '<br>' . $information;

            }


            if (isset($product_setting[$code . '_product_desciption'])) {

                $product_info['description'] = $product_setting[$code . '_product_desciption'];
            }

            $product_info['model_prefix'] = $this->config->get($code . '_setting_model_prefix');
        }

        if ($mode) {
            $message = '';
            if ($code == 'ty' || $code == 'hb') {
                if (!$product_info['manufacturer_id']) {
                    $message .= ' Ürününüz bir markaya ait olmalıdır!. Katalog->Ürünler bölümünden ürününüze bir marka ekleyin';

                } else if ($code == 'ty') {

                    if (!isset($manufacturer_setting['ty_manufacturer_id'])) {
                        $message .= ' Marka Eşleştirmesi yapmalısınız!.';
                    } else {

                        $product_info['manufacturer_id'] = $manufacturer_setting['ty_manufacturer_id'];

                    }

                }

            }

            if ($code == 'ty') {
                $product_info['attributes'] = isset($product_setting['selected_attributes']) ? $this->getTyAttributes($product_setting['selected_attributes']) : null;

            }

        }


        return $product_info;


    }


    public function isCategoryVarianter($category_id)
    {


        $query = $this->db->query("select attribute from " . DB_PREFIX . "es_attribute where category_id='" . $category_id . "' ");


        if ($query->num_rows) {
            $atributes = unserialize($query->row['attribute']);

            if (isset($atributes['variants'])) {

                if ($atributes['variants']) {

                    return true;
                } else {

                    return false;
                }

            } else {

                return false;
            }

        } else {

            return false;
        }


    }

    private function getTyAttributes($selected_attributes)
    {


        $attributes = array();


        foreach ($selected_attributes as $selected_attribute) {


            if ($selected_attribute['value']) {

                if ($selected_attribute['name'] == 47) {
                    $attributes[] = array(
                        'attributeId' => $selected_attribute['name'],
                        'customAttributeValue' => $selected_attribute['value']
                    );

                } else {

                    $val = explode('-', htmlspecialchars_decode($selected_attribute['value']));


                    $attributes[] = array(
                        'attributeId' => $val[0],
                        'attributeValueId' => $val[1]
                    );
                }

            }

        }

        return $attributes;

    }


    public function resize($filename, $width, $height, $catalog_url)
    {
        if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != DIR_IMAGE) {
            return;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $image_old = $filename;
        $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

        if (!is_file(DIR_IMAGE . $image_new) || (filectime(DIR_IMAGE . $image_old) > filectime(DIR_IMAGE . $image_new))) {
            list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);

            if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) {
                return DIR_IMAGE . $image_old;
            }

            $path = '';

            $directories = explode('/', dirname($image_new));

            foreach ($directories as $directory) {
                $path = $path . '/' . $directory;

                if (!is_dir(DIR_IMAGE . $path)) {
                    @mkdir(DIR_IMAGE . $path, 0777);
                }
            }

            if ($width_orig != $width || $height_orig != $height) {
                $image = new Image(DIR_IMAGE . $image_old);
                $image->resize($width, $height);
                $image->save(DIR_IMAGE . $image_new);
            } else {
                copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
            }
        }

        $image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +

        return $catalog_url . 'image/' . $image_new;

    }


    public function getImagesByMarketPlace($product_id, $main_image, $code, $catalog_url = '', $v_mode = false)
    {

        $product_setting = $this->getSettingData($code, 'product', $product_id);

        $main_image = isset($product_setting[$code . '_main_image']) ? $product_setting[$code . '_main_image'] : $main_image;


        $images = array();
        if ($v_mode) {
            $product_images = array();
            $main_image = $v_mode;
        } else {
            $product_images = $this->getProductImages($product_id);

        }


        if (strlen($this->config->get('easy_setting_img_domain')) > 5) {
            $catalog_url = $this->config->get('easy_setting_img_domain');
        }


        if ($code == 'n11') {


            if ($this->config->get('easy_setting_img_domain') == 'null') {
                $images['image'][] = array(
                    'url' => $main_image,
                    'order' => 1);


            } else {

                if ($this->config->get('easy_setting_resize_image')) {

                    $images['image'][] = array(
                        'url' => $this->resize($main_image, 900, 900, $catalog_url),
                        'order' => 1);
                } else {

                    $images['image'][] = array(
                        'url' => $catalog_url . 'image/' . $main_image,
                        'order' => 1);
                }

            }
            $sort = 2;
            foreach ($product_images as $row => $product_image) {


                if (is_file(DIR_IMAGE . $product_image['image'])) {

                    if ($this->config->get('easy_setting_img_domain') == 'null') {
                        $images['image'][] = array(
                            'url' => $product_image['image'],
                            'order' => $row + 2);


                    } else {
                        if ($this->config->get('easy_setting_resize_image')) {


                            $images['image'][] = array(
                                'url' => $this->resize($product_image['image'], 900, 900, $catalog_url),
                                'order' => $row + 2);

                        } else {

                            $images['image'][] = array(
                                'url' => $catalog_url . 'image/' . $product_image['image'],
                                'order' => $row + 2);
                        }

                    }
                    $sort++;
                }
            }
        } else if ($code == 'gg') {


            //$main_image=$this->resize($main_image, 900, 900, $catalog_url);


            $images['photo'] [] = array(
                'url' => $this->resize($main_image, 900, 900, $catalog_url),
                'base64' => base64_encode(file_get_contents(DIR_IMAGE . $main_image)),
                'photoId' => 0);

            $sort = 1;
            foreach ($product_images as $product_image) {
                if ($sort < 8) {
                    if (is_file(DIR_IMAGE . $product_image['image'])) {

                        // $ext_image=$this->resize($product_image['image'], 900, 900, $catalog_url);

                        $images['photo'][] = array(
                            'url' => $catalog_url . 'image/' . $product_image['image'],
                            'base64' => base64_encode(file_get_contents($this->resize($product_image['image'], 900, 900, $catalog_url))),
                            'photoId' => $sort
                        );
                        $sort++;
                    }
                }
            }


        } else if ($code == 'cs' || $code == 'ty') {
            if ($this->config->get('easy_setting_resize_image')) {

                $images[] = $this->resize($main_image, 900, 900, $catalog_url);

            } else {
                $images[] = $catalog_url . 'image/' . $main_image;


            }

            $i = 1;
            foreach ($product_images as $row => $product_image) {
                //  if ($i < 5) {


                if (is_file(DIR_IMAGE . $product_image['image'])) {

                    if ($this->config->get('easy_setting_resize_image')) {

                        $images[] = $this->resize($product_image['image'], 900, 900, $catalog_url);

                    } else {
                        $images[] = $catalog_url . 'image/' . $product_image['image'];
                    }


                    //  }
                    $i++;
                }
                if ($code == 'cs' && $i > 4) break;
                if ($code == 'ty' && $i > 7) break;

            }


        } else if ($code == 'hb' || $code == 'eptt') {

            $images[] = $catalog_url . 'image/' . $main_image;

            foreach ($product_images as $product_image) {
                if (is_file(DIR_IMAGE . $product_image['image'])) {
                    $images[] = $catalog_url . 'image/' . $product_image['image'];

                }
            }

        }


        return $images;

    }


    public function getInformationDescriptions($information_id)
    {
        $information_description_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_description WHERE information_id = '" . (int)$information_id . "'");

        if ($query->num_rows) {


            $result = $query->row;
            return $result['description'];
        } else {

            return '';
        }

    }

    public function addProduct($data)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW()");

        $product_id = $this->db->getLastId();

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
        }


        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        if (isset($data['product_store'])) {
            foreach ($data['product_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
            }
        }

        if (isset($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    // Removes duplicates
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" . $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        if (isset($data['product_option'])) {
            foreach ($data['product_option'] as $product_option) {
                if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                    if (isset($product_option['product_option_value'])) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

                        $product_option_id = $this->db->getLastId();

                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
                        }
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
                }
            }
        }

        if (isset($data['product_discount'])) {
            foreach ($data['product_discount'] as $product_discount) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
            }
        }

        if (isset($data['product_special'])) {
            foreach ($data['product_special'] as $product_special) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
            }
        }

        if (isset($data['product_image'])) {


            //$data['product_image']= array_shift($data['product_image']);

            if (isset($data['product_image'][0])) unset($data['product_image'][0]);


            foreach ($data['product_image'] as $product_image) {

                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");

            }
        }


        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
            }
        }


        if ($data['keyword']) {

            if (VERSION >= 3) {

                $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");

            } else {

                $this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");

            }
        }


        // $this->cache->delete('product');

        return $product_id;
    }

    public function getMarketPlaceManufacturer($manufacturer_id, $code)
    {


        if ($manufacturer_id) {

            $query = $this->db->query("select * from " . DB_PREFIX . "es_manufacturer where manufacturer_id='" . $manufacturer_id . "'");
            $result = $query->row;


            if ($query->num_rows) {

                return $result[$code] ? unserialize($result[$code]) : array();

            } else {

                return array();
            }

        } else {

            return array();
        }

    }

    public function getMatchedCategory($category_id, $code)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "es_category where category_id='" . $category_id . "'");
        if ($query->num_rows) {
            $row = $query->row;
            return unserialize($row[$code]);
        } else {
            return array();
        }
    }

    public function getOcCategory($product_id, $code)
    {

        $path = $this->getProductCategoryPath($product_id);

        if (!$path) return 2;

        $categories = array_reverse($path);


        $result = array();
        foreach ($categories as $category) {
            if ($category) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "es_category where category_id='" . $category . "'");


                if ($query->num_rows) {
                    $row = $query->row;
                    if ($row[$code]) {
                        return $category;
                    }

                }
            }
        }


        return $result;

    }

    public function getMarketPlaceCategory($product_id, $code)
    {

        $path = $this->getProductCategoryPath($product_id);

        if (!$path) return 2;

        $categories = array_reverse($path);


        $result = array();
        foreach ($categories as $category) {
            if ($category) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "es_category where category_id='" . $category . "'");


                if ($query->num_rows) {
                    $row = $query->row;
                    if ($row[$code]) {
                        return unserialize($row[$code]);
                    }

                }
            }
        }


        return $result;

    }


    public function getMatchedCategoryId($product_id, $code)
    {

        $path = $this->getProductCategoryPath($product_id);

        if (!$path) return 2;

        $categories = array_reverse($path);


        $result = array();
        foreach ($categories as $category) {
            if ($category) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "es_category where category_id='" . $category . "'");

                if ($query->num_rows) {
                    $row = $query->row;
                    if ($row[$code]) {
                        return $category;
                    }

                }
            }
        }


        return $result;

    }

    public function getProductCategoryPath($product_id)
    {
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

        $rows = $query->rows;


        $path = array();

        foreach ($rows as $row) {

            if ($row['category_id']) {

                $path[] = $row['category_id'];
            }
        }
        if ($path) {

            return $path;

        }
        return false;
    }

    public function getDefaults($category, $manufacturer, $product, $code)
    {

        $default = array();

        $default['commission'] = $this->checkParameter($category, $manufacturer, $product, $code . '_commission', $code . '_setting_commission', 'easy_setting_commission');
        $default['shipping_template'] = $this->checkParameter($category, $manufacturer, $product, $code . '_shipping_template', $code . '_setting_shipping_template');
        $default['city'] = $this->checkParameter($category, $manufacturer, $product, $code . '_city', $code . '_setting_city');
        $default['shipping_time'] = $this->checkParameter($category, $manufacturer, $product, $code . '_shipping_time', $code . '_setting_shipping_time');
        $default['subtitle'] = $this->checkParameter($category, $manufacturer, $product, $code . '_product_subtitle', $code . '_setting_subtitle');
        $default['image'] = $this->checkParameter($category, $manufacturer, $product, $code . '_main_image', $code . '_setting_image');
        $default['title'] = $this->checkParameter($category, $manufacturer, $product, $code . '_title', $code . '_setting_title');
        $default['shipping_price'] = $this->checkParameter($category, $manufacturer, $product, $code . '_shipping_price', $code . '_setting_shipping_price', 'easy_setting_shipping_price');
        $default['hour'] = $this->checkParameter($category, $manufacturer, $product, $code . '_hour', $code . '_setting_hour');
        $default['minute'] = $this->checkParameter($category, $manufacturer, $product, $code . '_minute', $code . '_setting_minute');
        $default['show_time'] = $this->checkParameter($category, $manufacturer, $product, $code . '_show_time', $code . '_setting_show_time');
        $default['shipping_company'] = $this->checkParameter($category, $manufacturer, $product, $code . '_shipping_company', $code . '_setting_shipping_company');
        $default['additional_content'] = $this->checkParameter($category, $manufacturer, $product, $code . '_additional_content', $code . '_setting_additional_content', 'easy_setting_additional_content');
        $default['product_special'] = $this->checkParameter($category, $manufacturer, $product, $code . '_setting_product_special', $code . '_setting_product_special');
        $default['currency'] = $this->checkParameter($category, $manufacturer, $product, $code . '_currency', $code . '_setting_currency', 'easy_setting_currency');
        $default['domestic'] = $this->checkParameter($category, $manufacturer, $product, $code . '_domestic', $code . '_setting_domestic');
        $default['delivery_type'] = $this->checkParameter($category, $manufacturer, $product, $code . '_delivery_type', $code . '_setting_delivery_type');
        $default['delivery_message_type'] = $this->checkParameter($category, $manufacturer, $product, $code . '_delivery_message_type', $code . '_setting_delivery_message_type');
        $default['maximum_order'] = $this->checkParameter($category, $manufacturer, $product, $code . '_maximum_order', $code . '_setting_maximum_order');
        $default['need_product_weight'] = $this->checkParameter($category, $manufacturer, $product, $code . '_need_product_weight', $code . '_setting_need_product_weight');
        $default['pay_in_the_basket'] = $this->checkParameter($category, $manufacturer, $product, $code . '_pay_in_the_basket', $code . '_setting_pay_in_the_basket');
        $default['price_multiplier'] = $this->checkParameter($category, $manufacturer, $product, $code . '_price_multiplier', $code . '_setting_price_multiplier', 'easy_setting_price_multiplier');

        return $default;

    }

    public function getKdvRange($tax_class_id)
    {
        $query = $this->db->query("SELECT rate FROM `" . DB_PREFIX . "tax_rule` tru left join " . DB_PREFIX . "tax_rate tra ON(tru.tax_rate_id=tra.tax_rate_id) where tru.tax_class_id='" . $tax_class_id . "' ");

        if ($query->num_rows) {

            return (int)$query->row['rate'];


        } else {

            return 0;


        }

    }


    public function calculatePrice($price, $default, $tax_id, $code, $product = array())
    {


        $default['commission'] = str_replace(array('%', 'yüzde', 'yuzde'), '', $default['commission']);

        // $default['commission']="22.46 TL";


        if ($this->config->get('config_currency') != 'TRY') {

            $price = $this->currency->convert($price, $this->config->get('config_currency'), 'TRY');
        }


        if ($default['currency']) {
            if ($default['currency'] != 'TRY') {
                $price = $this->currency->convert($price, $default['currency'], 'TRY');
            }
        }


        /*
                if($default['need_product_weight'] && $product){

                    $price=$price*$product['weight'];

                }
        */

        //KDV Ayarlar sayfası kontrol edilcek
        if ($this->config->get($code . '_setting_kdv_setting')) {

            $price = $this->tax->calculate($price, $tax_id);

        }

        $tempPrice = $price;


        if ($this->config->get('easy_setting_add_after_shipping_price')) {

            if ($default['shipping_price']) {

                if ($this->config->get($code . '_setting_shipping_template') != 'B') {
                    $price += str_replace(',', '.', $default['shipping_price']);
                }

            }

            if ($default['commission']) {

                $price = $price + ($price * $default['commission'] / 100);

            }

            if ($default['price_multiplier']) {

                $price = $this->price_multiplier($tempPrice, $default['price_multiplier']);
            }


        } else {
            if ($default['commission']) {

                $price = $price + ($price * $default['commission'] / 100);

            }

            if ($default['price_multiplier']) {

                $price = $this->price_multiplier($tempPrice, $default['price_multiplier']);
            }

            if ($default['shipping_price']) {

                if ($this->config->get($code . '_setting_shipping_template') != 'B') {
                    $price += str_replace(',', '.', $default['shipping_price']);
                }

            }

        }


        $price = number_format($price, 2);
        $price = str_replace(',', '', $price);

        return $price;

    }


    public function price_multiplier($price, $multipliers)
    {


        $returned_price = $price;


        $wholesaleTable = explode("\n", $multipliers);

        natsort($wholesaleTable);


        foreach ($wholesaleTable as $row) {
            $wholesale = explode(':', $row);

            if (count($wholesale) == 2) {
                if (isset($wholesale[1])) {

                    if ($price < $wholesale[0]) {

                        return $price + ($price * (int)$wholesale[1] / 100);

                    }
                }
            } else {

                return $price;
            }
        }


        return $returned_price;
    }


    public function checkSoap()
    {

        $status = true;
        if (!extension_loaded('soap')) {

            $status = false;

        }

        return $status;

    }

    public function getGtin($product_info)
    {

        if ($product_info['sku']) {

            return $product_info['sku'];
        }


    }


    public function addMarketProduct($stock_code, $marketplace_data, $code)
    {

        $downloaded = 0;
        $updated = 0;
        $matched = 0;

        $sql = "select product_id from " . DB_PREFIX . "product  where model='" . $this->db->escape($marketplace_data['model']) . "' or model='" . $this->db->escape($marketplace_data['stock_code']) . "' or model='" . $this->db->escape($marketplace_data['barcode']) . "'";

        if ($this->config->get($code . '_setting_barkod_place')) {
            $sql .= " or " . $this->config->get($code . '_setting_barkod_place') . "='" . $this->db->escape($marketplace_data['barcode']) . "'";
        }

        if ($this->config->get($code . '_setting_model_prefix')) {

            $model_without_prefix_array = explode($this->config->get($code . '_setting_model_prefix'), $marketplace_data['model'], 2);
            $stock_without_prefix_array = explode($this->config->get($code . '_setting_model_prefix'), $marketplace_data['stock_code'], 2);
            $barcode_without_prefix_array = explode($this->config->get($code . '_setting_model_prefix'), $marketplace_data['barcode'], 2);

            if (count($model_without_prefix_array) == 2) {
                $sql .= " or model='" . $this->db->escape($model_without_prefix_array[1]) . "' ";
            }

            if (count($stock_without_prefix_array) == 2) {
                $sql .= " or model='" . $this->db->escape($stock_without_prefix_array[1]) . "' ";
            }

            if (count($barcode_without_prefix_array) == 2) {
                $sql .= " or model='" . $this->db->escape($barcode_without_prefix_array[1]) . "' ";
            }


        }


        try {
            $query = $this->db->query($sql);

        } catch (Exception $exception) {

            echo $exception->getMessage();
        }

        if ($query->num_rows) {

            $marketplace_data['oc_product_id'] = $query->row['product_id'];
            $matched = 1;

            $this->addAndUpdateMarketProduct($code, $marketplace_data);

        } else if ($this->findInProductVariants($marketplace_data, $code)) {

            $marketplace_data['oc_product_id'] = $this->findInProductVariants($marketplace_data, $code);
            $matched = 1;
            $this->addAndUpdateMarketProduct($code, $marketplace_data);
        } else {

            $marketplace_data['oc_product_id'] = 0;

        }
        if (!$marketplace_data['oc_product_id']) {

            $sql = "select product_id from " . DB_PREFIX . "es_product where $code like '%" . $this->db->escape($marketplace_data['barcode']) . "%' OR $code like '%" . $this->db->escape($marketplace_data['model']) . "%' or $code like '%" . $this->db->escape($marketplace_data['stock_code']) . "%' ";

            $query = $this->db->query($sql);

            if ($query->num_rows) {

                $marketplace_data['oc_product_id'] = $query->row['product_id'];
                $matched = 1;

                $this->addAndUpdateMarketProduct($code, $marketplace_data);

            } else if ($this->findInProductVariants($marketplace_data, $code)) {

                $marketplace_data['oc_product_id'] = $this->findInProductVariants($marketplace_data, $code);
                if($marketplace_data['sale_status']==0){

                   $query= $this->db->query("select * from ".DB_PREFIX."es_market_product where oc_product_id='".$marketplace_data['oc_product_id']."' and sale_status=1");

                   if($query->num_rows){
                       $marketplace_data['sale_status']=1;
                   }
                }

                $matched = 1;
                $this->addAndUpdateMarketProduct($code, $marketplace_data);
            } else {

                $marketplace_data['oc_product_id'] = 0;

            }
        }


        $query = $this->db->query("select * from " . DB_PREFIX . "es_market_product where stock_code='" . $this->db->escape($stock_code) . "' and code='" . $code . "'");

        if ($query->num_rows) {

            try {
                $this->db->query("update " . DB_PREFIX . "es_market_product SET code='" . $code . "', oc_product_id='" . $marketplace_data['oc_product_id'] . "', name='" . $this->db->escape($marketplace_data['name']) . "', model='" . $this->db->escape($marketplace_data['model']) . "', barcode='" . $this->db->escape($marketplace_data['barcode']) . "', stock_code='" . $this->db->escape($marketplace_data['stock_code']) . "', sale_price='" . $marketplace_data['sale_price'] . "', list_price='" . $marketplace_data['list_price'] . "', quantity='" . $marketplace_data['quantity'] . "', sale_status='" . $marketplace_data['sale_status'] . "', approval_status='" . $marketplace_data['approval_status'] . "', marketplace_product_id='" . $marketplace_data['market_id'] . "' ,custom_data='" . $this->db->escape(serialize($marketplace_data['custom_data'])) . "',  date_modified=NOW() where market_product_id='" . $query->row['market_product_id'] . "' ");
                $updated = 1;
            } catch (Exception $exception) {

                echo $exception->getMessage();
            }


        } else {


            try {

                $this->db->query("INSERT INTO " . DB_PREFIX . "es_market_product SET  code='" . $code . "', oc_product_id='" . $marketplace_data['oc_product_id'] . "', name='" . $this->db->escape($marketplace_data['name']) . "', model='" . $this->db->escape($marketplace_data['model']) . "', barcode='" . $this->db->escape($marketplace_data['barcode']) . "', stock_code='" . $this->db->escape($marketplace_data['stock_code']) . "', sale_price='" . $marketplace_data['sale_price'] . "', list_price='" . $marketplace_data['list_price'] . "', quantity='" . $marketplace_data['quantity'] . "', sale_status='" . $marketplace_data['sale_status'] . "', approval_status='" . $marketplace_data['approval_status'] . "', marketplace_product_id='" . $marketplace_data['market_id'] . "' ,custom_data='" . $this->db->escape(serialize($marketplace_data['custom_data'])) . "', date_added=NOW() ");
                $downloaded = 1;

            } catch (Exception $exception) {

                echo $exception->getMessage();
            }


        }


        return array('downloaded' => $downloaded, 'updated' => $updated, 'matched' => $matched);
        // $this->deleteError($product_id, $code);

    }


    public function findInProductVariants($product, $code)
    {

        $sql = "select product_id from " . DB_PREFIX . "es_product_variant where model='" . $this->db->escape($product['stock_code']) . "' or barcode='" . $this->db->escape($product['barcode']) . "' ";

        if ($this->config->get($code . '_setting_model_prefix')) {

            $model_without_prefix_array = explode($this->config->get($code . '_setting_model_prefix'), $product['model'], 2);
            $stock_without_prefix_array = explode($this->config->get($code . '_setting_model_prefix'), $product['stock_code'], 2);
            $barcode_without_prefix_array = explode($this->config->get($code . '_setting_model_prefix'), $product['barcode'], 2);

            if (count($model_without_prefix_array) == 2) {
                $sql .= " or model='" . $this->db->escape($model_without_prefix_array[1]) . "' ";
            }

            if (count($stock_without_prefix_array) == 2) {
                $sql .= " or model='" . $this->db->escape($stock_without_prefix_array[1]) . "' ";
            }

            if (count($barcode_without_prefix_array) == 2) {
                $sql .= " or barcode='" . $this->db->escape($barcode_without_prefix_array[1]) . "' ";
            }

        }


        $query = $this->db->query($sql);


        if ($query->num_rows) {


            return $query->row['product_id'];
        } else {

            return 0;
        }


    }


    private function addAndUpdateMarketProduct($code, $product)
    {

        if (isset($product['link'])) {
            $url = $this->getMarketPlaceUrl($code, $product['market_id'], $product['link']);


        } else {

            $url = $this->getMarketPlaceUrl($code, $product['market_id'], "yok");

        }


        $data = array('commission' => 0, 'sale_status' => $product['sale_status'], 'approval_status' => $product['approval_status'], 'barcode' => $product['barcode'], 'product_id' => $product['market_id'], 'price' => number_format($product['sale_price'], 2), 'url' => $url);


        if (isset($product['product_status'])) {
            $data['product_status'] = $product['product_status'];
        }

        if (isset($product['message'])) {
            $data['message'] = $product['message'];
        }

        if (isset($product['request_id'])) {
            $data['request_id'] = $product['request_id'];
        }


        if ($code == 'n11') {

            $data['stock_id'] = $product['stock_id'];
        }


        //$get_product_to_marketplace_data_query = $this->db->query("select * from " . DB_PREFIX . "es_product_to_marketplace where product_id='" . $product['oc_product_id'] . "' and $code !=''");

        /*    if ($get_product_to_marketplace_data_query->num_rows) {

                if ($get_product_to_marketplace_data_query->row[$code]) {

                    $marketplace_data = unserialize($get_product_to_marketplace_data_query->row[$code]);

                    if (!$marketplace_data['sale_status'] && $data['sale_status']) {

                        $this->addMarketplaceProduct($product['oc_product_id'], $data, $code);

                    }

                }


            } else { */

        $this->addMarketplaceProduct($product['oc_product_id'], $data, $code);

        // }

    }

    public function addMarketplaceProduct($product_id, $marketplace_data, $code)
    {


        $query = $this->db->query("select * from " . DB_PREFIX . "es_product_to_marketplace where product_id='" . $product_id . "'");

        if ($query->num_rows) {

            $this->db->query("update " . DB_PREFIX . "es_product_to_marketplace SET  $code='" . $this->db->escape(serialize($marketplace_data)) . "', date_modified=NOW() where product_id='" . $product_id . "' ");

        } else {

            $this->db->query("INSERT INTO " . DB_PREFIX . "es_product_to_marketplace SET product_id='" . $product_id . "', $code='" . $this->db->escape(serialize($marketplace_data)) . "', date_added=NOW() ");

        }

        $this->deleteError($product_id, $code);

    }

    public function getMarketPlaceProductForMarket($product_id, $code)
    {

        $query = $this->db->query("select * from " . DB_PREFIX . "es_product_to_marketplace where product_id='" . $product_id . "'");


        if ($query->num_rows) {
            return $query->row[$code] ? unserialize($query->row[$code]) : array();

        } else {
            return array();
        }
    }

    public function addError($product_id, $code, $type, $error)
    {

        $this->db->query("INSERT INTO " . DB_PREFIX . "es_product_error SET product_id='" . $product_id . "', code='" . $code . "', type='" . $type . "', error='" . $this->db->escape($error) . "', date_added=NOW(), date_modified=NOW() ");

    }

    public function getError($product_id, $code)
    {

        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "es_product_error WHERE  product_id='" . $product_id . "' and code='" . $code . "'");
        return $query->row;

    }


    public function updateError($product_id, $code, $type, $error)
    {

        $this->db->query("UPDATE " . DB_PREFIX . "es_product_error SET code='" . $code . "', type='" . $type . "', error='" . $this->db->escape($error) . "', date_modified=NOW() WHERE product_id='" . $product_id . "' and code='" . $code . "' ");

    }


    public function deleteError($product_id, $code)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "es_product_error where product_id='" . $product_id . "' and code='" . $code . "'");
    }

    public function deleteMarketplaceProduct($product_id, $code)
    {

        $this->db->query("update " . DB_PREFIX . "es_product_to_marketplace SET $code='', date_modified=NOW() where product_id='" . $product_id . "' ");


    }

    private function checkParameter($category, $manufacturer, $product, $index, $config, $easy = '')
    {


        $param = isset($product[$index]) ? $product[$index] : false;

        if ($param) return $param;

        $param = isset($manufacturer[$index]) ? $manufacturer[$index] : false;
        if ($param) return $param;

        $param = isset($category[$index]) ? $category[$index] : false;
        if ($param) return $param;

        if ($config) return $this->config->get($config);

        return $this->config->get($easy);


    }

    public function getSettingData($code, $controller, $primary_key)
    {

        $data = array();
        $query = $this->db->query("select * from " . DB_PREFIX . "es_" . $controller . " where " . $controller . "_id='" . $primary_key . "'");
        if ($query->num_rows) {
            $row = $query->row;


            $variable = $row['' . $code . ''];

            if ($variable) {
                $data = unserialize($variable);
            }

        }


        return $data;

    }

    public function getProductCategories($product_id)
    {

        $this->load->model('catalog/category');

        $categories = $this->model_catalog_product->getProductCategories($product_id);


        $product_categories = array();

        foreach ($categories as $category_id) {
            $category_info = $this->model_catalog_category->getCategory($category_id);

            if ($category_info) {
                $product_categories[] = array(
                    'category_id' => $category_info['category_id'],
                    'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                );
            }
        }


        return $product_categories;
    }


    public function updateProductStock($products)
    {
        $updated = array();


        foreach ($products as $product) {

            if ($product['product_id']) {

                if ($product['option']) {
                    $update_stok_data = array('product_id' => $product['product_id'], 'quantity' => $product['quantity']);

                    $product_info = $this->getProduct($product['product_id']);

                    $main_stock = $this->updateStock($update_stok_data);
                    if ($main_stock) {

                        $updated[] = $product['product_id'];
                    }
                    foreach ($product['option'] as $option) {


                        $result = $this->updateProductOptionStock2($option['product_option_value_id'], $product['quantity']);
                        $logmesage = 'By After get Order  - Product and options stock ' . $result['before'] . ' to ' . $result['now'] . ' updated on your OC catalog. Product model:' . $product_info['model'] . '- option_value_id:' . $option['product_option_value_id'];
                        $this->log('', $logmesage, false);

                    }


                } else {

                    $update_stok_data = array('product_id' => $product['product_id'], 'quantity' => $product['quantity']);
                    $product_info = $this->getProduct($product['product_id']);

                    $result = $this->updateStock($update_stok_data);

                    if ($result) {

                        $updated[] = $product['product_id'];
                    }

                    $logmesage = 'By After get order - Product Stock ' . $result['before'] . ' to ' . $result['now'] . ' updated on your OC catalog. product model:' . $product_info['model'];

                    $this->log('', $logmesage, false);

                }

            } else {


                $logmesage = 'By After get order - Satılan ürünün, Ürün kodu yada barkodu sitenizde bulunamadığı için ürün Stoğu Web sitenizde Güncellenemedi!. Ürün Kodu:' . $product['model'] . ' ürün ve varyant barkodlarını kontrol ediniz.';

                $this->log('', $logmesage, false);


            }


        }

        return $updated;
    }

    public function updateProductOptionStock($oc_option_value_id, $quantity, $product_id)
    {

        $status = false;
        $query = $this->db->query("select * from " . DB_PREFIX . "product_option_value pov Left join " . DB_PREFIX . "option_value_description opd ON(pov.option_value_id=opd.option_value_id)  where pov.option_value_id='" . $oc_option_value_id . "' and pov.product_id='" . $product_id . "'");

        if ($query->num_rows) {

            $new_quantity = (int)$query->row['quantity'] - (int)$quantity;


            // print_r($query->rows);
            // return;
            $this->db->query("update " . DB_PREFIX . "product_option_value SET quantity='" . $new_quantity . "' where product_option_value_id='" . $query->row['product_option_value_id'] . "' ");

            $status = true;

        }

        return $status;

    }

    public function updateProductOptionStock2($product_option_value_id, $quantity)
    {
        $query = $this->db->query("select * from " . DB_PREFIX . "product_option_value  where product_option_value_id='" . $product_option_value_id . "' ");


        $new_quantity = $query->row['quantity'] - $quantity;

        try {
            $this->db->query("update " . DB_PREFIX . "product_option_value SET quantity='" . $new_quantity . "' where product_option_value_id='" . $product_option_value_id . "' ");
            return array('before' => $query->row['quantity'], 'now' => $new_quantity, 'quantity' => $quantity);

        } catch (Exception $exception) {

            return false;
        }

    }


    public function getOptionValueForUpdateStock($size)
    {

        $query = $this->db->query("select * from " . DB_PREFIX . "es_option_value where market_option_value_name='" . $size . "'");

        return $query->row;
    }

    public function isVarianterProduct($product_id)
    {


        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option`  WHERE product_id = '" . (int)$product_id . "' ");


        return $query->num_rows;

    }


    public function getProductOptionTitles($product_id)
    {

        $product_options = array();
        $product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        foreach ($product_option_query->rows as $product_option) {

            $product_options[] = $product_option['name'];


        }

        return $product_options;

    }

    public function getOptionValues($option_id)
    {
        $option_value_data = array();

        $option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE ov.option_id = '" . (int)$option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order, ovd.name");

        foreach ($option_value_query->rows as $option_value) {
            $option_value_data[] = array(
                'option_value_id' => $option_value['option_value_id'],
                'name' => $option_value['name'],
                'image' => $option_value['image'],
                'sort_order' => $option_value['sort_order']
            );
        }

        return $option_value_data;
    }

    public function getProductOptions($product_id)
    {
        $product_option_data = array();

        $product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' order by o.sort_order");


        foreach ($product_option_query->rows as $product_option) {
            $product_option_value_data = array();

            $product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON(pov.option_value_id = ov.option_value_id) WHERE pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' ORDER BY ov.sort_order ASC");

            foreach ($product_option_value_query->rows as $product_option_value) {
                $product_option_value_data[] = array(
                    'product_option_value_id' => $product_option_value['product_option_value_id'],
                    'option_value_id' => $product_option_value['option_value_id'],
                    'quantity' => $product_option_value['quantity'],
                    'subtract' => $product_option_value['subtract'],
                    'price' => $product_option_value['price'],
                    'price_prefix' => $product_option_value['price_prefix'],
                    'points' => $product_option_value['points'],
                    'points_prefix' => $product_option_value['points_prefix'],
                    'weight' => $product_option_value['weight'],
                    'weight_prefix' => $product_option_value['weight_prefix']
                );
            }

            $product_option_data[] = array(
                'product_option_id' => $product_option['product_option_id'],
                'product_option_value' => $product_option_value_data,
                'option_id' => $product_option['option_id'],
                'name' => $product_option['name'],
                'type' => $product_option['type'],
                'value' => $product_option['value'],
                'required' => $product_option['required']
            );
        }


        return $product_option_data;
    }


    public function getProductOptionQuantity($product_id, $option_value_id)
    {

        $query = $this->db->query("select quantity,price from " . DB_PREFIX . "product_option_value where product_id='" . $product_id . "' and option_value_id='" . $option_value_id . "'  ");

        if ($query->num_rows) {
            return array('quantity' => $query->row['quantity'], 'price' => $query->row['price']);
        } else {
            return array('quantity' => 0, 'price' => 0);
        }
    }

    public function getProductOptionValue($product_id, $product_option_value_id)
    {


        try {
            $query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
            return $query->row;

        } catch (Exception $exception) {

            echo $exception->getMessage();
        }

    }

    public function getProductOptionInfoByProductIdAndOptionValueId($product_id, $option_value_id)
    {

        $query = $this->db->query("select ovd.name as value,od.name as option_name,pov.option_value_id,pov.product_option_value_id,pov.option_id,pov.points_prefix,pov.option_id from " . DB_PREFIX . "product_option_value pov LEFT JOIN   " . DB_PREFIX . "option_value_description ovd on(pov.option_value_id=ovd.option_value_id) LEFT JOIN   " . DB_PREFIX . "option_description od on(pov.option_id=od.option_id) where pov.product_id='" . $product_id . "' and pov.option_value_id='" . $option_value_id . "'");

        return $query->row;

    }


    public function getMarketPlaceUrl($code, $market_id, $cs_link = false)
    {


        if ($code == 'gg') {

            return 'http://urun.gittigidiyor.com/' . $market_id;
        } else if ($code == 'n11') {

            return 'http://urun.n11.com/xxx-P' . $market_id;
        } else if ($code == 'ty') {

            return 'https://www.trendyol.com/p/p-p-p-' . $market_id;
        } else if ($code == 'hb') {

            return 'https://www.hepsiburada.com/product-p-' . $market_id;

        } else if ($code == 'eptt') {

            return 'https://www.epttavm.com/item/' . $market_id . '_p';

        } else if ($code == 'cs') {

            return $cs_link;

        } else {

            return false;
        }


    }

    public function getOcOptions()
    {
        $sql = "SELECT od.name,o.option_id FROM `" . DB_PREFIX . "option` o LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE od.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        $query = $this->db->query($sql);

        $options = array();

        foreach ($query->rows as $option) {

            $options[] = array(

                'option_id' => $option['option_id'],
                'name' => $option['name']

            );

        }

        return $options;
    }

    public function getOcOPtionValues($option_id, $matched_option_id)
    {
        $option_value_data = array();
        try {
            $sql = "SELECT *,(select market_option_value_id from " . DB_PREFIX . "es_option_value where oc_option_value_id = ov.option_value_id  and matched_option_id='" . $matched_option_id . "') as is_matched, (select market_option_value_name from " . DB_PREFIX . "es_option_value where oc_option_value_id = ov.option_value_id  and matched_option_id='" . $matched_option_id . "') as matched_value  FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id)  WHERE ov.option_id = '" . (int)$option_id . "'  AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order, ovd.name";

            $option_value_query = $this->db->query($sql);

        } catch (Exception $exception) {

            echo $exception->getMessage();
        }

        foreach ($option_value_query->rows as $option_value) {
            $option_value_data[] = array(
                'option_value_id' => $option_value['option_value_id'],
                'name' => $option_value['name'],
                'image' => $option_value['image'],
                'sort_order' => $option_value['sort_order'],
                'is_matched' => $option_value['is_matched'],
                'matched_value' => $option_value['matched_value']
            );
        }

        return $option_value_data;

    }


    public function is_product_exists($model, $barcode = '')
    {
        $query = $this->db->query("select product_id from " . DB_PREFIX . "product where model='" . $this->db->escape($model) . "' ");
        if ($query->num_rows) {
            return true;
        } else {


            return false;
        }
    }

    public function is_product_exists_in_product_to_marketplace($code, $product)
    {

        if (!$product['barcode']) return false;
        try {
            $query = $this->db->query("select product_id from " . DB_PREFIX . "es_product_to_marketplace where  $code LIKE '%" . $product['barcode'] . "%' ");

            if ($query->num_rows) {
                return true;
            } else {


                return 0;
            }


        } catch (Exception $exception) {

            echo $exception->getMessage();

        }
    }

    public function getProductByModelFromProductSetting($code, $model)
    {


        $sql = "select * from " . DB_PREFIX . "es_product where $code like '%" . $this->db->escape($model) . "%' ";

        $result = array();

        try {
            $query = $this->db->query($sql);

            foreach ($query->rows as $row) {
                if (isset($row[$code . '_product_code'])) {

                    if ($row[$code . '_product_code'] == $model) {

                        $result = $this->entegrasyon->getProduct($row['product_id']);

                    }
                }
            }

            return $result;

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }


    }


    public function getProductByModelFast($model, $barcode = '')
    {

        if ($model) {
            $sql = "select product_id from " . DB_PREFIX . "product where model='" . $this->db->escape($model) . "'";
        } else {

            return array();

        }
        if ($barcode) {
            $sql .= " or model='" . $this->db->escape($barcode) . "'";

            $query = $this->db->query($sql);
            return $query->row;
        }

    }


    public function getProductByModel($model, $barcode = '', $name = '', $prefix = '', $barkod_place = '', $stockCode = '', $code = 'ty')
    {
        $sqltemp = "SELECT p.product_id,p.model,p.quantity,pd.name,p.price,p.tax_class_id,(SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON(pd.product_id=p.product_id)";


        if ($model) {
            $sql = $sqltemp;
            $sql .= "WHERE LOWER(p.model)='" . strtolower($this->db->escape($model)) . "' or p.model='" . $this->db->escape($model) . "' ";

            if ($stockCode) {

                $sql .= " or p.model='" . $this->db->escape($stockCode) . "'";
            }
            if ($barcode) {
                $sql .= " or p.model='" . $this->db->escape($barcode) . "'";
            }

            if ($this->config->get($code . '_setting_main_product_id')) {

                $sql .= " or p." . $this->config->get($code . '_setting_main_product_id') . "='" . $model . "'";
            }


            try {
                $query = $this->db->query($sql);

            } catch (Exception $exception) {

                // print_r($exception);
            }

            if ($query->num_rows) {

                return $query->row;
            }


        }


        if ($stockCode) {
            $sql = $sqltemp;
            $sql .= " WHERE LOWER(p.model)='" . strtolower($this->db->escape($stockCode)) . "' or p.model='" . $this->db->escape($stockCode) . "' ";
            if ($this->config->get($code . '_setting_barkod_place')) {

                $sql .= " or p." . $this->config->get($code . '_setting_barkod_place') . "='" . $this->db->escape($stockCode) . "'";

            }


            $query = $this->db->query($sql);

            if ($query->num_rows) {

                return $query->row;
            }
        }


        if ($model && $prefix) {
            $explodefromprefix = explode($prefix, $model);
            if (count($explodefromprefix) == 2) {
                $sql = $sqltemp;
                $sql .= " WHERE LOWER(p.model)='" . strtolower($this->db->escape($explodefromprefix[1])) . "' or p.model='" . $this->db->escape($explodefromprefix[1]) . "' ";
                $query = $this->db->query($sql);

                if ($query->num_rows) {

                    return $query->row;
                }

            }


        }


        if ($stockCode && $prefix) {
            $explodefromprefix = explode($prefix, $stockCode);
            if (count($explodefromprefix) == 2) {
                $sql = $sqltemp;
                $sql .= " WHERE LOWER(p.model)='" . strtolower($this->db->escape($explodefromprefix[1])) . "' or p.model='" . $this->db->escape($explodefromprefix[1]) . "' ";
                $query = $this->db->query($sql);

                if ($query->num_rows) {

                    return $query->row;
                }

            }


        }


        if ($barcode && $this->config->get($code . '_setting_barkod_place')) {
            $sql = $sqltemp;

            if ($this->config->get($code . '_setting_barkod_place')) {

                $sql .= " where p." . $this->config->get($code . '_setting_barkod_place') . "='" . $this->db->escape($barcode) . "'";

            }

            $query = $this->db->query($sql);
            if ($query->num_rows) {

                return $query->row;
            }

        }


        if ($prefix) {
            $prefix_split = explode(strtolower($prefix), strtolower($model));
            if (count($prefix_split) > 1) {
                $model_split = $prefix_split[1];
                $sql = $sqltemp;
                $sql .= " where p.model='" . $this->db->escape($model_split) . "'";

                $query = $this->db->query($sql);
                return $query->row;
            }

        }


        /*  if ($name) {
              $sql .= " or LOWER(pd.name)='" . strtolower($this->db->escape($name)) . "'";
              $sql .= " or pd.name='" . $this->db->escape($name) . "'";

          }*/


//echo $sql;
        //          return;


        return false;
    }


    public function getProductByOrderModel($barcode = '', $model = '', $name = '', $code)
    {


        //echo $model;

        //return;


        $status = false;
        $product_type = 'p';
        $variant_id = 0;

        $product_info = $this->getProductByModel($model, $barcode, $name);


        if (!$product_info && $model) {
            if ($this->config->get($code . '_setting_model_prefix')) {

                if ($code == "hb") {
                    $model2 = explode(mb_strtoupper($this->config->get($code . '_setting_model_prefix')), $model);

                } else {
                    $model2 = explode($this->config->get($code . '_setting_model_prefix'), $model);

                }

                if (count($model2) > 1) {
                    $product_info = $this->getProductByModel($model2[1], $model2[1]);

                }


            } else {

                $product_info = $this->getProductByModel($model, $model);

            }

        }


        if (!$product_info && $model && $this->config->get($code . '_setting_model_prefix')) {

            $model = explode($this->config->get($code . '_setting_model_prefix'), $model, 2);

            if (isset($model[1])) {

                $query = $this->db->query("select product_id,variant_id from " . DB_PREFIX . "es_product_variant where LOWER(model)='" . strtolower($model[1]) . "' ");
                if ($query->num_rows) {

                    $product_info = $this->getProduct($query->row['product_id']);
                    $product_type = 'v';
                    $variant_id = $query->row['variant_id'];

                }
            }
        }


        if (!$product_info && $barcode) {


            $query = $this->db->query("select * from " . DB_PREFIX . "es_market_product where barcode='" . $this->db->escape($barcode) . "'  or model='" . $this->db->escape($barcode) . "'");


            if ($query->num_rows) {

                $product_info = $this->getProduct($query->row['oc_product_id']);

            }
        }


        if ($product_info) {
            $status = true;
        } else {
            $status = false;
        }


        return array('status' => $status, 'product_type' => $product_type, 'variant_id' => $variant_id, 'product' => $product_info);

    }


    public function updateStock($product_info)
    {
        $status = false;
        $product = $this->getProduct($product_info['product_id']);
        if ($product) {
            $quantity = (int)$product['quantity'] - (int)$product_info['quantity'];
            $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity='" . $quantity . "', date_modified=NOW() WHERE product_id='" . $product_info['product_id'] . "' ");
            return array('before' => $product['quantity'], 'now' => $quantity, 'quantity' => $product_info['quantity']);
        } else {

            return false;
        }


    }

    public function getVariantTitles($category_id)
    {

        $titles = array();
        $all = array();


        $query = $this->db->query("select * from " . DB_PREFIX . "es_option where category_id = '" . $category_id . "'");


        foreach ($query->rows as $row) {

            $titles[] = $row['market_option_name'];
            $all[] = array(
                'market_option_name' => $row['market_option_name'],
                'oc_option_id' => $row['oc_option_id']);
        }


        return array('titles' => $titles, 'all' => $all);

    }


    public function getVariants($code, $product_id, $category_id)
    {

        ///$this->load->model('entegrasyon/category/ty');
        //$this->load->model('entegrasyon/tool');
//        $this->load->model('entegrasyon/category');


        // $trendyol_attributes=$this->model_entegrasyon_category->getAttributes($category_id,'ty');

        $status = true;
        $message = '';

        if ($code == 'n11') {

            $variants = $this->getOptionsforOthers($product_id, $code);
            return array('status' => true, 'error' => '', 'variants' => $variants);

        }

        $market_variants = $this->getVariantTitles($category_id);

        $product_option_titles = $this->getProductOptionTitles($product_id);

        if ($code == 'gg') {

            if (count($product_option_titles) < count($market_variants['titles'])) {

                $status = false;
                $message = 'Ürünün bulunduğu Kategori ' . implode(',', $market_variants['titles']) . " Seçeneklerini birlikte istemektedir. Ürününüzde sadece " . implode(',', $product_option_titles) . " seçeneği var. Kategorinin istediği seçenek adedi ile ürününüzün seçenek adedi aynı olmalıdır.";

            }
        }


        if (!$market_variants['titles']) {


            return array('status' => true, 'message' => '', 'variants' => array());


            //  $status = false;
            //  $message = 'Ürününüz seçenekli ancak seçenek eşleştirmesi yapmamışsınız. Ürününüzün bulunduğu kategori ayarlarına girerek seçenekleri eşleştiriniz.';

        }

        if (!$status) {
            return array('status' => false, 'message' => $message, 'variants' => array());
        }


        $variants = $this->getOptions($product_id, $market_variants, $code);


        if (!$variants['status']) {
            return array('status' => false, 'message' => $variants['message'], 'variants' => array());

        }


        $attributes = array();

        // $attributes['selected_attributes']=array();

        $error = array();
        /*
                $temp_quantity=array();


                foreach ($variants as $key=> $attribute) {

                    //SEÇENEK DEĞERLERİMİZİN ADETLERİNİ ALIYORUZ.
                    foreach ($attribute as $item) {

                        if(!in_array($item['option_value_id'],$temp_quantity)){
                            $temp_quantity[$item['option_value_id']]=$item['quantity'];
                        }
                    }

                }
        */


        foreach ($variants['options'] as $key => $attribute) {


            $quantity = 9999999999;
            $price = 0;
            $prefix = '';


            $matched = 0;
            $order_number_info = array();

            // $temp_opt=array();


            foreach ($attribute as $item) {

                //      print_r($item);return;

                if ($code == 'ty' && strtolower($item['name']) == 'renk') {
                    $variant_option_info = array('market_option_name' => 'Renk', 'order_number' => 1, 'market_option_id' => 47);

                } else {

                    $variant_option_info = $this->getVariantOptionInfo($code, $item['option_id'], $category_id);

                }


                $order_number_info[] = $variant_option_info['market_option_name'] . '-' . $variant_option_info['order_number'];

                //  $current_quantity=$temp_quantity[$item['option_value_id']];

                /*
                               if($temp_quantity[$item['option_value_id']] < $quantity){
                                    $quantity=$current_quantity;
                                }
                */


                //  $temp_opt[]=$item['option_value_id'];

                // $quantity=$quantity<0?0:$quantity;

                if ($item['quantity'] < $quantity) {
                    $quantity = $item['quantity'];
                }

                if ($item['price'] > $price) {
                    $price = $item['price'];
                }


                //  echo $item['option_value_id'];


                if ($code == 'ty' && strtolower($item['name']) == 'renk') {
                    $option_value_info = array('market_option_value_order' => 1, 'market_option_value_name' => $item['value'], 'market_option_id' => 47, 'market_option_value_id' => 47);

                } else {
                    $option_value_info = $this->getVariantOptionValueId($item['option_value_id'], $variant_option_info['option_id']);

                }


                if ($option_value_info) {

                    if ($option_value_info['market_option_value_id']) {

                        $attributes['options'][$key]['attributes'][] = array(

                            'order_number' => $option_value_info['market_option_value_order'],
                            'name' => $option_value_info['market_option_value_name'],
                            'attributeId' => $variant_option_info['market_option_id'],
                            'attributeValueId' => $option_value_info['market_option_value_id']
                        );
                        $prefix .= $this->createSEOKeyword($item['value']);

                        $attributes['options'][$key]['prefix'] = $prefix;
                        $attributes['options'][$key]['order_number'] = implode('|', $order_number_info);
                        $attributes['options'][$key]['quantity'] = $quantity;
                        $attributes['options'][$key]['price'] = $price;


                        $matched++;

                    } else {

                        if (!in_array($item['name'] . '-' . $item['value'], $error)) {
                            $error[] = $item['name'] . '-' . $item['value'];

                        }

                    }


                } else {

                    if (!in_array($item['name'] . '-' . $item['value'], $error)) {
                        $error[] = $item['name'] . '-' . $item['value'];

                    }
                }


            }


            /*
                        if ($matched != count($market_variants['all'])) {
                            unset($attributes['options'][$key]);
                        }
            */

            /*
                        foreach ($temp_opt as $temp) {

                            $temp_quantity[$temp]-=$quantity;

                        }
            */
        }


        if (isset($attributes['options'])) {
            $attributes['options'] = array_values($attributes['options']);

        }


        if ($error) {

            $status = false;
            $message = 'Eşleştirilmemiş varyant değerleri tespit edildi, Tüm varyantların gönderilmesi için şu değerleri eşleştirin, Değerler:' . implode('-', $error);
        }


        return array('status' => $status, 'message' => $message, 'variants' => $attributes);


    }


    public function getVariantByModel($code, $model, $barcode, $mainModel = "")
    {


        $query = $this->db->query("select * from " . DB_PREFIX . "es_product_variant where model='" . $this->db->escape($model) . "' or barcode='" . $this->db->escape($barcode) . "' or barcode='" . $this->db->escape(str_replace('-', '', $barcode)) . "' ");

        if ($query->num_rows) {

            return $query->row;

        } else {


            $sql = "select * from " . DB_PREFIX . "es_product_variant where model='" . $this->db->escape($model) . "' or LOWER(model)='" . strtolower($this->db->escape($model)) . "' or barcode='" . $this->db->escape($barcode) . "' or LOWER(barcode) ='" . strtolower($this->db->escape($barcode)) . "' ";

            if ($mainModel) {
                $alternative = explode($mainModel, $barcode);
                if (count($alternative) > 1) {
                    if (!$this->str_search($alternative[1], '-')) {

                        $new_barcode = $mainModel . '-' . $alternative[1];
                        $sql .= " or barcode='" . $this->db->escape($new_barcode) . "' or LOWER(barcode)='" . strtolower($this->db->escape($new_barcode)) . "' ";

                    }
                }
                $alternative2 = explode($mainModel, $model);

                if (count($alternative2) > 1) {
                    if (!$this->str_search($alternative2[1], '-')) {

                        $new_model = $mainModel . '-' . $alternative2[1];
                        $sql .= " or model='" . $this->db->escape($new_model) . "' or LOWER(model)='" . strtolower($this->db->escape($new_model)) . "'";

                    }
                }

            }

            if ($this->config->get($code . '_setting_model_prefix')) {
                $model_arr = explode($this->config->get($code . '_setting_model_prefix'), $model);
                $barcode_arr = explode($this->config->get($code . '_setting_model_prefix'), $barcode);

                if (count($model_arr) == 2) {
                    $sql .= " or model='" . $this->db->escape($model_arr[1]) . "' or LOWER(model)='" . strtolower($this->db->escape($model_arr[1])) . "'";
                }

                if (count($barcode_arr) == 2) {
                    $sql .= " or barcode='" . $this->db->escape($barcode_arr[1]) . "'or  LOWER(barcode)='" . strtolower($this->db->escape($barcode_arr[1])) . "' ";
                }

            }
            $query = $this->db->query($sql);

            return $query->row;
        }

    }


    public function getMarketVariantNotNeedMatchOption($product_variants, $code, $product_id, $model = '', $catalog_url = '', $price_data = array())
    {


        $not_match = array();

        $renk_control = '';


        $variant_option_info = array();
        $variants = array();


        foreach ($product_variants as $product_variant) {
            $not_matching_exist = false;
            $renk_control = $product_variant['variant_info'];

            $attribute_infos = explode('|', $product_variant['variant_info']);

            if (!$this->config->get('ty_setting_color') && $code == 'ty') {


                foreach ($attribute_infos as $key => $attribute_info) {

                    if (($this->str_search($attribute_info, 'renk') || $this->str_search($attribute_info, 'reng'))) {
                        unset($attribute_infos[$key]);
                        //$attribute_infos=array_keys($attribute_infos);
                    }
                }

            }


            $order_number = array();
            $quantity = 9999;
            $price = 0;
            $attributes = array();
            $pref = '';


            foreach ($attribute_infos as $attribute_info) {

                $attribute_option_infos = explode('+-', $attribute_info);
                $oc_option_value_quantity = $this->getProductOptionQuantity($product_id, $attribute_option_infos[3]);

                if ($oc_option_value_quantity['quantity'] < $quantity) {
                    $quantity = $oc_option_value_quantity['quantity'];
                }


                if ($price_data) {

                    $price_data['defaults']['shipping_price'] = false;
                    $price = $this->calculatePrice($price + $oc_option_value_quantity['price'], $price_data['defaults'], $price_data['tax_class_id'], $code);
                } else {
                    $price = $price + $oc_option_value_quantity['price'];
                }

                $pref = $attribute_option_infos[2];

            }


            if (!$attribute_infos) {

                $variant_temp['quantity'] = $oc_option_value_quantity['quantity'];

            } else {

                $variant_temp['quantity'] = $quantity > 999 ? 10 : $quantity;

            }
            if ($this->config->get('easy_setting_critical_stock')) {
                $variant_temp['quantity'] = $variant_temp['quantity'] <= $this->config->get('easy_setting_critical_stock') ? 0 : $variant_temp['quantity'];

            }


            $prefix_arr = explode('-', $product_variant['model']);

            $variant_temp['variant_id'] = $product_variant['variant_id'];
            $variant_temp['attributes'] = array();
            $variant_temp['image'] = $product_variant['image'] ? $catalog_url . 'image/' . $product_variant['image'] : false;
            $variant_temp['price'] = $price;
            $variant_temp['prefix'] = count($prefix_arr) > 1 ? $prefix_arr[1] : $product_variant['model'];


            if (!$this->config->get('ty_setting_color') && $code == 'ty' && ($this->str_search($renk_control, 'renk') || $this->str_search($renk_control, 'reng'))) {

                $variant_temp['model'] = $model . $this->createSEOKeyword($pref);
                $variant_temp['barcode'] = $model . $this->createSEOKeyword($pref);


            } else {

                $variant_temp['model'] = $this->config->get($code . '_setting_model_prefix') . $product_variant['model'];
                $variant_temp['barcode'] = $this->config->get($code . '_setting_model_prefix') . $product_variant['barcode'];
            }

            /*    if ($code == 'hb') {

                    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "es_market_product` where `model`='" . strtoupper($variant_temp['model']) . "' and code='" . $code . "' ");

                    if ($query->num_rows) {

                        $variant_temp['hbsku'] = $query->row['marketplace_product_id'];

                    } else {
                        $variant_temp['hbsku'] = false;
                    }

                }*/

            $variant_temp['order_number'] = implode('|', $order_number);
            if (!$not_matching_exist) {
                $variants[] = $variant_temp;

            }

        }


        $message = '';


        return array('status' => true, 'message' => $message, 'variants' => $variants);

    }


    public function getDefaultGgColorInfo($product_id, $category_id)
    {

        $renkOption_id = 0;
        $renkOptionValue_id = 0;

        $options = $this->getProductOptions($product_id);
        $colorStatus = true;
        foreach ($options as $option) {
            if (!$this->str_search($option['name'], 'renk')) {
                $colorStatus = false;
            }
        }


        $matched_options = $this->isOptionsMatched($category_id, 'gg');

        $marketColorStatus = false;
        if (isset($matched_options[0])) {

            if ($matched_options[0] == 'Renk') {
                $marketColorStatus = true;
            }

        }

        if (!$colorStatus && $marketColorStatus) {

            $query = $this->db->query("select * from " . DB_PREFIX . "es_attribute where category_id='" . $category_id . "'");

            $attributes = unserialize($query->row['attribute']);

            foreach ($attributes['variants'] as $variant) {
                if ($variant['name'] == 'Renk') {
                    $renkOption_id = $variant['id'];

                    foreach ($variant['values'] as $value) {
                        if ($this->str_search($value['name'], 'renkli')) {
                            $renkOptionValue_id = $value['value_id'];
                        }
                    }

                }
            }

        }
        // print_r($options);
        return array('attribute_id' => $renkOption_id, 'attribute_value_id' => $renkOptionValue_id);

    }


    public function getMarketVariant($product_variants, $code, $category_id, $product_id, $model = '', $catalog_url = '', $price_data = array())
    {

        $defultAttributeInfo = array();

        $not_match = array();

        $renk_control = '';

        if ($code == 'n11' || $code == 'eptt') {

            $variants = $this->getOptionsforOthers($product_id, $code, $product_variants);

            return array('status' => true, 'error' => '', 'variants' => $variants);

        }

        $variants = array();

        if ($code == 'gg' && $this->config->get('gg_setting_default_variant_color')) {

            $defultAttributeInfo = $this->getDefaultGgColorInfo($product_id, $category_id);

        }


        foreach ($product_variants as $product_variant) {
            $not_matching_exist = false;
            $renk_control = $product_variant['variant_info'];

            $attribute_infos = explode('|', $product_variant['variant_info']);

            if (!$this->config->get('ty_setting_color') && $code == 'ty') {


                foreach ($attribute_infos as $key => $attribute_info) {

                    if ($this->str_search($attribute_info, 'renk') || $this->str_search($attribute_info, 'reng')) {
                        unset($attribute_infos[$key]);
                        //$attribute_infos=array_keys($attribute_infos);
                    }
                }

            }


            $order_number = array();
            $quantity = 9999;
            $price = 0;
            $attributes = array();
            $pref = '';


            foreach ($attribute_infos as $attribute_info) {

                $attribute_option_infos = explode('+-', $attribute_info);
                $oc_option_value_quantity = $this->getProductOptionQuantity($product_id, $attribute_option_infos[3]);

                if ($oc_option_value_quantity['quantity'] < $quantity) {
                    $quantity = $oc_option_value_quantity['quantity'];
                }


                if ($price_data) {

                    $price_data['defaults']['shipping_price'] = false;
                    $price = $this->calculatePrice($price + $oc_option_value_quantity['price'], $price_data['defaults'], $price_data['tax_class_id'], $code);
                } else {
                    $price = $price + $oc_option_value_quantity['price'];
                }


                if ($code == 'ty' && ($this->str_search($attribute_option_infos[0], 'renk') || $this->str_search($attribute_option_infos[0], 'reng')) && $this->config->get('ty_setting_color')) {

                    $variant_option_info = array('market_option_name' => 'Renk', 'order_number' => 1, 'market_option_id' => 47);
                } else {

                    $variant_option_info = $this->getVariantOptionInfo($code, $attribute_option_infos[1], $category_id);

                }

                $order_number[] = $attribute_option_infos[0] . '-' . $attribute_option_infos[1];


                if ($code == 'ty' && ($this->str_search($attribute_option_infos[0], 'renk') || $this->str_search($attribute_option_infos[0], 'reng'))) {

                    $option_value_info = array('market_option_value_order' => 1, 'market_option_value_name' => $attribute_option_infos[2], 'market_option_id' => 47, 'market_option_value_id' => 47);

                } else {
                    $option_value_info = $this->getVariantOptionValueId($attribute_option_infos[3], $variant_option_info['option_id']);

                }


                if (!$option_value_info['market_option_value_id']) {

                    if (!in_array($attribute_option_infos[2], $not_match)) {
                        $not_match[] = $attribute_option_infos[2];

                    }

                    $not_matching_exist = true;

                }


                $pref = $attribute_option_infos[2];

                $attributes[] = array(
                    'order_number' => 1,
                    'option_name' => $attribute_option_infos[0],
                    'name' => $option_value_info['market_option_value_name'],
                    'attributeId' => $variant_option_info['market_option_id'],
                    'attributeValueId' => $option_value_info['market_option_value_id']

                );

                if ($code == 'gg' && $this->config->get('gg_setting_default_variant_color')) {


                    if ($defultAttributeInfo) {
                        $attributes[] = array(
                            'order_number' => 0,
                            'option_name' => 'Renk',
                            'name' => 'Renk',
                            'attributeId' => $defultAttributeInfo['attribute_id'],
                            'attributeValueId' => $defultAttributeInfo['attribute_value_id']

                        );
                    }
                }

            }


            if (!$attribute_infos) {

                $attribute_option_infos = explode('+-', $product_variant['variant_info']);
                $oc_option_value_quantity = $this->getProductOptionQuantity($product_id, $attribute_option_infos[3]);

                $variant_temp['quantity'] = $oc_option_value_quantity['quantity'];

            } else {

                $variant_temp['quantity'] = $quantity > 999 ? 998 : $quantity;

            }

            if ($this->config->get('easy_setting_critical_stock')) {

                $variant_temp['quantity'] = $variant_temp['quantity'] <= $this->config->get('easy_setting_critical_stock') ? 0 : $variant_temp['quantity'];
            }


            $prefix_arr = explode('-', $product_variant['model']);

            $variant_temp['variant_id'] = $product_variant['variant_id'];
            $variant_temp['attributes'] = $attributes;
            $variant_temp['image'] = $product_variant['image'] ? $catalog_url . 'image/' . $product_variant['image'] : false;
            $variant_temp['price'] = $price;
            $variant_temp['prefix'] = count($prefix_arr) > 1 ? $prefix_arr[1] : $product_variant['model'];


            if (!$this->config->get('ty_setting_color') && $code == 'ty' && ($this->str_search($renk_control, 'renk') || $this->str_search($attribute_option_infos[0], 'reng'))) {

                $variant_temp['model'] = $model . $this->createSEOKeyword($pref);
                $variant_temp['barcode'] = $model . $this->createSEOKeyword($pref);

            } else {

                $variant_temp['model'] = $this->config->get($code . '_setting_model_prefix') . $product_variant['model'];
                $variant_temp['barcode'] = $this->config->get($code . '_setting_model_prefix') . $product_variant['barcode'];
            }

            $variant_temp['order_number'] = implode('|', $order_number);
            if (!$not_matching_exist) {
                $variants[] = $variant_temp;

            }

        }


        $message = '';

        if ($not_match) {

            $message = implode(' ve ', $not_match);

        }

        return array('status' => true, 'message' => $message, 'variants' => $variants);

    }


    public function str_search($samanlik, $igne)
    {

        if (strpos(strtolower($samanlik), strtolower($igne)) === false) {
            return false;
        } else {
            return true;
        }

    }


    public function getOptions($product_id, $marketVariants, $code)
    {

        $status = true;
        $message = '';

        $need_variants = array();
        $need_variants_names = array();


        foreach ($marketVariants['all'] as $marketVariant) {


            $need_variants[] = $marketVariant['oc_option_id'];
            $need_variants_names[] = $marketVariant['market_option_name'];
        }

        $product_options = $this->getProductOptions($product_id);


        $options = array();


        foreach ($product_options as $key => $product_option) {


            if (in_array($product_option['option_id'], $need_variants) || ($code == 'ty' && strtolower($product_option['name']) == 'renk')) {


                foreach ($product_option['product_option_value'] as $val) {

                    $val_info = $this->getProductOptionValue($product_id, $val['product_option_value_id']);

                    $options[$key][] = array('option_id' => $product_option['option_id'], 'name' => $product_option['name'], 'value' => $val_info['name'], 'option_value_id' => $val_info['option_value_id'], 'quantity' => $val['quantity'], 'price' => $val['price']);

                }


            } else {

                $product_option_name = $product_option['name'];


            }


        }


        if (!$options) {
            $status = false;
            $message = 'Ürününüzde olması gereken seçenek/seçenekler: ' . implode(',', $need_variants_names) . ', ancak Ürününüzde sadece şu seçenek/seçenekler bulunuyor:' . $product_option_name . ' Ürün seçenekli göndermek için ürününüze ' . implode(',', $need_variants_names) . ' Ekleyiniz';

        }


        if ($status) {
            $options = $this->makeVariant($options);

        } else {
            $options = array();

        }


        return array('status' => $status, 'message' => $message, 'options' => $options);


    }


    public function getOptions2($product_id)
    {

        $product_info = $this->getProduct($product_id);

        $status = true;
        $message = '';

        $product_options = $this->getProductOptions($product_id);

        $options = array();


        foreach ($product_options as $key => $product_option) {


            foreach ($product_option['product_option_value'] as $val) {

                $val_info = $this->getProductOptionValue($product_id, $val['product_option_value_id']);

                $options[$key][] = array('option_id' => $product_option['option_id'], 'name' => $product_option['name'], 'value' => $val_info['name'], 'option_value_id' => $val_info['option_value_id'], 'quantity' => $val['quantity'], 'price' => $val['price']);

            }
        }


        if ($status) {
            $options = $this->makeVariant($options);

        } else {
            $options = array();

        }

        $attributes = array();

        foreach ($options as $key => $attribute) {


            $quantity = 9999999999;
            $price = 0;
            $prefix = '';
            $name = array();
            $option_info = array();
            $variant_count = 0;


            foreach ($attribute as $item) {


                if ($item['quantity'] < $quantity) {
                    $quantity = $item['quantity'];
                }

                if ($item['price'] > $price) {
                    $price = $item['price'];
                }


                $variant_count++;

                $prefix .= $this->createSEOKeyword($item['value']);
                $name[] = $item['value'];
                $option_info[] = $item['name'] . '+-' . $item['option_id'] . '+-' . $item['value'] . '+-' . $item['option_value_id'];
                $attributes['options'][$key]['name'] = implode('-', $name);
                $attributes['options'][$key]['variant_info'] = implode('|', $option_info);
                // $attributes['options'][$key]['barcode'] = substr($product_info['model'] . $this->createSEOKeyword($prefix), -25);
                $attributes['options'][$key]['barcode'] = $product_info['model'] . $this->createSEOKeyword($prefix);

                // $attributes['options'][$key]['prefix'] = $prefix;
                $attributes['options'][$key]['quantity'] = $quantity;
                $attributes['options'][$key]['price'] = $price;
                $attributes['options'][$key]['image'] = '';
                $attributes['options'][$key]['variant_count'] = $variant_count;

            }


        }


        return array('status' => $status, 'message' => $message, 'options' => $attributes);

    }


    public function getOptionsforOthers($product_id, $code, $variants)
    {


        $product_options = $this->getProductOptions($product_id);


        $options = array();

        foreach ($product_options as $key => $product_option) {


            foreach ($product_option['product_option_value'] as $val) {

                $val_info = $this->getProductOptionValue($product_id, $val['product_option_value_id']);

                $options[$key][] = array('option_id' => $product_option['option_id'], 'name' => $product_option['name'], 'value' => $val_info['name'], 'option_value_id' => $val_info['option_value_id'], 'quantity' => $val['quantity'], 'price' => $val['price']);

            }

        }

        $options = $this->makeVariant($options);


        foreach ($options as $key => $attribute) {


            $quantity = 9999999999;
            $price = 0;
            $prefix = '';


            $matched = 0;
            $order_number_info = array();

            // $temp_opt=array();

            foreach ($attribute as $item) {

                //print_r($item);


                //  $current_quantity=$temp_quantity[$item['option_value_id']];

                /*
                               if($temp_quantity[$item['option_value_id']] < $quantity){
                                    $quantity=$current_quantity;
                                }
                */


                //  $temp_opt[]=$item['option_value_id'];

                // $quantity=$quantity<0?0:$quantity;

                if ($item['quantity'] < $quantity) {
                    $quantity = $item['quantity'];
                }

                //if ($item['price'] > $price) {
                $price = $price + $item['price'];
                //}


                if ($this->str_search($item['name'], 'beden')) {

                    $item['name'] = 'Beden';
                }

                $attributes[$key]['attributes'][] = array(

                    'order_number' => 0,
                    'name' => $item['name'],
                    'value' => $item['value'],

                    'attributeId' => 0,
                    'attributeValueId' => 0
                );
                $prefix .= $this->createSEOKeyword($item['value']);

                $attributes[$key]['prefix'] = $prefix;
                $attributes[$key]['order_number'] = implode('|', $order_number_info);
                $attributes[$key]['quantity'] = $quantity;
                $attributes[$key]['price'] = $price;


                $matched++;


            }


        }
        //  $attributes['saved_variants']=$variants;

        return $attributes;
    }


    public function deleteIfInAttbutes($variants, $attributes)
    {


        foreach ($attributes as $key => $attribute) {

            $option_id = explode('-', $attribute['value'])[0];
            $isExists = $this->isInselectedAttibutes($option_id, $variants);

            if ($isExists) {
                unset($attributes[$key]);


            }
        }


        return $attributes;

    }

    private function isInselectedAttibutes($option_id, $variants)
    {


        foreach ($variants['variants']['options'] as $variant) {

            foreach ($variant['attributes'] as $attribute) {


                if ($option_id == $attribute['attributeId']) {

                    return true;

                }

            }

        }

        return false;

    }

    public function makeVariant($sources)
    {
        $result = array();
        $cache = array();
        foreach ($sources as $node) {
            $cache = $result;
            $result = array();
            foreach ($node as $item) {
                if (empty($cache)) {
                    $result[] = array($item);
                } else {
                    foreach ($cache as $line) {
                        $line[] = $item;
                        $result[] = $line;
                    }
                }
            }
        }
        return $result;
    }

    public function getVariantOptionInfo($code, $option_id, $category_id)
    {


        $query = $this->db->query("select * from " . DB_PREFIX . "es_option where code='" . $code . "' and category_id='" . $category_id . "' and oc_option_id='" . $option_id . "' ");
        if ($query->num_rows) {
            return $query->row;
        } else {
            return false;
        }

    }

    public function getVariantOptionValueId($option_value_id, $matched_option_id)
    {

        $query = $this->db->query("select * from " . DB_PREFIX . "es_option_value where oc_option_value_id='" . $option_value_id . "' and matched_option_id='" . $matched_option_id . "' ");
        if ($query->num_rows) {
            return $query->row;
        } else {

            return false;
        }

    }


    public function getImage($image_url, $name)
    {
        $image_folder = 'catalog/easyentegre/';

        if (!file_exists(DIR_IMAGE . $image_folder)) {

            mkdir(DIR_IMAGE . $image_folder, 0777, true);

        }

        $image_url = str_replace(" ", "%20", $image_url);
        $image_name = explode("/", $image_url);
        $image_name = $image_name[count($image_name) - 1];
        $path_parts = pathinfo($image_name);
        $extension = $path_parts['extension'];

        $image_name = $this->createSEOKeyword($name) . '.' . $extension;


        $image_file = DIR_IMAGE . $image_folder . $image_name;

        //if (!file_exists($image_file)) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $image_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $image_content = curl_exec($ch);

        if ($image_content == false) {
            $image_content = file_get_contents($image_url);
        }
        file_put_contents($image_file, $image_content);

        // }

        return $image_folder . $image_name;
    }


    public function createSEOKeyword($title, $options = array('transliterate'))
    {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $title = mb_convert_encoding((string)$title, 'UTF-8', mb_list_encodings());
        $defaults = array(
            'delimiter' => '-',
            'limit' => null,
            'lowercase' => true,
            'replacements' => array(),
            'transliterate' => true,
        );
        // Merge options
        $options = array_merge($defaults, $options);
        $char_map = array(
            // Latin
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y', 'ž' => 'z',
            // Latin symbols
            '©' => '(c)',
            // Greek
            'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
            'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
            'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
            'Ϋ' => 'Y',
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
            'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
            'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
            'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
            'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
            // Turkish
            'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
            'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',
            // Russian
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
            'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya',
            // Ukrainian
            'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
            // Czech
            'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
            'Ž' => 'Z',
            'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
            'ž' => 'z',
            // Polish
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
            'Ż' => 'Z',
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z',
            // Latvian
            'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
            'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
            'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
            'š' => 's', 'ū' => 'u', 'ž' => 'z',
            //special
            'Ľ' => 'L', 'ľ' => 'l',
        );
        // Make custom replacements
        $title = preg_replace(array_keys($options['replacements']), $options['replacements'], $title);
        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $title = str_replace(array_keys($char_map), $char_map, $title);
        }
        // Replace non-alphanumeric characters with our delimiter
        $title = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $title);
        // Remove duplicate delimiters
        $title = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $title);
        // Truncate slug to max. characters
        $title = mb_substr($title, 0, ($options['limit'] ? $options['limit'] : mb_strlen($title, 'UTF-8')), 'UTF-8');
        // Remove delimiter from ends
        $title = trim($title, $options['delimiter']);
        return $options['lowercase'] ? mb_strtolower($title, 'UTF-8') : $title;
    }

    public function replaceSpace($string)
    {

        $string = str_replace(' ', '', $string);
        $string = preg_replace('/\s+/', '', $string);
        $string = trim($string);

        $string = strtolower($string);

        $res = str_split($string, 1);

        $kontrol = array();

        foreach ($res as $re) {

            if (ctype_print($re)) {

                $kontrol[] = $re;
            }


        }


        $string = implode($kontrol);


        return $string;

    }


    public function getOptionIdByName($name)
    {
        $query = $this->db->query("select * from " . DB_PREFIX . "option_description where `name`='" . $this->db->escape($name) . "' ");
        if ($query->num_rows) {

            return $query->row['option_id'];
        } else {
            return false;
        }
    }


    public function getProductOptionInfoByOptionIdAndOptionValueId($product_id, $option_id, $option_value_id)
    {

        $query = $this->db->query("select * from " . DB_PREFIX . "product_option_value where `product_id`='" . $product_id . "' and option_id='" . $option_id . "' and option_value_id='" . $option_value_id . "' ");
        if ($query->num_rows) {

            return $query->row;
        } else {
            return false;
        }

    }

    public function getOptionValueIdByName($name)
    {
        $query = $this->db->query("select * from " . DB_PREFIX . "option_value_description where `name`='" . $this->db->escape($name) . "' ");
        if ($query->num_rows) {

            return $query->row['option_value_id'];
        } else {
            return false;
        }
    }

    public function getOptionNames($product_id)
    {


        $product_options = $this->getProductOptions($product_id);

        $options = array();

        foreach ($product_options as $key => $product_option) {

            $options[] = $product_option['name'];
        }

        return $options;


    }

    public function imageControl($image)
    {

        return $image;

        $bosluk = strpos($image, ' ');

        if ($bosluk) {
            if (is_file(DIR_IMAGE . $image)) {

                $degisti = $this->imageTemizle($image);


                $produduct_image = $this->db->query("select * from " . DB_PREFIX . "product WHERE image='" . $image . "' ");

                if ($produduct_image->num_rows) {

                    $product_id = $produduct_image->row['product_id'];

                    $this->db->query("UPDATE " . DB_PREFIX . "product SET image='" . $degisti . "' WHERE product_id=" . (int)$product_id . " ");

                    rename(DIR_IMAGE . $image, DIR_IMAGE . $degisti);

                    return $degisti;
                } else {

                    $produduct_image = $this->db->query("select * from " . DB_PREFIX . "product_image WHERE image='" . $image . "' ");


                    if ($produduct_image->num_rows)

                        $image_id = $produduct_image->row['product_image_id'];

                    $this->db->query("UPDATE " . DB_PREFIX . "product_image SET image='" . $degisti . "' WHERE product_id='" . (int)$image_id . "' ");

                    rename(DIR_IMAGE . $image, DIR_IMAGE . $degisti);

                    return $degisti;

                }
            }
        } else {

            return $image;

        }


    }

    public function imageTemizle($image)
    {
//Veritabanına kayıtlı olan imajı seoya uygun hale getirir
        $basename = basename($image);


        $file_path = explode($basename, $image);

        $ext = pathinfo($basename, PATHINFO_EXTENSION);


        $withoutExt = explode($ext, $basename);

        $temizle = $this->createSEOKeyword($withoutExt[0]);
        $degisti = $file_path[0] . $temizle . '.' . $ext;


        return $degisti;
    }

    public function checkRequiredAttributes($category_id, $code, $selected_attributes, $product_id = 0)
    {

        error_reporting(0);

        $reqired = array();


        $query = $this->db->query("select * from " . DB_PREFIX . "es_attribute where category_id='" . $category_id . "' and code='" . $code . "' ");
        $attributes = $query->row;


        $required_attributes = isset($attributes['required']) ? unserialize($attributes['required']) : array();

        $reqired = $required_attributes;

        if ($required_attributes) {
            if ($this->config->get($code . '_setting_variant')) {

                foreach (unserialize($attributes['attribute']) as $attribute) {


                    if ($attribute['varianter']) {

                        if (in_array($attribute['name'], $reqired) || in_array($attribute['id'], $reqired)) {


                            if ($code == 'hb') {

                                $pos = array_search($attribute['id'], $reqired);

                            } else {

                                $pos = array_search($attribute['name'], $reqired);

                            }

                            unset($reqired[$pos]);

                            if ($code == 'ty') {

                                $optionNames = $this->getOptionNames($product_id);


                                $isexists = array_values(preg_grep("/rEnk/i", $optionNames));


                                if ($isexists) {
                                    $renk = $isexists[0];
                                    foreach ($reqired as $key => $req) {
                                        if (strtolower($req) == strtolower($renk)) {

                                            unset($reqired[$key]);
                                        }

                                    }
                                }


                                //if(in_array('Renk'))

                            }
                        }


                    }

                }
            }

            if ($code == 'ty' && $this->findColor($this->getOptionNames($product_id))) {

                foreach ($reqired as $key => $req) {
                    if (strtolower($req) == 'renk') {

                        unset($reqired[$key]);
                    }

                }

            }

            foreach ($required_attributes as $key => $attribute) {


                foreach ($selected_attributes as $selected) {


                    if ($selected['name'] == $attribute) {


                        if ($selected['value']) {

                            unset($reqired[$key]);

                        }
                    } else if (is_numeric($selected['name'])) {


                        if ($selected['value']) {

                            unset($reqired[$key]);

                        }
                    }
                }
            }


        }
        return $reqired;
    }


    public function findColor($options)
    {
        $is_exists = false;
        foreach ($options as $option) {
            if ($this->str_search($option, 'renk')) {
                $is_exists = true;
                return $is_exists;
            }
        }

        return $is_exists;
    }


    public function stringClear($str)
    {

        $bul = array('&');
        $degistir = array('-');

        return str_replace($bul, $degistir, $str);

    }

    public function currentRate($currency, $price)
    {
        $connect_web = simplexml_load_file('http://www.tcmb.gov.tr/kurlar/today.xml');
        $usd = $connect_web->Currency[0]->BanknoteSelling;
        $euro = $connect_web->Currency[3]->BanknoteSelling;
        if ($currency == 'USD') {
            $price = (float)$price * (float)$usd;
        } else if ($currency == 'EUR') {
            $price = $price * $euro;
        }
        return $price;
    }

    public function getProductImages($product_id)
    {

        $images = array();
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

        foreach ($query->rows as $item) {
            if ($item['image']) {
                if (is_file(DIR_IMAGE . $item['image'])) {

                    $images[] = $item;

                }

            }

        }

        return $images;
    }


    public function getPoductVariants($product_id)
    {


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "es_product_variant WHERE product_id = '" . (int)$product_id . "'");

        $variants = array();
        if (!$query->num_rows) {

            $create_variants = $this->getOptions2($product_id);

            if ($create_variants['status']) {

                $this->addProductVariants($create_variants['options']['options'], $product_id);

                $variants = $this->db->query("SELECT * FROM " . DB_PREFIX . "es_product_variant WHERE product_id = '" . (int)$product_id . "'")->rows;


            }

        } else {

            $variants = $query->rows;


        }

        return $variants;


    }

    public function addProductVariants($datas, $product_id)
    {


        foreach ($datas as $data) {


            try {
                $this->db->query("INSERT INTO " . DB_PREFIX . "es_product_variant SET product_id='" . $product_id . "', barcode='" . $this->db->escape($data['barcode']) . "',model='" . $this->db->escape($data['barcode']) . "', `name`='" . $this->db->escape($data['name']) . "',`variant_info`='" . $this->db->escape($data['variant_info']) . "',`variant_count`='" . $data['variant_count'] . "',image='" . $this->db->escape($data['image']) . "',quantity='" . $data['quantity'] . "',price='" . $data['price'] . "' ");

            } catch (Exception $exception) {

                echo $exception->getMessage();
            }
        }
    }


    public function updateProductVariant($column, $value, $variant_id)
    {

        $sql = "UPDATE  " . DB_PREFIX . "es_product_variant SET " . $column . "='" . $value . "' where variant_id='" . $variant_id . "' ";

        try {
            $this->db->query($sql);

        } catch (Exception $exception) {

            echo $exception->getMessage();
        }

        return $this->db->countAffected();

    }


}