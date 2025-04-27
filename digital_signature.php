<?php
function genpdf_get_signature_folder(){
    return [
        "path" => get_home_path()."wp-content/signatures",
        "url" =>  get_site_url()."/wp-content/signatures",
        "subdir" => "/signatures",
        "basedir" => get_home_path()."wp-content",
        "baseurl" => get_site_url()."/wp-content"
        ];
}
add_filter('genpdf_get_signature_folder','genpdf_get_signature_folder');