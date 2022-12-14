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
//Veritaban??na kay??tl?? olan imaj?? seoya uygun hale getirir


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
            '??' => 'A', '??' => 'A', '??' => 'A', '??' => 'A', '??' => 'A', '??' => 'A', '??' => 'AE', '??' => 'C',
            '??' => 'E', '??' => 'E', '??' => 'E', '??' => 'E', '??' => 'I', '??' => 'I', '??' => 'I', '??' => 'I',
            '??' => 'D', '??' => 'N', '??' => 'O', '??' => 'O', '??' => 'O', '??' => 'O', '??' => 'O', '??' => 'O',
            '??' => 'O', '??' => 'U', '??' => 'U', '??' => 'U', '??' => 'U', '??' => 'U', '??' => 'Y', '??' => 'TH',
            '??' => 'ss',
            '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'ae', '??' => 'c',
            '??' => 'e', '??' => 'e', '??' => 'e', '??' => 'e', '??' => 'i', '??' => 'i', '??' => 'i', '??' => 'i',
            '??' => 'd', '??' => 'n', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o',
            '??' => 'o', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'y', '??' => 'th',
            '??' => 'y', '??' => 'z',
            // Latin symbols
            '??' => '(c)',
            // Greek
            '??' => 'A', '??' => 'B', '??' => 'G', '??' => 'D', '??' => 'E', '??' => 'Z', '??' => 'H', '??' => '8',
            '??' => 'I', '??' => 'K', '??' => 'L', '??' => 'M', '??' => 'N', '??' => '3', '??' => 'O', '??' => 'P',
            '??' => 'R', '??' => 'S', '??' => 'T', '??' => 'Y', '??' => 'F', '??' => 'X', '??' => 'PS', '??' => 'W',
            '??' => 'A', '??' => 'E', '??' => 'I', '??' => 'O', '??' => 'Y', '??' => 'H', '??' => 'W', '??' => 'I',
            '??' => 'Y',
            '??' => 'a', '??' => 'b', '??' => 'g', '??' => 'd', '??' => 'e', '??' => 'z', '??' => 'h', '??' => '8',
            '??' => 'i', '??' => 'k', '??' => 'l', '??' => 'm', '??' => 'n', '??' => '3', '??' => 'o', '??' => 'p',
            '??' => 'r', '??' => 's', '??' => 't', '??' => 'y', '??' => 'f', '??' => 'x', '??' => 'ps', '??' => 'w',
            '??' => 'a', '??' => 'e', '??' => 'i', '??' => 'o', '??' => 'y', '??' => 'h', '??' => 'w', '??' => 's',
            '??' => 'i', '??' => 'y', '??' => 'y', '??' => 'i',
            // Turkish
            '??' => 'S', '??' => 'I', '??' => 'C', '??' => 'U', '??' => 'O', '??' => 'G',
            '??' => 's', '??' => 'i', '??' => 'c', '??' => 'u', '??' => 'o', '??' => 'g',
            // Russian
            '??' => 'A', '??' => 'B', '??' => 'V', '??' => 'G', '??' => 'D', '??' => 'E', '??' => 'Yo', '??' => 'Zh',
            '??' => 'Z', '??' => 'I', '??' => 'J', '??' => 'K', '??' => 'L', '??' => 'M', '??' => 'N', '??' => 'O',
            '??' => 'P', '??' => 'R', '??' => 'S', '??' => 'T', '??' => 'U', '??' => 'F', '??' => 'H', '??' => 'C',
            '??' => 'Ch', '??' => 'Sh', '??' => 'Sh', '??' => '', '??' => 'Y', '??' => '', '??' => 'E', '??' => 'Yu',
            '??' => 'Ya',
            '??' => 'a', '??' => 'b', '??' => 'v', '??' => 'g', '??' => 'd', '??' => 'e', '??' => 'yo', '??' => 'zh',
            '??' => 'z', '??' => 'i', '??' => 'j', '??' => 'k', '??' => 'l', '??' => 'm', '??' => 'n', '??' => 'o',
            '??' => 'p', '??' => 'r', '??' => 's', '??' => 't', '??' => 'u', '??' => 'f', '??' => 'h', '??' => 'c',
            '??' => 'ch', '??' => 'sh', '??' => 'sh', '??' => '', '??' => 'y', '??' => '', '??' => 'e', '??' => 'yu',
            '??' => 'ya',
            // Ukrainian
            '??' => 'Ye', '??' => 'I', '??' => 'Yi', '??' => 'G',
            '??' => 'ye', '??' => 'i', '??' => 'yi', '??' => 'g',
            // Czech
            '??' => 'C', '??' => 'D', '??' => 'E', '??' => 'N', '??' => 'R', '??' => 'S', '??' => 'T', '??' => 'U',
            '??' => 'Z',
            '??' => 'c', '??' => 'd', '??' => 'e', '??' => 'n', '??' => 'r', '??' => 's', '??' => 't', '??' => 'u',
            '??' => 'z',
            // Polish
            '??' => 'A', '??' => 'C', '??' => 'e', '??' => 'L', '??' => 'N', '??' => 'o', '??' => 'S', '??' => 'Z',
            '??' => 'Z',
            '??' => 'a', '??' => 'c', '??' => 'e', '??' => 'l', '??' => 'n', '??' => 'o', '??' => 's', '??' => 'z',
            '??' => 'z',
            // Latvian
            '??' => 'A', '??' => 'C', '??' => 'E', '??' => 'G', '??' => 'i', '??' => 'k', '??' => 'L', '??' => 'N',
            '??' => 'S', '??' => 'u', '??' => 'Z',
            '??' => 'a', '??' => 'c', '??' => 'e', '??' => 'g', '??' => 'i', '??' => 'k', '??' => 'l', '??' => 'n',
            '??' => 's', '??' => 'u', '??' => 'z',
            //special
            '??' => 'L', '??' => 'l',
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



