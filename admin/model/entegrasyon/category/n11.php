<?php

class ModelEntegrasyonCategoryN11 extends Model
{

    
    public function renderAttributes($result)
    {

        $attributes = array();
        $required=array();


        foreach ($result['result'] as $attribute) {

            $attributes[]=$attribute;
            if ($attribute['required']) {
                if($attribute['name']!='Marka'){

                $required[] = $attribute['name'];

                }
            }
        }

        $attributes['required_attributes']=$required;

        return $attributes;

    }



    public function getCategoryOptions($cat)
    {

        return false;

    }

}