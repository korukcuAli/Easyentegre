<?php

class ModelEntegrasyonProduct extends Model
{



    public function deleteProduct($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_recurring WHERE product_id = " . (int)$product_id);
        $this->db->query("DELETE FROM " . DB_PREFIX . "review WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "es_product_to_marketplace WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "es_product WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "es_product_error WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "es_product_variant WHERE product_id = '" . (int)$product_id . "'");
        $this->cache->delete('product');
    }



    public function productSettingDelete($code){


        if ($code == "all" ){

            $sql="DELETE FROM " . DB_PREFIX . "es_product ";


        }else {
            $sql="UPDATE " . DB_PREFIX . "es_product SET ".$code."= ''";

        }

        $this->db->query($sql);

    }


    public function editProduct($product_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_store'])) {
            foreach ($data['product_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

        if (!empty($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    // Removes duplicates
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_option'])) {
            foreach ($data['product_option'] as $product_option) {
                if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                    if (isset($product_option['product_option_value'])) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

                        $product_option_id = $this->db->getLastId();

                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
                        }
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
                }
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_discount'])) {
            foreach ($data['product_discount'] as $product_discount) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_special'])) {
            foreach ($data['product_special'] as $product_special) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_image'])) {
            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_download'])) {
            foreach ($data['product_download'] as $download_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_filter'])) {
            foreach ($data['product_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");

        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_reward'])) {
            foreach ($data['product_reward'] as $customer_group_id => $value) {
                if ((int)$value['points'] > 0) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$value['points'] . "'");
                }
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_layout'])) {
            foreach ($data['product_layout'] as $store_id => $layout_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
            }
        }


        if(VERSION >= 3) {

            if (isset($data['product_seo_url'])) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");

                foreach ($data['product_seo_url']as $store_id => $language) {
                    foreach ($language as $language_id => $keyword) {
                        if (!empty($keyword)) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                        }
                    }
                }
            }

        }else {
            if (isset($data['keyword'])) {
                if($data['keyword']){
                $this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product_id . "'");

                $this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
            }}
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = " . (int)$product_id);

        if (isset($data['product_recurring'])) {
            foreach ($data['product_recurring'] as $product_recurring) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$product_recurring['customer_group_id'] . ", `recurring_id` = " . (int)$product_recurring['recurring_id']);
            }
        }

        $this->cache->delete('product');
    }






    public function getProduct($product_id)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    public function getMarketPlaceProducts($code)
    {

        $sql = "select * from " . DB_PREFIX . "es_product_to_marketplace where $code != '' ";

        $query = $this->db->query($sql);
        $products = array();


        foreach ($query->rows as $row){
            if (isset(unserialize($row[$code])['sale_status'])) {
                if (unserialize($row[$code])['sale_status']) {
                    $products[] = $row;
                }
            }

        }



        return $products;
    }

    public function getCloseMarketPlaceProducts($code)
    {


        $status = 'sale_status";i:0';
        $alternatif_status = 'sale_status";b:0';

        $sql = " select * from " . DB_PREFIX . "es_product_to_marketplace WHERE " . $code . " LIKE '%" . $status . "%'  or  " . $code . " LIKE '%" . $alternatif_status . "%'    ";




        //  $sql = "select * from " . DB_PREFIX . "es_product_to_marketplace where $code != '' ";

        $query = $this->db->query($sql);


        //foreach ($query->rows as $row){
        //   if (!unserialize($row[$code])['sale_status']){
        //  $products[] = $row;
        //  }

        // }


        return $query->rows;
    }


    public function getMarketPlaceProductsForMatch($product_id,$code,$product_model)
    {

        $query = $this->db->query("select * from " . DB_PREFIX . "es_product_to_marketplace where product_id='" . $product_id . "'");
        if ($query->num_rows) {
            $row = $query->row;

            $variable = $row['' . $code . ''];

            if ($variable) {
                $settings = unserialize($variable);

                $temp=isset($settings['product_match'])?$settings['product_match']:0;
                $settings['product_match'] = $product_model;

                if (!$product_model) {

                    unset($settings['product_match']);
                }


                if ($settings) {


                    $this->db->query("update " . DB_PREFIX . "es_product_to_marketplace SET $code='" . $this->db->escape(serialize($settings)) . "', date_modified=NOW() where product_id='" . $product_id . "' ");

                } else {

                    $this->db->query("update " . DB_PREFIX . "es_product_to_marketplace SET $code='', date_modified=NOW() where product_id_id='" . $product_id. "' ");
                }


            }else {

                $insert_data = array('product_match' => $product_model);
                $this->db->query("update " . DB_PREFIX . "es_product_to_marketplace SET $code='" . $this->db->escape(serialize($insert_data)) . "',date_modified=NOW() where product_id ='" . $product_id . "' ");

            }
        }else {
            //Yeni Match_Id Oluştur;
            $insert_data = array('product_match' => $product_model);
            $this->db->query("insert into " . DB_PREFIX . "es_product_to_marketplace SET $code='" . $this->db->escape(serialize($insert_data)) . "', product_id='" . $product_id . "', date_modified=NOW() ");
        }


    }





