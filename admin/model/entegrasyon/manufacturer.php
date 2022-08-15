<?php
class ModelEntegrasyonManufacturer extends Model {


    public function manufacturerSettingDelete($code){


       

        if ($code == "all" ){

            $sql="DELETE FROM " . DB_PREFIX . "es_manufacturer ";


        }else {
            $sql="UPDATE " . DB_PREFIX . "es_manufacturer SET ".$code."= ''";

        }

        $this->db->query($sql);

    }

    public function deleteManufacturer($manufacturer_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer` WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_store` WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");

        $this->cache->delete('manufacturer');
    }

    public function getManufacturers($data = array()) {
        $sql = "SELECT m.name, m.manufacturer_id,esm.n11,esm.gg,esm.cs,esm.hb,esm.ty,esm.eptt FROM " . DB_PREFIX . "manufacturer m LEFT JOIN ".DB_PREFIX."es_manufacturer esm  ON(esm.manufacturer_id=m.manufacturer_id)";

        if (!empty($data['filter_manufacturer'])) {
            $sql .= " WHERE m.manufacturer_id = '" . $data['filter_manufacturer'] . "'";
        }

        $sort_data = array(
            'name',
            'sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
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
        }catch (Exception $exception){

            echo $exception->getMessage();
        }


    }
    
    public function getTotalManufacturers($data) {
        $sql= "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "manufacturer";
        if (!empty($data['filter_manufacturer'])) {

            $sql .= " WHERE manufacturer_id='" . $this->db->escape($data['filter_manufacturer']) . "'";
        }
        $query = $this->db->query($sql);
        return $query->row['total'];
    }
}
