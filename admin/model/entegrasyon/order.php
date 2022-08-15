<?php

class ModelEntegrasyonOrder extends  Model{


    public function getOrders($filter_data)
    {


        $sql="select distinct  * from ".DB_PREFIX."es_order o left join ".DB_PREFIX."es_order_status os ON(os.order_status_id=o.order_status) where 1=1  ";



     if(!empty($filter_data['filter_order_status_id'])){

         if($filter_data['filter_order_status_id']!='*'){

             $sql.=" and order_status='".$filter_data['filter_order_status_id']."'";

         }
     }

        if(!empty($filter_data['filter_marketplace'])){

            if($filter_data['filter_marketplace']!='*'){

                $sql.=" and code='".$filter_data['filter_marketplace']."'";

            }
        }

   //     echo $this->db->escape(date('Y-m-d H:i:s', strtotime($filter_data['filter_date_modified'])));

     //   return;

        if (!empty($filter_data['filter_order_id'])) {
            $sql .= " AND market_order_id LIKE '%" . (int)$filter_data['filter_order_id'] . "%'";
        }

        if (!empty($filter_data['filter_customer'])) {
            $sql .= " AND CONCAT(o.first_name, ' ', o.last_name) LIKE '%" . $this->db->escape($filter_data['filter_customer']) . "%'";
        }

        if (!empty($filter_data['filter_date_added'])) {
            $sql .= " AND DATE(o.date_added) >= DATE('" . $this->db->escape(date('Y-m-d H:i:s', strtotime($filter_data['filter_date_added']))) . "')";
        }

        if (!empty($filter_data['filter_date_modified'])) {
            $sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape(date('Y-m-d H:i:s', strtotime($filter_data['filter_date_modified']))) . "')";
        }


            $sql .= " ORDER BY o.date_added";



            $sql .= " DESC";


     if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }


      $query =  $this->db->query($sql);



      return $query->rows;


    }

    public function getOrder($order_id)
    {

        $query =  $this->db->query("select * from ".DB_PREFIX."es_order o left join ".DB_PREFIX."order_status os ON(os.order_status_id=o.order_status) where o.order_id='".$order_id."'");

        return $query->row;


    }
    



    public function getTotalOrders($filter_data)
    {

        $query =  $this->db->query("select COUNT(*) as total from ".DB_PREFIX."es_order");

        return $query->row['total'];

    }

    public function getOrderedProducts($order_id)
    {
        $query =  $this->db->query("select * from ".DB_PREFIX."es_ordered_product where order_id='".$order_id."'");

        return $query->rows;
    }

    public function getOrderStatus($code,$status)
    {


        $query =  $this->db->query("select * from ".DB_PREFIX."es_order_status where $code='".$status."'" );

           return $query->row['name'];




    }
    public function getOrderStatuses()
    {
        $query =  $this->db->query("select * from ".DB_PREFIX."es_order_status" );

        return $query->rows;

    }

    public function getOrderbyToday($code)
    {
        $query=$this->db->query("SELECT COUNT(*) AS total from ".DB_PREFIX."es_order  WHERE  code='".$code."' AND DATE(date_added) = CURDATE()");
        return $query->row['total'];
    }

    public function getOrderbyTotal($code)
    {
        $query=$this->db->query("SELECT COUNT(*) AS total from ".DB_PREFIX."es_order  WHERE  code='".$code."'");
        return $query->row['total'];
    }

    public function getTotalOrdersByDay($marketplace) {

        $order_data = array();

        for ($i = 0; $i < 24; $i++) {
            $order_data[$i] = array(
                'hour'  => $i,
                'total' => 0
            );
        }

        $query = $this->db->query("SELECT COUNT(*) AS total, HOUR(date_added) AS hour FROM `" . DB_PREFIX . "es_order` WHERE code='".$marketplace."' AND DATE(date_added) = DATE(NOW()) GROUP BY HOUR(date_added) ORDER BY date_added ASC");

        foreach ($query->rows as $result) {
            $order_data[$result['hour']] = array(
                'hour'  => $result['hour'],
                'total' => $result['total']
            );
        }

        return $order_data;
    }

    public function getTotalOrdersByWeek($marketplace) {

        $order_data = array();

        $date_start = strtotime('-' . date('w') . ' days');

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', $date_start + ($i * 86400));

            $order_data[date('w', strtotime($date))] = array(
                'day'   => date('D', strtotime($date)),
                'total' => 0
            );
        }

        $query = $this->db->query("SELECT COUNT(*) AS total, date_added FROM `" . DB_PREFIX . "es_order` WHERE code='".$marketplace."' AND DATE(date_added) >= DATE('" . $this->db->escape(date('Y-m-d', $date_start)) . "') GROUP BY DAYNAME(date_added)");

        foreach ($query->rows as $result) {
            $order_data[date('w', strtotime($result['date_added']))] = array(
                'day'   => date('D', strtotime($result['date_added'])),
                'total' => $result['total']
            );
        }

        return $order_data;
    }

    public function getTotalOrdersByMonth($marketplace) {


        $order_data = array();

        for ($i = 1; $i <= date('t'); $i++) {
            $date = date('Y') . '-' . date('m') . '-' . $i;

            $order_data[date('j', strtotime($date))] = array(
                'day'   => date('d', strtotime($date)),
                'total' => 0
            );
        }

        $query = $this->db->query("SELECT COUNT(*) AS total, date_added FROM `" . DB_PREFIX . "es_order` WHERE code='".$marketplace."' AND DATE(date_added) >= '" . $this->db->escape(date('Y') . '-' . date('m') . '-1') . "' GROUP BY DATE(date_added)");

        foreach ($query->rows as $result) {
            $order_data[date('j', strtotime($result['date_added']))] = array(
                'day'   => date('d', strtotime($result['date_added'])),
                'total' => $result['total']
            );
        }

        return $order_data;
    }


    public function getTotalOrdersByYear($marketplace) {



        $order_data = array();

        for ($i = 1; $i <= 12; $i++) {
            $order_data[$i] = array(
                'month' => date('M', mktime(0, 0, 0, $i)),
                'total' => 0
            );
        }

        $query = $this->db->query("SELECT COUNT(*) AS total, date_added FROM `" . DB_PREFIX . "es_order` WHERE code='".$marketplace."' AND YEAR(date_added) = YEAR(NOW()) GROUP BY MONTH(date_added)");


        foreach ($query->rows as $result) {
            $order_data[date('n', strtotime($result['date_added']))] = array(
                'month' => date('M', strtotime($result['date_added'])),
                'total' => $result['total']
            );
        }

        return $order_data;
    }




}

