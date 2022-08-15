<?php
class ModelEntegrasyonProductQuestion extends Model{

  /* public function getQuestions()
    {

        $quetions[]= array('question_id'=>'1','code'=>'ty','user'=>'Fırat YAKUT','question'=>'denemetet','date_added'=>'11-10-2020 05:55:00');
        $quetions[]= array('question_id'=>'2','code'=>'n11','user'=>'Ali Körükçü','question'=>'Hocam ürün hakkında detaylı bilgi rica edeceyim.','date_added'=>'24-09-2020 14:43:21');
        $quetions[]= array('question_id'=>'3','code'=>'gg','user'=>'Muhamed Sephe','question'=>'Kaç para lan bi flüt??!!?','date_added'=>'23-10-2020 11:44:00');
        $quetions[]= array('question_id'=>'3','code'=>'gg','user'=>'Muhamed Sephe','question'=>'Kaç para lan bi flüt??!!?','date_added'=>'23-10-2020 11:44:00');
        $quetions[]= array('question_id'=>'4','code'=>'n11','user'=>'Pelin Koçak','question'=>'Ürünün ön yüzü parlak mı? Fotoğrafta mı öyle gözüküyor?','date_added'=>'05-11-2020 17:55:00');
        $quetions[]= array('question_id'=>'5','code'=>'cs','user'=>'Kemal Canyürek','question'=>'5 aylık çocuğum için uygun olur mu?','date_added'=>'31-10-2020 15:32:00');


        return $quetions;

}*/

    public function getQuestions($filter_data) {
        $sql = "SELECT * FROM " . DB_PREFIX . "es_product_question ";


   /*     if(!empty($filter_data['filter_question_status'])){

            if($filter_data['filter_question_status']!='*'){

                $sql.=" and answered='".$filter_data['filter_question_status']."'";

            }
        }*/

        if(!empty($filter_data['filter_marketplace'])){

            if($filter_data['filter_marketplace']!='*'){

                $sql.=" where code='".$filter_data['filter_marketplace']."'";

            }
        }

        $sql.=" order by date_added DESC";

        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        $query = $this->db->query($sql);
        return $query->rows;
    }


    public function getTotalQuestions($filter_data) {
        $sql = "SELECT count(*) as total FROM " . DB_PREFIX . "es_product_question ";


        if(!empty($filter_data['filter_marketplace'])){

            if($filter_data['filter_marketplace']!='*'){

                $sql.=" where code='".$filter_data['filter_marketplace']."'";

            }
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }




    public function answeredQuestion($question_id) {

        $sql="UPDATE oc_es_product_question
             SET 	answered = 1,is_rejected =0
             WHERE question_id = '" . $question_id. "'";
        $this->db->query($sql);


    }


}