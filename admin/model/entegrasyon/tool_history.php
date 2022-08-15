<?php

class ModelEntegrasyonToolHistory extends Model
{

    public function addToolHistory($code,$do,$tool_category)
    {

        print_r('eklendi');

        $this->db->query("INSERT INTO " . DB_PREFIX . "es_tool_history SET
        code = '" . $code . "', 
        tool_name = '" .  $do . "',
        tool_category = '" .  $tool_category ."'
                 ");

    }

    public function getHistorys()
    {

        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "es_tool_history order by history_id desc");

        return $query->rows;

    }

    public function delHistory($history_id)
    {
        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . " es_tool_history  WHERE history_id =  " . $history_id . "''");


    }


}