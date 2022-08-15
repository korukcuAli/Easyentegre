<?php
class ModelEntegrasyonQuestionN11 extends Model {


    public function getQuestions()
    {


        $questions_data=array();
        $post_data['request_data'] = 'OPEN';

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('n11');

        $questions = $this->entegrasyon->clientConnect($post_data, 'get_questions', 'n11', false);


//print_r($questions);return ;


        if($questions['status']){

            if(isset($questions['result']['productQuestions'])){

                if($questions['result']['productQuestions']){

            if(isset($questions['result']['productQuestions']['productQuestion']['question'])){

                $question=$questions['result']['productQuestions']['productQuestion'];



                $questions_data[]=array(
                    'rejected'=>false,
                    'new_message'=>false,
                    'id'=>$question['id'],
                    'product'=>$question['productTitle'],
                    'user'=>'',
                    'text'=>$question['question'],
                    'created_date'=>date('Y-m-d H:i:s')
                );




            }else {

                foreach ($questions['result']['productQuestions']['productQuestion'] as $question) {



                    $rejected=false;
                    if(isset($question['rejectedAnswer'])){
                        $rejected=true;
                    }

                    $questions_data[]=array(
                        'rejected'=>$rejected,
                        'id'=>$question['id'],
                        'new_message'=>false,

                        'product'=>$question['productTitle'],
                        'user'=>'',
                        'text'=>$question['question'],
                        'created_date'=>date('Y-m-d H:i:s')
                    );





                }

            }}



            return array('status'=>true,'message'=>'','result'=>$questions_data);


        }else {

                return array('status' => false, 'message' => $questions['message'], 'result' => array());

            }
            return array('status' => false, 'message' => $questions['message'], 'result' => array());

        }



    }



}
