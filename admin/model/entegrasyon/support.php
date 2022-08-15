<?php
class ModelEntegrasyonSupport extends Model {



    public function get_support_data($ticket_data=array(),$action)
    {
/*
        if($ticket_data){
            $params_data=$ticket_data;
        }else {

            $params_data=$this->request->post;
        }
*/

        $postData = '';
        foreach($ticket_data as $k => $v) {
            $postData .= $k . '='.$v.'&';
        }

        //$action = $this->request->get['action'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.easyentegre.com/index.php?route=api/support/ticket&action='.$action);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $result = curl_exec($ch);

        return json_decode($result,1);

    }



}