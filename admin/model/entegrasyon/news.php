<?php
class ModelEntegrasyonNews extends Model {



    public function get_news_data()
    {



       /* foreach($news_data as $k => $v) {
            $postData .= $k . '='.$v.'&';
        }
*/
        $postData = '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.easyentegre.com/index.php?route=api/information');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $result = curl_exec($ch);

      return json_decode($result,1);

    }



}