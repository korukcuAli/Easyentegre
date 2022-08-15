<?php

class ModelEntegrasyonCategory extends Model
{


    public function categorySettingDelete($code){



        if ($code == "all" ){

            $sql="DELETE FROM " . DB_PREFIX . "es_category ";


        }else {
            $sql="UPDATE " . DB_PREFIX . "es_category SET ".$code."= ''";

        }

        $this->db->query($sql);

    }


    public function deleteCategory($category_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_path WHERE path_id = '" . (int)$category_id . "'");

        foreach ($query->rows as $result) {
            $this->deleteCategory($result['category_id']);
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$category_id . "'");

        $this->cache->delete('category');
    }




    public function getCategory($category_id)
    {
        $query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id AND cp.category_id != cp.path_id) WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }


    public function getMarketCategory($category_id,$code)
    {

        $query=$this->db->query("select * from ".DB_PREFIX."es_category where category_id='".$category_id."'");
        return unserialize($query->row[$code]);

    }

    public function getCategories($data = array())
    {
        $sql = "SELECT es.n11,es.gg,es.cs,es.hb,es.ty,es.amz,es.eptt, cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, c1.parent_id, c1.sort_order FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) LEFT JOIN " . DB_PREFIX . "es_category es ON(es.category_id=c1.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_category'])) {
            $sql .= " AND c1.category_id ='" . $data['filter_category'] . "'";
        }

        $sql .= " GROUP BY c1.category_id";

        $sort_data = array(
            'name',
            'sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sort_order";
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


        try {
            $query = $this->db->query($sql);

            return $query->rows;
        } catch (Exception $exception) {

            echo $exception->getMessage();
        }

    }


    public function getCategoryPath($category_id)
    {
        $query = $this->db->query("SELECT category_id, path_id, level FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");

        return $query->rows;
    }


    public function getTotalCategories($data)
    {

        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category";

        if ($data['filter_category']) {

            $sql .= " where category_id='" . $data['filter_category'] . "'";
        }
        $query = $this->db->query($sql);
        return $query->row['total'];
    }


    public function getAttributes($category_id, $code, $is_varianter = false)
    {



        $attributes = array();
        $message = '';
        $required = array();
        $status = true;
        ini_set("memory_limit", '-1');
        ini_set('max_execution_time', 118000);
        $query = $this->db->query("select * from " . DB_PREFIX . "es_attribute where category_id='" . $category_id . "' and code='" . $code . "' ");


        if (!$query->num_rows) {

            $from = 'marketplace';

            $result = $this->getAttributesFromMarketPlace($category_id, $code);


            $status = $result['status'];
            $message = $result['message'];

            return array('status' => $status, 'message' => $message, 'result' => $result['result']);


        } else {

            $result = $this->getAttributesFromDb($category_id, $code, $is_varianter);



            $from = 'db';

            
            return array('status' => $status, 'message' => $message, 'result' => $result);

        }




    }

    public function getAttributesFromDb($category_id, $code, $is_varianter = false)
    {

        $atributes = array();

        $sql = "select * from " . DB_PREFIX . "es_attribute where category_id='" . $category_id . "' and code='" . $code . "' ";


        $query = $this->db->query($sql);


        if ($query->num_rows) {

            if ($is_varianter) {
                foreach (unserialize($query->row['attribute']) as $key => $item) {


                    if (is_numeric($key)) {

                        //echo 'numerik'.$key.'<br>';

                        if (!$item['varianter']) {
                            $atributes[] = $item;
                        }

                    } else {

                        // echo 'numerik değil'.$key.'<br>';;
                        $atributes[$key] = $item;

                    }


                }


            } else {


                $atributes= unserialize($query->row['attribute']);


            }

        }else {

            $atributes = array();
        }


        return $atributes;

    }

    public function getAttributesFromMarketPlace($category_id, $code)
    {

        $post_data['request_data'] = $category_id;
        $this->load->model('entegrasyon/general');


        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace($code);
        $result = $this->entegrasyon->clientConnect($post_data, 'category_attributes', $code, false);


        $this->load->model('entegrasyon/category/' . $code);


        if ($result['status']) {

            $attributes = $this->{'model_entegrasyon_category_' . $code}->renderAttributes($result);



            $get_attribute = $this->db->query("SELECT * FROM " . DB_PREFIX . "es_attribute WHERE category_id='" . $category_id . "' and code='" . $code . "' ");

            if (!$get_attribute->num_rows) {
                try {

                    $this->db->query("INSERT INTO " . DB_PREFIX . "es_attribute SET category_id='" . $category_id . "', attribute='" . $this->db->escape(serialize($attributes)) . "' , code='" . $code . "',required='" . $this->db->escape(serialize($attributes['required_attributes'])) . "'");

                } catch (Exception $exception) {
                    echo $exception->getMessage();
                }
            }

        }
        $getFromDb = $this->getAttributesFromDb($category_id, $code);

        
        return array('status' => $result['status'], 'message' => $result['message'], 'result' => $getFromDb);

    }


}