<?php

class  ModelEntegrasyonTool extends Model {



    private $chanced_paths=array();

    public function checkImagePath($image)
    {

        $thisimage=$image;
        $image_array= explode('/',$image);
        array_pop($image_array);

        $image_variants=array();

        if($this->chanced_paths) {
            foreach ($image_array as $item) {

                $thisimage =  str_replace($item, $this->findChancedFolder($item), $thisimage);

                $image_variants[]=$thisimage;

            }


            foreach ($image_variants as $image_variant) {
                if(is_file(DIR_IMAGE.$image_variant)) {
                    return $image_variant;
                }

            }

        }

        return $thisimage;

    }

    private function findChancedFolder($folder)
    {

        foreach ($this->chanced_paths as $index => $chanced_path) {
            if($folder==$index)
            {
                return $chanced_path;
            }
        }

        return  $folder;
    }

    public function optimize_image($image,$image_type,$primary_id)
    {


        if(isset($this->session->data['chanced_paths'])){
            $this->chanced_paths = $this->session->data['chanced_paths'];
        }

        $image = $this->checkImagePath($image);

        $bosluk = strpos($image, ' ');

        if ($bosluk) {

            if (is_file(DIR_IMAGE . $image)) {

                $changed_paths=$this->clearImage($image);

                $orginal_path=DIR_IMAGE.$changed_paths['folder_path'].'/'.$changed_paths['original_image'];
                $changed_path=DIR_IMAGE.$changed_paths['folder_path'].'/'.$changed_paths['optimized_image'];
                //$changed_path_array=explode('/',$changed_paths['folder_path']);
                //$original_path_array=explode('/',$changed_paths['original_image']);
                foreach ($changed_paths['chanced_folder_array'] as $index => $item) {
                    if(!in_array($item,$this->chanced_paths) ){
                        $this->chanced_paths[$item]=$changed_paths['original_folder_array'][$index];
                    }
                }

                $this->session->data['chanced_paths']=$this->chanced_paths;
                rename($orginal_path,$changed_path);
                $this->update_image($changed_paths['folder_path'].'/'.$changed_paths['optimized_image'],$primary_id,$image_type);

            }
        }else {

            $this->update_image($image,$primary_id,$image_type);

        }

    }


    private function update_image($image,$primary_id,$image_type)
    {
        if ($image_type=='main') {

            //$product_id = $produduct_image->row['product_id'];

            $this->db->query("UPDATE " . DB_PREFIX . "product SET image='" . $image . "' WHERE product_id=" . (int)$primary_id . " ");

        }else {

            $this->db->query("UPDATE " . DB_PREFIX . "product_image SET image='" . $image . "' WHERE product_image_id='" . (int)$primary_id . "' ");

        }

    }


    private function clearImage($image)
    {
//Veritabanına kayıtlı olan imajı seoya uygun hale getirir


        $folders=explode('/',$image);

        array_pop($folders);
        $folderorg=$folders;
        $optimized_folders=array();


        foreach ($folders as $index => $folder){
            if(strpos($folder, ' ')){

                $make_optimize_folder=$this->createSEOKeyword($folder);
                $optimized_folders[]=$make_optimize_folder;
                $orginal_folders=array();
                for($i=0; $i<$index+1; $i++ ){
                    $orginal_folders[]=$folders[$i];
                }

                $orginal_path=DIR_IMAGE.implode('/',$orginal_folders);
                $chanced_path=DIR_IMAGE.implode('/',$optimized_folders);
                //echo 'original_path'.$orginal_path.'<br>';
                //echo 'chanced_path'.$chanced_path.'<br>';
                rename($orginal_path,$chanced_path );

                $folders[$index]=$make_optimize_folder;

            }else {

                $optimized_folders[]=$folder;
            }

        }


        $basename=basename($image);
        $file_path=explode($basename,$image);
        $ext = pathinfo($basename, PATHINFO_EXTENSION);
        $withoutExt=explode($ext,$basename);

        $temizle=$this->createSEOKeyword($withoutExt[0]);
        $optimized_image=$temizle.'.'.$ext;

        return array('folder_path'=>implode('/',$optimized_folders),'original_folder_array'=>$optimized_folders,'chanced_folder_array'=>$folderorg,'optimized_image'=>$optimized_image,'original_image'=>$withoutExt[0].$ext);
    }




    public function createSEOKeyword($title, $options = array('transliterate'))
    {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $title = mb_convert_encoding((string)$title, 'UTF-8', mb_list_encodings());
        $defaults = array(
            'delimiter' => '-',
            'limit' => null,
            'lowercase' => true,
            'replacements' => array(),
            'transliterate' => true,
        );
        // Merge options
        $options = array_merge($defaults, $options);
        $char_map = array(
            // Latin
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y', 'ž' => 'z',
            // Latin symbols
            '©' => '(c)',
            // Greek
            'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
            'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
            'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
            'Ϋ' => 'Y',
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
            'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
            'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
            'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
            'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
            // Turkish
            'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
            'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',
            // Russian
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
            'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya',
            // Ukrainian
            'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
            // Czech
            'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
            'Ž' => 'Z',
            'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
            'ž' => 'z',
            // Polish
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
            'Ż' => 'Z',
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z',
            // Latvian
            'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
            'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
            'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
            'š' => 's', 'ū' => 'u', 'ž' => 'z',
            //special
            'Ľ' => 'L', 'ľ' => 'l',
        );
        // Make custom replacements
        $title = preg_replace(array_keys($options['replacements']), $options['replacements'], $title);
        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $title = str_replace(array_keys($char_map), $char_map, $title);
        }
        // Replace non-alphanumeric characters with our delimiter
        $title = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $title);
        // Remove duplicate delimiters
        $title = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $title);
        // Truncate slug to max. characters
        $title = mb_substr($title, 0, ($options['limit'] ? $options['limit'] : mb_strlen($title, 'UTF-8')), 'UTF-8');
        // Remove delimiter from ends
        $title = trim($title, $options['delimiter']);
        return $options['lowercase'] ? mb_strtolower($title, 'UTF-8') : $title;
    }

    public function replaceSpace($string)
    {

        $string = str_replace(' ', '', $string);
        $string = preg_replace('/\s+/', '', $string);
        $string = trim($string);

        $string = strtolower($string);

        $res = str_split($string, 1);

        $kontrol = array();

        foreach ($res as $re) {

            if (ctype_print($re)) {

                $kontrol[] = $re;
            }


        }


        $string = implode($kontrol);


        return $string;

    }




}



