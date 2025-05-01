<?php
use GenPDF\GenPDF;
function genpdf_active(){
  //Check if tables exits or not
  global $wpdb;
  $prefix = GenPDF::getFullPrefix();

  //table TEMPLATES
  //TODO BLOCK NO UPDATE-NO DELETE
  $table = $prefix."_templates";
  maybe_create_table($table,"CREATE TABLE IF NOT EXISTS {$table} (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL UNIQUE,
    html LONGTEXT,
    created_at DATETIME DEFAULT(CURTIME())
    PRIMARY KEY (id)
  )");

  //table ORDERS_TEMPLATE
  $table = $prefix."_orders_template";
  maybe_create_table($table,"CREATE TABLE IF NOT EXISTS {$table} (
    order_id BIGINT UNSIGNED NOT NULL,
    template_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT(CURTIME()),
    updated_at DATETIME,
    PRIMARY KEY(order_id,template_id),
    FOREIGN KEY (order_id) REFERENCES {$wpdb->base_prefix}wc_orders (id),
    FOREIGN KEY (template_id) REFERENCES {$prefix}_templates (id)
  )");

  //tabel ORDERS_EMAIL
  $table = $prefix."_orders_email";
  maybe_create_table($table, "CREATE TABLE IF NOT EXISTS {$table} (
    order_id BIGINT UNSIGNED NOT NULL UNIQUE,
    next_time DATETIME DEFAULT(CURTIME()) NOT NULL,
    remaining_attemps INT DEFAULT(5) NOT NULL,
    info JSON,
    has_sent_email_admin bool DEFAULT(false),
    created_at DATETIME DEFAULT(CURTIME()),
    updated_at DATETIME DEFAULT(CURTIME()),
    PRIMARY KEY(order_id),
    FOREIGN KEY (order_id) REFERENCES {$wpdb->base_prefix}wc_orders (id)
  )");
  //endregion

  //region options
  add_option('_genpdf_logo_pdf','/wp-content/uploads/2025/04/img_pdf.png');
  add_option('_genpdf_id_current_template_pdf',1);
  add_option('_genpdf_id_admin_template_email','');
  add_option('_genpdf_id_customer_template_email','');
  add_option('_genpdf_email_cc','');
  //endregion


  //region create folder for signature
  $dir_path = get_home_path()."wp-content/signatures";
  if(is_dir($dir_path) === false){
    if( mkdir($dir_path) === false) {
        error_log("The plugin wordpress-plugin-genpdf cannot create the folder wp-content/signatures. [genpdf001]");
    }
    
  } 
  //endregion

}