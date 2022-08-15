<?php
class ModelEntegrasyonQuestionTy extends Model {


    public function getQuestions()
    {


        $questions_data=array();
        $post_data['request_data'] = '';

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('ty');

        $questions = $this->entegrasyon->clientConnect($post_data, 'get_questions', 'ty', false);



        if($questions['status']){
            if(isset($questions['result']['content']['status'])){
                $question=$questions['result']['content'];
                    $rejected=false;
                    if(isset($question['rejectedAnswer'])){
                        $rejected=true;
                    }

                    $questions_data[]=array(
                    'rejected'=>$rejected,
                     'id'=>$question['id'],
                        'product'=>'',
                        'new_message'=>false,
                        'user'=>$question['userName'],
                     'text'=>$question['text'],
                     'created_date'=>date('Y-m-d H:i:s', substr($question['creationDate'], 0, 10))

                    );




            }else {


if(is_array($questions['result']['content'])){


                foreach ($questions['result']['content'] as $question) {



                    $rejected=false;
                    if(isset($question['rejectedAnswer'])){
                        $rejected=true;
                    }

                        $questions_data[]=array(
                            'rejected'=>$rejected,
                            'id'=>$question['id'],
                            'product'=>'',
                            'new_message'=>false,

                            'user'=>$question['userName'],
                            'text'=>$question['text'],
                            'created_date'=>date('Y-m-d H:i:s', substr($question['creationDate'], 0, 10))

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
