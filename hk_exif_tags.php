<?php
    /**
     * @package HK_Exif_Tags
     * @version 1.12
     */
    /*
     Plugin Name: HK Exif Tags
     Plugin URI: http://wordpress.org/extend/plugins/hk-exif-tags/
     Description: Adds EXIF Tags below each image
     Author: Henry Kellner
     Version: 1.12
     Author URI: http://hk.tt/
     */
    
    add_filter('the_content', 'hk_filter_the_content');
    add_filter('wp_read_image_metadata', 'hk_filter_add_exif','',3);
    
    //******************************************************************************************
    // this hook function adds also the exif tag "make" in the database of each uploaded image
    //******************************************************************************************
    function hk_filter_add_exif($meta, $file, $sourceImageType)
    {
        if ( is_callable('exif_read_data') &&
            in_array($sourceImageType, apply_filters('wp_read_image_metadata_types', array(IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM)) ) )
        {
            $exif = @exif_read_data( $file );
            
            if (!empty($exif['Make']))      $meta['make'] = $exif['Make'] ;
			
            return $meta;
        }
    }
    
    //******************************************************************************************
    // find the attachment id of a given image url
    //******************************************************************************************
    function hk_get_attachment_id( $url )
    {
        $dir = wp_upload_dir();
        
        // baseurl never has a trailing slash
        if ( false === strpos( $url, $dir['baseurl'] . '/' ) )
        {
            // URL points to a place outside of upload directory
            return false;
        }
        
        $file  = basename( $url );
        $query = array(
                       'post_type'  => 'attachment',
                       'fields'     => 'ids',
                       'meta_query' => array(
                                             array(
                                                   'value'   => $file,
                                                   'compare' => 'LIKE',
                                                   ),
                                             )
                       );
        
        $query['meta_query'][0]['key'] = '_wp_attached_file';
        
        // query attachments
        $ids = get_posts( $query );
        
        if ( ! empty( $ids ) )
        {
            foreach ( $ids as $id )
            {
                // first entry of returned array is the URL
                if ( $url === array_shift( wp_get_attachment_image_src( $id, 'full' ) ) )
                    return $id;
            }
        }
        
        $query['meta_query'][0]['key'] = '_wp_attachment_metadata';
        
        // query attachments again
        $ids = get_posts( $query );
        
        if ( empty( $ids) )
            return false;
        
        foreach ( $ids as $id )
        {
            
            $meta = wp_get_attachment_metadata( $id );
            
            foreach ( $meta['sizes'] as $size => $values )
            {
                if ( $values['file'] === $file && $url === array_shift( wp_get_attachment_image_src( $id, $size ) ) )
                    return $id;
            }
        }
        
        return false;
    }
    
    //******************************************************************************************
    // process each found <img> tag in the page, and try to add a line with the exif data
    //******************************************************************************************
    function hk_exif_tags_images_process($matches)
    {
        // if <img> tag contains "hk_noexif" than do nothing
        if(preg_match('/hk_noexif/', $matches[0], $noexif) )
            return $matches[0];

        // try to find id in class value
        if(preg_match('/wp-image-([0-9]+)/', $matches[0], $idMatches) )
            $id = $idMatches[1];
        else
        {
            // extract url of image from src property of <img> tag
            if(!preg_match('/src="([^"?]*)/', $matches[0], $urlMatches) )
                return $matches[0];
            
            // find the attachment id of this image url
            $id = hk_get_attachment_id($urlMatches[1]);
        }
        
        // read the meta data of the found attachment id out of the database
        $imgmeta = wp_get_attachment_metadata( $id );
        
        // check if there are any valid exif data
        if( !isset($imgmeta['image_meta']) || !isset($imgmeta['image_meta']['camera']) || $imgmeta['image_meta']['camera'] == '')
            return $matches[0];

        // get each exif tag in a proper format
        
        $pcamera = $imgmeta['image_meta']['camera'];
        
        // Convert the shutter speed retrieve from database to fraction
        
        if(isset($imgmeta['image_meta']['shutter_speed']))
        {
            if ((1 / $imgmeta['image_meta']['shutter_speed']) > 1)
            {
                if ((number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1)) == 1.3
                    or number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1) == 1.5
                    or number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1) == 1.6
                    or number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1) == 2.5){
                    $pshutter = "1/" . number_format((1 / $imgmeta['image_meta']['shutter_speed']), 1, '.', '') . " sec";
                }
                else
                    $pshutter = "1/" . number_format((1 / $imgmeta['image_meta']['shutter_speed']), 0, '.', '') . " sec";
            }
            else
                $pshutter = $imgmeta['image_meta']['shutter_speed'] . " sec";
        }
        else
            $pshutter = "";
        
        if( isset($imgmeta['image_meta']['make']) )         $pmake = $imgmeta['image_meta']['make'];
        else                                                $pmake = "";
        
        if( isset($imgmeta['image_meta']['focal_length']) ) $pfocal_length = $imgmeta['image_meta']['focal_length'] . "mm";
        else                                                $pfocal_length = "";
        
        if( isset($imgmeta['image_meta']['aperture']) )     $paperature = "f/" . $imgmeta['image_meta']['aperture'];
        else                                                $paperature = "";
        
        if( isset($imgmeta['image_meta']['iso']) )          $piso = "ISO" . $imgmeta['image_meta']['iso'];
        else                                                $piso = "";

        // eliminate long make names like "NIKON CORPORATION"
        if( strlen($pmake)>12 && strcasecmp(substr($pmake, strlen($pmake)-12), " CORPORATION")==0 )
            $pmake = substr($pmake, 0, strlen($pmake)-12);
        
        if( strlen($pmake)==20 && strcasecmp($pmake, "PENTAX RICOH IMAGING")==0 )
            $pmake = "RICOH";
        
        // eliminate duplicate brand names in make and model field, like "Canon Canon EOS 5D"
        if( $pmake!="" &&  strcasecmp( substr($pcamera, 0, strlen($pmake)), $pmake)==0 )
            $pcamera = substr($pcamera, strlen($pmake)+1);
        
        // prevent code injections
        $pmake = htmlspecialchars($pmake, ENT_QUOTES);
        $pcamera = htmlspecialchars($pcamera, ENT_QUOTES);
        $pfocal_length = htmlspecialchars($pfocal_length, ENT_QUOTES);
        $paperature = htmlspecialchars($paperature, ENT_QUOTES);
        $pshutter = htmlspecialchars($pshutter, ENT_QUOTES);
        $piso = htmlspecialchars($piso, ENT_QUOTES);
        
        // ****************************************************************************************************************************
        // **
        // ** the follwing code defines the layout of the inserted line
        // **
        // ****************************************************************************************************************************
        
        $result = $matches[0] . '<span style="display:block; color:#888; font-size:small; font-weight:normal;">';
        $result = $result . $pmake . ' ' . $pcamera . ' (' . $pfocal_length . ', ' . $paperature . ', ' . $pshutter . ', ' . $piso . ')';
        $result = $result . '<br></span>';
        
        // ****************************************************************************************************************************
        
        return $result;
    }
    
    //******************************************************************************************
    // search for all occurrences of "<img...</p>" or "<img...</a>" and process them
    //******************************************************************************************
    function hk_filter_the_content($content)
    {
        return preg_replace_callback('/<img(?:.*?)(?:(?:<\/p>)|(?:<\/a>))/', 'hk_exif_tags_images_process', $content);
    } 
    
    ?>