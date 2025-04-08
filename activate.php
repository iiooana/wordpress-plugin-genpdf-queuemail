<?php

function genpdf_active(){
  //Check if tables exits or not
  global $wpdb;
  $prefix = $wpdb->base_prefix.GenPDF::PREFIX_TABLE;

  //table COURSES
  $table = $prefix."_courses";
  maybe_create_table($table,"CREATE TABLE IF NOT EXISTS ".$table." (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT(CURTIME()),
    updated_at DATETIME ,
    PRIMARY KEY (id)
  )");

  //table TEMPLATES
  $table = $prefix."_templates";
  maybe_create_table($table,"CREATE TABLE IF NOT EXISTS ".$table." (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL UNIQUE,
    html TEXT,
    created_at DATETIME DEFAULT(CURTIME()),
    updated_at DATETIME,
    PRIMARY KEY (id)
  )");

  //table COURSE_TEMPLATE
  $table = $prefix."_course_template";
  maybe_create_table($table,"CREATE TABLE IF NOT EXISTS ".$table." (
    course_id INT UNSIGNED NOT NULL,
    template_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT(CURTIME()),
    updated_at DATETIME,
    PRIMARY KEY (course_id,template_id),
    FOREIGN KEY (course_id) REFERENCES ".$prefix."_courses (id),
    FOREIGN KEY (template_id) REFERENCES ".$prefix."_templates (id)
  )");
}