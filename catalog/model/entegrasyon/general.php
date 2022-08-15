<?php

class ModelEntegrasyonGeneral extends Model {


    public function getMarketPlaces()
    {
        $this->load->model('tool/image');
        $marketplaces = array();
        $markets= unserialize($this->config->get('mir_marketplaces'));
        if ($markets){
            foreach ($markets as $market) {

                $marketplaces[] = array(
                    'name'      => $market['marketname'],
                    'logo'     =>$this->model_tool_image->resize('entegrasyon-logo/'.$market['code'].'-logo.png', 40, 40),
                    'status'    => $market['status'],
                    'member_type'=>$market['usergroupname'],
                    'code'      => $market['code'],
                    'domain_id'=>$market['domain_id'],
                    'domain_marketplace_id'=>$market['domain_marketplace_id'],
                    'edit'     => ''
                );
            }
        }
        return $marketplaces;

    }


    public function update_product_price($code,$competition_price,$product_id)
    {
        $query = $this->db->query("select * from " . DB_PREFIX . "es_product where product_id='" . $product_id . "'");
        $row = $query->row;

        $variable = $row[''. $code . ''];
        $settings = unserialize($variable);
        $settings[$code.'_product_sale_price'] = $competition_price;
        $this->db->query("update " . DB_PREFIX . "es_product SET $code='" . $this->db->escape(serialize($settings)) . "', date_modified=NOW() where product_id = '".$row['product_id']."' ");



    }

    public function checkPermission()
    {

        $ara = strpos(HTTP_SERVER,'easyentegre');

        if($ara){

          return false;
        }else {

            return true;
        }

    }

    public function getMarketPlace($code)
    {
        $this->load->model('tool/image');
        $markets= unserialize($this->config->get('mir_marketplaces'));
        foreach ($markets as $market) {
            if($market['code']==$code){
                return array(
                    'name'      => $market['marketname'],
                    'logo'     =>$this->model_tool_image->resize('entegrasyon-logo/'.$market['code'].'-logo.png', 40, 40),
                    'status'    => $market['status'],
                    'member_type'=>$market['usergroupname'],
                    'code'      => $market['code'],
                    'domain_id'=>$market['domain_id'],
                    'domain_marketplace_id'=>$market['domain_marketplace_id'],
                    'edit'     => ''
                );
            }
        }


    }




    public function checkSoap()
    {

        $status=true;
        if (!extension_loaded('soap')) {

            $status = false;

        }

        return $status;

    }

    public function getUpdatableProducts($data=array())
    {
      $last_update_date =  $this->config->get('module_entegrasyon_last_update');
        if(!$last_update_date){

            $last_update_date=date("Y-m-d H:i:s", strtotime("-1 day"));
        }
        $sql="select p.product_id,p.quantity,p.price,p2m.n11,p2m.cs,p2m.gg,p2m.hb,p2m.ty,p2m.eptt,p2m.amz,p.date_modified from ".DB_PREFIX."es_product_to_marketplace p2m LEFT JOIN ".DB_PREFIX."product p ON(p.product_id=p2m.product_id) where p.date_modified >= '".$last_update_date."' ";


        if(isset($data['market'])){

            $sql .= " AND p2m.".$data['market']."!=''";
        }



        $query=$this->db->query($sql);
        if($query->num_rows){
            return $query->rows;
        }else {

            return false;
        }
    }
    public function getUpdatableProductsTotal($data=array())
    {
      $last_update_date =  $this->config->get('module_entegrasyon_last_update');
        if(!$last_update_date){

            $last_update_date=date("Y-m-d H:i:s", strtotime("-1 day"));
        }
        $sql="select count(*) as total from ".DB_PREFIX."es_product_to_marketplace p2m LEFT JOIN ".DB_PREFIX."product p ON(p.product_id=p2m.product_id) where p.date_modified >= '".$last_update_date."' ";



        if(isset($data['market'])){

            $sql .= " AND p2m.".$data['market']."!=''";
        }


        $query=$this->db->query($sql);

        return $query->row['total'];
    }
    public function getUpdatableProductsTotalTest($data=array())
    {
      $last_update_date =  $this->config->get('module_entegrasyon_last_update');
        if(!$last_update_date){

            $last_update_date=date("Y-m-d H:i:s", strtotime("-1 day"));
        }
        $sql="select count(*) as total from ".DB_PREFIX."es_product_to_marketplace p2m LEFT JOIN ".DB_PREFIX."product p ON(p.product_id=p2m.product_id)  ";



        if(isset($data['market'])){

            $sql .= " WHERE p2m.".$data['market']."!=''";
        }




        $query=$this->db->query($sql);

        return $query->row['total'];
    }


    public function getUpdatableProductsTest($data=array())
    {
        $last_update_date =  $this->config->get('module_entegrasyon_last_update');
        if(!$last_update_date){

            $last_update_date=date("Y-m-d H:i:s", strtotime("-100000 day"));
        }


        $sql="select p.product_id,p.quantity,p.price,p2m.n11,p2m.cs,p2m.gg,p2m.hb,p2m.ty,p2m.eptt,p2m.amz,p.date_modified from ".DB_PREFIX."es_product_to_marketplace p2m LEFT JOIN ".DB_PREFIX."product p ON(p.product_id=p2m.product_id)";


        if(isset($data['market'])){

            $sql .= " WHERE p2m.".$data['market']."!=''";
        }

       // $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];





        $query=$this->db->query($sql);
        if($query->num_rows){
            return $query->rows;
        }else {

            return false;
        }
    }


    public function createUpdateSession($update_type)
    {
        $this->db->query("INSERT INTO ".DB_PREFIX."es_update_session SET update_type='".$update_type."', date_created=NOW() ");
        return $this->db->getLastId();

    }

    public function getLastUpdateSession($update_type)
    {
        $query=  $this->db->query("SELECT date_created FROM `".DB_PREFIX."es_update_session`  WHERE `update_type`=".$update_type." ORDER BY update_session_id DESC LIMIT 0,1");
        if($query->num_rows){
            return $query->row['date_created'];

        }else {

            return '2000-05-25 10:00:00';

        }

    }



    public function getProductByModel($model)
    {

        $query= $this->db->query("select * from ".DB_PREFIX."product where model='".$model."'");

        return $query->row;

    }






}