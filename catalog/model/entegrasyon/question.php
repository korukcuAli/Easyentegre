<?php

class ModelEntegrasyonQuestion extends Model
{

    public function addQuestion($question, $code)
    {




        try {

            $this->db->query("INSERT INTO " . DB_PREFIX . "es_product_question 
                SET code='" . $code . "', 
                question_id='" . $question['id'] . "',
                `user`='" .$this->db->escape($question['user']). "',
                `product`='" .$this->db->escape($question['product']). "',
                	is_rejected='" . $question['rejected'] . "',
                	message='" . $this->db->escape($question['text']) . "',
              
                	date_added='" . $question['created_date'] . "'
                
                ");

        } catch (Exception $exception) {

            echo $exception->getMessage();

        }


        $last_insert_id = $this->db->getLastId();

        return $last_insert_id;



    }


    public function updateQuestion($question)
    {

        $this->db->query("UPDATE " . DB_PREFIX . "es_product_question SET answered=0 where question_id='".$question['id']."'");
    }

   public function updateRejectedQuestion($question)
    {

        $this->db->query("UPDATE " . DB_PREFIX . "es_product_question SET is_rejected=1 where question_id='".$question['id']."'");
    }

    public function getQuestion($question_id)
    {
        $query = $this->db->query("select * from " . DB_PREFIX . "es_product_question where question_id='" . $question_id . "'");
        return $query->row;


    }



}


