<?php

class ModelEntegrasyonProductEptt extends Model
{


    public function sendProduct($product_data,$attributes=array(),$debug=false)
    {

        $product_data['attributes'] = $this->getProductAttributes($product_data['product_id']);

        $images = array();
        $image_no = 0;
        if (!empty($product_data["main_image"])) {
            $urunresim = new stdClass();
            $urunresim->UrunResim = array();
            $urunresim->UrunResim[0]['Url'] = HTTPS_CATALOG . 'image/' . $product_data["main_image"];
            $Resim1Url = HTTPS_CATALOG . 'image/' . $product_data["main_image"];


            $urunresim->UrunResim[0]['Sira'] = 1;
            $additional_image = $this->db->query("SELECT * FROM  " . DB_PREFIX . "product_image WHERE product_id = '" . $product_data['product_id'] . "'");
            foreach ($additional_image->rows as $key => $ekresim) {
                if ($image_no < 10) {
                    $l = $key + 1;
                    $ekresimler[$l] = HTTPS_CATALOG . 'image/' . $ekresim["image"];
                    $urunresim->UrunResim[$l] = array();
                    $urunresim->UrunResim[$l]['Url'] = HTTPS_CATALOG . 'image/' . $ekresim["image"];
                    $urunresim->UrunResim[$l]['Sira'] = $key + 2;
                    $image_no++;
                }
            }
        }
        $product_data['images'] = $urunresim;
        $product_data['kdv'] = $this->kdv($product_data['tax_class_id']);

        $post_data['request_data'] = $product_data;
        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('eptt');
        $send = $this->entegrasyon->clientConnect($post_data, 'add_product', 'eptt', $debug);


        if ($send['status']) {
            $price = $product_data['sale_price'];
            $data = array('commission' => $product_data['defaults']['commission'],'sale_status'=>1,'approval_status'=>1, 'product_id' => $product_data['product_id'], 'price' => $price);
            $this->entegrasyon->addMarketplaceProduct($product_data['product_id'], $data, 'eptt');
            return array('status' => true, 'message' => $send['message'], 'price' => $price . ' TL');

        } else {

            return array('status' => false, 'message' => $send['message']);

        }

    }

    public function getExtraData($product_data)
    {

        return $product_data;

    }

    public function getProductOptions($product_id)
    {
        $product_option_data = array();

        $product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        foreach ($product_option_query->rows as $product_option) {
            $product_option_value_data = array();

            $product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_option_id = '" . (int)$product_option['product_option_id'] . "'");

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


    public function deleteProduct($product_id)
    {
        $product_info = $this->entegrasyon->getProduct($product_id);
        return $this->reset_stock($product_info);

    }


    public function kdv($tax_class_id)
    {
        if ($tax_class_id) {
            $taxquery = $this->db->query("SELECT * FROM " . DB_PREFIX . "tax_rule WHERE tax_class_id = '" . $tax_class_id . "' ORDER BY tax_rule_id DESC LIMIT 1");
            $tax = $this->db->query("SELECT * FROM " . DB_PREFIX . "tax_rate WHERE tax_rate_id = '" . $taxquery->row['tax_rate_id'] . "'");
            return $tax->row['rate'];
        } else {

            return 18;
        }
    }


    public function getProductAttributes($product_id)
    {

        $product_attribute_group_data = array();
        $product_attribute_group_query = $this->db->query("SELECT ag.attribute_group_id, agd.name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_group ag ON (a.attribute_group_id = ag.attribute_group_id) LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE pa.product_id = '" . (int)$product_id . "' AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY ag.attribute_group_id ORDER BY ag.sort_order, agd.name");
        foreach ($product_attribute_group_query->rows as $product_attribute_group) {
            $product_attribute_data = array();
            $product_attribute_query = $this->db->query("SELECT a.attribute_id, ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id = '" . (int)$product_id . "' AND a.attribute_group_id = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY a.sort_order, ad.name");
            foreach ($product_attribute_query->rows as $product_attribute) {

                $product_attribute_data[] = array(

                    'attribute_id' => $product_attribute['attribute_id'],

                    'name' => $product_attribute['name'],

                    'text' => $product_attribute['text']

                );

            }


            $product_attribute_group_data[] = array(

                'attribute_group_id' => $product_attribute_group['attribute_group_id'],

                'name' => $product_attribute_group['name'],

                'attribute' => $product_attribute_data

            );

        }


        return $product_attribute_group_data;

    }


    public function getImages($product_info)
    {


        $images = array();
        $image_no = 0;
        if (!empty($product_info["image"])) {
            $urunresim = new stdClass();
            $urunresim->UrunResim = array();
            $urunresim->UrunResim[0]['Url'] = HTTPS_CATALOG . 'image/' . $product_info["image"];
            $Resim1Url = HTTPS_CATALOG . 'image/' . $product_info["image"];


            $urunresim->UrunResim[0]['Sira'] = 1;
            $additional_image = $this->db->query("SELECT * FROM  " . DB_PREFIX . "product_image WHERE product_id = '" . $product_info['product_id'] . "'");
            foreach ($additional_image->rows as $key => $ekresim) {
                if ($image_no < 10) {
                    $l = $key + 1;
                    $ekresimler[$l] = HTTPS_CATALOG . 'image/' . $ekresim["image"];
                    $urunresim->UrunResim[$l] = array();
                    $urunresim->UrunResim[$l]['Url'] = HTTPS_CATALOG . 'image/' . $ekresim["image"];
                    $urunresim->UrunResim[$l]['Sira'] = $key + 2;
                    $image_no++;
                }
            }
        }


    }

    public function reset_stock($product_info)
    {

        $this->load->model('entegrasyon/general');

        $product_array = array(
            'Barkod' => $product_info['model'],
            'KDVOran' => 18,
            'KDVli' => 1,
            'KDVsiz' => 1, // KDVsiz Fiyat
            'Fiyat' => 1, // KDVsiz Fiyat
            'Miktar' => 0,
            'Aktif' => false, // kapatmak için false
            'Mevcut' => true,
            'Iskonto' => 0,
            'ShopId' => $this->config->get('eptt_magaza_id'), // mağaza shop id
        );


        $post_data['request_data'] = $product_array;
        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('eptt');
        $result = $this->entegrasyon->clientConnect($post_data, 'update_basic', 'eptt');


        if ($result['status']) {

            $this->entegrasyon->deleteMarketplaceProduct($product_info['product_id'], 'eptt');
            return array('status' => true, 'message' => 'Ürün Satışa Kapatıldı');

        } else {

            return array('status' => false, 'message' => $result['message']);

        }


    }


    public function getProducts($data = array(),$debug=false)
    {

        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        $this->load->model('entegrasyon/general');

        $message = '';
        $status = false;
        $total = 0;
        $products = array();
        $post_data['request_data'] = '';
        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('eptt');

        //$debug=true;
        $result = $this->entegrasyon->clientConnect($post_data, 'get_products', 'eptt', $debug);
        

        $pages = 0;
        $products = array();

        if ($result['status']) {

            if ($result['result']['total'] > 0) {

                $total = $result['result']['total'];
                $products = $result['result']['products'];
                $status = true;

            } else {

                $message = 'Ürün Bulunamadı!';

            }

        }

        return array('status' => $status, 'total' => $total, 'pages' => $pages, 'message' => $message, 'products' => $products);
    }


}