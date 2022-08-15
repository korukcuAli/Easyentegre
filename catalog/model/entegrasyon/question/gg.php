<?php
class ModelEntegrasyonQuestionGg extends Model {


    public function getQuestions()
    {


        $questions_data=array();
        $post_data['request_data'] = 'OPEN';

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('gg');

        $questions = $this->entegrasyon->clientConnect($post_data, 'get_questions', 'gg', false);




        if($questions['status']){

            if (!isset($questions['result']['result']['conversationsCount'])){
                return array('status'=>false,'message'=>$questions['message'],'result'=>array());

            }
            if($questions['result']['result']['conversationsCount']>0){




                if($questions['result']['result']['conversationsCount']==1){


                    $question=$questions['result']['result']['conversations']['conversation'];


                    $rejected = false;
                    if (isset($question['rejectedAnswer'])) {
                        $rejected = true;
                    }
                    $new_message = false;


                    $user = $this->config->get('gg_role_kullanici_adi');

                    foreach ($question['participants']['participant'] as $participant) {

                        if ($user == $participant['nickName']) {

                            if (!$participant['isRead']) $new_message = true;

                        }
                    }


                    $questions_data[] = array(
                        'rejected' => $rejected,
                        'new_message' => $new_message,
                        'id' => $question['conversationId'],
                        'product' => isset($question['context']['product']['productTitle']) ? $question['context']['product']['productTitle'] : '',
                        'user' => '',
                        'text' => '',
                        'created_date' => date('Y-m-d H:i:s', strtotime($question['createDate']))
                    );


                }else {


                    foreach ($questions['result']['result']['conversations']['conversation'] as $question) {

                        $rejected = false;
                        if (isset($question['rejectedAnswer'])) {
                            $rejected = true;
                        }
                        $new_message = false;


                        $user = $this->config->get('gg_role_kullanici_adi');

                        foreach ($question['participants']['participant'] as $participant) {

                            if ($user == $participant['nickName']) {

                                if (!$participant['isRead']) $new_message = true;

                            }
                        }


                        $questions_data[] = array(
                            'rejected' => $rejected,
                            'new_message' => $new_message,
                            'id' => $question['conversationId'],
                            'product' => isset($question['context']['product']['productTitle']) ? $question['context']['product']['productTitle'] : '',
                            'user' => '',
                            'text' => '',
                            'created_date' => date('Y-m-d H:i:s', strtotime($question['createDate']))
                        );


                    }

                }
            }




            return array('status'=>true,'message'=>'','result'=>$questions_data);


        }else {

            return array('status'=>false,'message'=>$questions['message'],'result'=>array());


        }



    }



}
