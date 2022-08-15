<?php

class ModelEntegrasyonCategoryHb extends Model
{



    public function renderAttributes($result)
    {


        
        $required=array();

        $variants=array();

        foreach ($result['result'] as $key =>$attribute) {
            if ($attribute['required']) {
                $required[] = $attribute['id'];
            }

            if($attribute['varianter']){

                    $values=array();
                    if(is_array($attribute['values'])) {
                        foreach ($attribute['values'] as $atr_val) {


                            $values[] = array(
                                'value_id' => $atr_val['id'],
                                'name' => $atr_val['value'],
                                'order_number' => 1
                            );
                        }


                        $variants[] = array(
                            'name' => $attribute['name'],

                            'id' => $attribute['id'],
                            'values' => $values
                        );
                    }


               //unset($result['result'][$key]);
            }

        }





        $result['result']['variants']=$variants;


        $result['result']['required_attributes']=$required;

 
        return $result['result'];

    }



}