    public function updateProductOptions($data, $product_id)
    {

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_option'])) {
            foreach ($data['product_option'] as $product_option) {
                if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                    if (isset($product_option['product_option_value'])) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

                        $product_option_id = $this->db->getLastId();

                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "'");
                        }
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
                }
            }
        }


    }

    public function getPassiveProducts($code)
    {

        $sql = "select p.product_id from " . DB_PREFIX . "product p left join " . DB_PREFIX . "es_product_to_marketplace p2m ON(p.product_id=p2m.product_id) where p2m." . $code . "='' ";


        $query = $this->db->query($sql);


        return $query->rows;

    }

    public function getTotalOptions($product_id)
    {
        $query = $this->db->query("select count(*) as total from " . DB_PREFIX . "product_option where product_id='" . $product_id . "'");
        return $query->row['total'];
    }


    public function getProducts2($data = array())
    {
        $sql = "SELECT p.product_id,p.model,p.manufacturer_id,p.price,m.name as manufacturer, p.image,p2m.n11,p2m.gg,p2m.cs,p2m.ty,p2m.amz,p2m.hb,p2m.eptt,p.status,pd.name,p.quantity,(select count(*) from " . DB_PREFIX . "product_option where product_id=p.product_id) as total_options ";

        if (!empty($data['filter_category'])) {

            if (!empty($data['filter_sub_category'])) {
                $sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
            } else {
                $sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
            }

            if (!empty($data['filter_filter'])) {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
            } else {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
            }
        } else {
            $sql .= " FROM " . DB_PREFIX . "product p";
        }
        $sql .= " LEFT JOIN " . DB_PREFIX . "es_product_to_marketplace p2m ON(p2m.product_id=p.product_id) ";
        $sql .= " LEFT JOIN " . DB_PREFIX . "manufacturer m ON(p.manufacturer_id=m.manufacturer_id) ";

        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

        if (!empty($data['filter_category'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql .= " AND cp.path_id = '" . (int)$data['filter_category'] . "'";
            } else {
                $sql .= " AND p2c.category_id = '" . (int)$data['filter_category'] . "'";
            }

            if (!empty($data['filter_filter'])) {
                $implode = array();

                $filters = explode(',', $data['filter_filter']);

                foreach ($filters as $filter_id) {
                    $implode[] = (int)$filter_id;
                }

                $sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
            }
        }


        if (isset($data['filter_status']) && $data['filter_status'] !== '*') {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_marketplace'])) {
            if ($data['filter_marketplace'] != '*') {
                $sql .= " AND p2m." . $data['filter_marketplace'] . "= ''";
            }
        }


        if (!empty($data['filter_except'])) {

            $sql .= " AND (p2m." . $data['filter_except'] . "='' or  p2m." . $data['filter_except'] . " is null) ";
        }

        if (!empty($data['filter_stock_prefix'])) {


            if ($data['filter_stock_prefix'] != '*') {


                $sql .= " AND p.quantity " . $data['filter_stock_prefix'] . " " . $data['filter_stock'] . " ";

            }
        }
        if (!empty($data['filter_price_prefix'])) {


            if ($data['filter_price_prefix'] != '*') {


                $sql .= " AND p.price " . $data['filter_price_prefix'] . " " . $data['filter_price'] . " ";

            }
        }



        if (!empty($data['filter_in_notname'])){
          //  $sql .= " and pd.name not like ( '%" . $data['filter_in_notname'] . "%') ";

            $sql .= " AND (";



            $wordsi = explode(',', $data['filter_in_notname']);
            $wordsi2 = explode('-', $data['filter_in_notname']);
            if(count($wordsi2) > 1){


                foreach ($wordsi2 as $word) {
                    $implode[] = "pd.name not LIKE '%" . $this->db->escape($word) . "%'";
                }

                if ($implode) {
                    $sql .= " " . implode(' OR ', $implode) . "";
                }
            }else {

                foreach ($wordsi as $word) {
                    $implode[] = "pd.name not LIKE '%" . $this->db->escape($word) . "%'";
                }

                if ($implode) {
                    $sql .= " " . implode(' AND ', $implode) . "";
                }
            }

            $sql .= ")";
        }


        if (!empty($data['filter_name'])) {
            $sql .= " AND (";

            if (!empty($data['filter_name'])) {
                $implode = array();



                $words = explode(',', $data['filter_name']);
                $words2 = explode('-', $data['filter_name']);
                if(count($words2) > 1){


                    foreach ($words2 as $word) {
                        $implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
                    }

                    if ($implode) {
                        $sql .= " " . implode(' OR ', $implode) . "";
                    }
                }else {

                    foreach ($words as $word) {
                        $implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
                    }

                    if ($implode) {
                        $sql .= " " . implode(' AND ', $implode) . "";
                    }
                }



                if (!empty($data['filter_description'])) {
                    $sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
                }
            }

            /*   if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
                 $sql .= " OR ";
             }

             if (!empty($data['filter_tag'])) {
                 $implode = array();

                 $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

                 foreach ($words as $word) {
                     $implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
                 }

                 if ($implode) {
                     $sql .= " " . implode(" AND ", $implode) . "";
                 }
             }

           if (!empty($data['filter_name'])) {
                 $sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                 $sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                 $sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                 $sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                 $sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                 $sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                 $sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
             }*/

            $sql .= ")";
        }

        if (!empty($data['filter_manufacturer_id'])) {

            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }

        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.quantity',
            'p.price',
            'rating',
            'p.sort_order',
            'p.date_added'
        );

        $viewed = true;

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
                $sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
            } else if ($data['sort'] == 'p.price') {
                $sql .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
            } else if ($viewed) {
                $sql .= " ORDER BY p.viewed";

            } else {
                $sql .= " ORDER BY p.sort_order";
            }
        } else {
            $sql .= " ORDER BY p.sort_order";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC, LCASE(pd.name) DESC";
        } else if ($viewed) {
            $sql .= " DESC, LCASE(p.viewed) DESC";
        } else {
            $sql .= " ASC, LCASE(pd.name) ASC";
        }


        //$data['start'] = 0;
        //  $data['limit'] = 10000000;

        //$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];




        $query = $this->db->query($sql);


        // $this->session-> = (int)($count->num_rows) ? (int)$count->row['count'] : 0;


        return $query->rows;
    }

    public function getProductErrors($data)
    {
        $sql = "select p.product_id, pd.name,p.model,pe.code,pe.type,pe.error,pe.date_modified from " . DB_PREFIX . "es_product_error pe LEFT JOIN " . DB_PREFIX . "product p ON(p.product_id=pe.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON(p.product_id=pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";


        if (!empty($data['filter_marketplace'])) {
            if ($data['filter_marketplace'] != '*') {
                $sql .= " AND pe.code='" . $data['filter_marketplace'] . "'";
            }
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }


        $sql .= " ORDER BY pe.date_added DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
        }

        //  $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];

        try {
            $query = $this->db->query($sql);
            return $query->rows;
        } catch (Exception $exception) {

            echo $exception->getMessage();
        }


    }

    public function getTotalProductErrors($data)
    {
        $sql = "select COUNT(*) as total  from " . DB_PREFIX . "es_product_error pe LEFT JOIN " . DB_PREFIX . "product p ON(p.product_id=pe.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON(p.product_id=pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";


        if (!empty($data['filter_marketplace'])) {

            if ($data['filter_marketplace'] != '*') {
                $sql .= " AND pe.code='" . $data['filter_marketplace'] . "'";
            }
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        try {
            $query = $this->db->query($sql);
            return $query->row['total'];

        } catch (Exception $exception) {

            echo $exception->getMessage();
        }


    }


    public function getUnApprovedProducts($data)
    {

        $keyword='approval_status";i:0';
        $sql = "select * from ".DB_PREFIX."es_product_to_marketplace p2m left join ".DB_PREFIX."product p ON(p.product_id=p2m.product_id) left join ".DB_PREFIX."product_description pd ON(p.product_id=pd.product_id)  where p2m.".$data['code']." like '%".$keyword."%' ";

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }



        $sql .= " ORDER BY p2m.date_added DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
        }

        //  $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];

        try {
            $query = $this->db->query($sql);
            return $query->rows;
        } catch (Exception $exception) {

            echo $exception->getMessage();
        }


    }

    public function getTotalUnApprovedProducts($data)
    {

        $keyword='approval_status";i:0';
        $sql = "select count(*) as total from ".DB_PREFIX."es_product_to_marketplace p2m left join ".DB_PREFIX."product p ON(p.product_id=p2m.product_id) where p2m.".$data['code']." like '%".$keyword."%' ";





        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }


        try {
            $query = $this->db->query($sql);
            return $query->row['total'];

        } catch (Exception $exception) {

            echo $exception->getMessage();
        }


    }

    public function getMarketPlaceProductsForDownloadTotal($data)
    {
        $sql = "SELECT DISTINCT * FROM " . DB_PREFIX . "es_market_product where `code` = '" . $data['code'] . "'";
        if (!empty($data['filter_name'])) {

            $sql .= "AND name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        if (!empty($data['filter_oc_product_id'])) {

            $sql .= "AND oc_product_id LIKE '%" . $this->db->escape($data['filter_oc_product_id']) . "%'";
        }


        if (!empty($data['filter_model'])) {
            $sql .= " AND model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (isset($data['filter_marketplace_do']) && $data['filter_marketplace_do'] !== '*') {
            $sql .= " AND sale_status LIKE '" . $this->db->escape($data['filter_marketplace_do']) . "%'";
        }
        if (isset($data['filter_match']) && $data['filter_match'] !== '*') {

            if ($data['filter_match'] == 1){
                $sql .= " AND oc_product_id  NOT LIKE '0'";

            }else{
                $sql .= " AND oc_product_id LIKE '0'";

            }

        }

        if (!empty($data['filter_barcode'])) {
            $sql .= " AND barcode LIKE '" . $this->db->escape($data['filter_barcode']) . "%'";
        }
        if (!empty($data['filter_marketplace_product_id'])) {
            $sql .= " AND marketplace_product_id LIKE '" . $this->db->escape($data['filter_marketplace_product_id']) . "%'";
        }

        if (!empty($data['filter_stock_prefix'])) {


            if ($data['filter_stock_prefix'] != '*') {
                if($data['filter_stock'] !== false) {

                    $sql .= " AND p.quantity " . $data['filter_stock_prefix'] . " " . $data['filter_stock'] . " ";
                }
            }
        }






        $query = $this->db->query($sql);
        return count($query->rows);
    }
    public function getMarketPlaceProductsForDownload($data)
    {


        $sql = "SELECT DISTINCT * FROM " . DB_PREFIX . "es_market_product where `code` = '" . $data['code'] . "'";




        if (!empty($data['filter_name'])) {

            $sql .= "AND name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_oc_product_id'])) {

            $sql .= "AND oc_product_id LIKE '%" . $this->db->escape($data['filter_oc_product_id']) . "%'";
        }


        if (!empty($data['filter_model'])) {
            $sql .= " AND model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (isset($data['filter_marketplace_do']) && $data['filter_marketplace_do'] !== '*') {

            if ($data['code'] == "n11" && $data['filter_marketplace_do']){
                $filter_marketplace_do = 2;
                $filter_marketplace_do2 = 4;
                $sql .= " AND sale_status LIKE '" . $filter_marketplace_do . "%'";
                $sql .= " or sale_status LIKE '" . $filter_marketplace_do2 . "%'";

            }else{
                $sql .= " AND sale_status LIKE '" . $this->db->escape($data['filter_marketplace_do']) . "%'";

            }
        }


        if (isset($data['filter_match']) && $data['filter_match'] !== '*') {

            if ($data['filter_match'] == 1){
                $sql .= " AND oc_product_id  NOT LIKE '0'";

            }else{
                $sql .= " AND oc_product_id LIKE '0'";

            }

        }

        if (!empty($data['filter_barcode'])) {
            $sql .= " AND barcode LIKE '" . $this->db->escape($data['filter_barcode']) . "%'";
        }
        if (!empty($data['filter_marketplace_product_id'])) {
            $sql .= " AND marketplace_product_id LIKE '" . $this->db->escape($data['filter_marketplace_product_id']) . "%'";
        }

        if (!empty($data['filter_stock_prefix'])) {


            if ($data['filter_stock_prefix'] != '*') {
                if($data['filter_stock'] !== false) {

                    $sql .= " AND p.quantity " . $data['filter_stock_prefix'] . " " . $data['filter_stock'] . " ";
                }
            }
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

    public function getProducts($data = array(),$for_bulk=null)
    {



        if ($this->config->get('easy_setting_customer_group')) {
            $customer_group_id = $this->config->get('easy_setting_customer_group');
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }



        $sql = "SELECT p.product_id,p.model,p.manufacturer_id,p.price,m.name as manufacturer, p.image,p2m.n11,p2m.gg,p2m.cs,p2m.ty,p2m.amz,p2m.hb,p2m.eptt,p.status,pd.name,p.quantity,p.date_added,(select count(*) from " . DB_PREFIX . "product_option where product_id=p.product_id) as total_options ";


        if (method_exists($this->currency, 'getCodeOrDefault')) {
            $sql.=", p.currency_id ";
        }

        $sql.=" FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "es_product_to_marketplace p2m ON(p2m.product_id=p.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON(p.manufacturer_id=m.manufacturer_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p2c.product_id=p.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";





        if (!empty($data['filter_stock_prefix'])) {


            if ($data['filter_stock_prefix'] != '*') {
                if($data['filter_stock'] !== false) {

                    $sql .= " AND p.quantity " . $data['filter_stock_prefix'] . " " . $data['filter_stock'] . " ";
                }
            }
        }


        if (isset($data['filter_status']) && $data['filter_status'] !== '*') {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }


        if (!empty($data['filter_except'])) {

            $sql .= " AND (p2m." . $data['filter_except'] . "='' or  p2m." . $data['filter_except'] . " is null) ";
        }


        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";


        }

        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }

        if (!empty($data['filter_price'])) {
            $sql .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
        }

        if (isset($data['filter_quantity']) && $data['filter_quantity'] !== '') {
            $sql .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
        }



        if (!empty($data['filter_category'])) {
            $sql .= " AND p2c.category_id='" . $data['filter_category'] . "' ";
        }
        if (!empty($data['filter_manufacturer'])) {
            $sql .= " AND p.manufacturer_id='" . $data['filter_manufacturer'] . "' ";
        }


        if (isset($data['filter_product'])) {
            $sql .= " AND p.product_id = '" . (int)$data['filter_product'] . "'";
        }

        if (!empty($data['filter_marketplace'])) {

            if ($data['filter_marketplace'] != '*') {

                if (isset($data['filter_marketplace_do'])) {

                    if ($data['filter_marketplace_do'] == 1) {
                        if ($data['filter_marketplace'] == 'n11'){
                            $status = '"sale_status";i:2';
                            $alternatif_status = '"sale_status";b:2';

                        }else{
                            $status = '"sale_status";i:1';
                            $alternatif_status = '"sale_status";b:1';

                        }
                        $sql .= " AND (p2m." . $data['filter_marketplace'] . " LIKE '%" . $status . "%'  or  p2m." . $data['filter_marketplace'] . " LIKE '%" . $alternatif_status . "%'  )  ";



                    }elseif ($data['filter_marketplace_do'] == 2){
                        $status = 'sale_status";i:0';
                        $alternatif_status = 'sale_status";b:0';

                        $sql .= " AND ( p2m." . $data['filter_marketplace'] . " LIKE '%" . $status . "%'  or  p2m." . $data['filter_marketplace'] . " LIKE '%" . $alternatif_status . "%')    ";


                    } else {

                        $sql .= " AND (p2m." . $data['filter_marketplace'] . "='' or p2m." . $data['filter_marketplace'] . " is null )";

                    }

                }else {
                    $sql .= " AND (p2m." . $data['filter_marketplace'] . "='' or p2m." . $data['filter_marketplace'] . " is null )";

                }
            }
        }


        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.price',
            'p.quantity',
            'p.status',
            'p.date_added',
            'p.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }


        if ($for_bulk == "for_bulk"){

        }else{

            if (isset($data['start']) || isset($data['limit'])) {
                if ($data['start'] < 0) {
                    $data['start'] = 0;
                }

                if ($data['limit'] < 1) {
                    $data['limit'] = 20;
                }

                $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
            }

        }


        // echo $sql;return ;



        try {
            $query = $this->db->query($sql);
            return $query->rows;

        } catch (Exception $exception) {

            echo $exception->getMessage();
        }

    }


    public function getProductImages($product_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

        return $query->rows;
    }

    public function getProductSpecials($product_id)
    {

        if ($this->config->get('easy_setting_customer_group')) {

            $customer_group_id = $this->config->get('easy_setting_customer_group');
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "' and customer_group_id='" . $customer_group_id . "' ORDER BY priority, price");

        return $query->rows;
    }

    public function getTotalProductsNoFilter()
    {

        $sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)";


        $query = $this->db->query($sql);
        return $query->row['total'];
    }


    public function getTotalProducts($data = array())
    {
        $sql1 = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p   ";

        $sql2 = " WHERE p.product_id is not NULL ";

        if (!empty($data['filter_name'])) {

            $sql1 .= "LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) ";
            $sql2 .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        if (!empty($data['filter_in_notname'])){
            $sql1 .= "LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) ";

            $sql2 .= " and pd.name not like ( '%" . $data['filter_in_notname'] . "%') ";

        }

        if (!empty($data['filter_marketplace'])) {


            if ($data['filter_marketplace'] != '*') {
                $sql1 .= " LEFT JOIN " . DB_PREFIX . "es_product_to_marketplace p2m ON(p2m.product_id=p.product_id) ";

                if (isset($data['filter_marketplace_do'])) {

                    if ($data['filter_marketplace_do'] == 1) {
                        if ($data['filter_marketplace'] == 'n11') {
                            $status = '"sale_status";i:2';
                            $alternatif_status = '"sale_status";b:2';

                        } else {
                            $status = '"sale_status";i:1';
                            $alternatif_status = '"sale_status";b:1';

                        }
                        $sql2 .= " AND ( p2m." . $data['filter_marketplace'] . " LIKE '%" . $status . "%'  or  p2m." . $data['filter_marketplace'] . " LIKE '%" . $alternatif_status . "%' )   ";


                    } elseif ($data['filter_marketplace_do'] == 2) {
                        $status = 'sale_status";i:0';
                        $alternatif_status = 'sale_status";b:0';

                        $sql2 .= " AND p2m." . $data['filter_marketplace'] . " LIKE '%" . $status . "%'  or  p2m." . $data['filter_marketplace'] . " LIKE '%" . $alternatif_status . "%'    ";


                    } else {

                        $sql2 .= " AND p2m." . $data['filter_marketplace'] . "=''";

                    }

                } else {
                    $sql2 .= " AND p2m." . $data['filter_marketplace'] . "!=''";

                }
            }
        }


        if (isset($data['filter_status']) && $data['filter_status'] !== '*') {
            $sql2 .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_stock_prefix'])) {

            if ($data['filter_stock']) {


                if ($data['filter_stock_prefix'] != '*') {

                    $sql2 .= " AND p.quantity " . $data['filter_stock_prefix'] . " " . $data['filter_stock'] . " ";

                }
            }

        }

        if (!empty($data['filter_model'])) {
            $sql2 .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (!empty($data['filter_category'])) {

            $sql1 .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p2c.product_id=p.product_id) ";
            $sql2 .= " AND p2c.category_id='" . $data['filter_category'] . "' ";
        }
        if (!empty($data['filter_manufacturer'])) {
            $sql2 .= " AND p.manufacturer_id='" . $data['filter_manufacturer'] . "' ";
        }

        if (isset($data['filter_price']) && !is_null($data['filter_price'])) {
            $sql2 .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
        }

        if (isset($data['filter_quantity']) && $data['filter_quantity'] !== '') {
            $sql2 .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '*') {
            $sql2 .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }


        $sql = $sql1 . $sql2;


        //  echo $sql;
        //  return ;

        try {
            $query = $this->db->query($sql);

            return $query->row['total'];
        } catch (Exception $exception) {
            echo $exception->getMessage();

        }

    }


    public function getTotalProductByMarketPlace($marketplace)
    {

        $query = $this->db->query("SELECT COUNT(product_id) as total FROM `" . DB_PREFIX . "es_product_to_marketplace` WHERE `$marketplace` !=''");

        return $query->row['total'];

    }


}
