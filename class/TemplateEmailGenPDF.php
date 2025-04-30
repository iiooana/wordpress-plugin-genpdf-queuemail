<?php

namespace GenPDF;

class TemplateEmailGenPDF
{
    protected $post_content = null;
    public function __construct(int $post_id)
    {
        $post = get_post($post_id);
        if(!empty($post) && !empty($post->post_content)){
            $this->post_content = $post->post_content;
        }
    }
    public function getHtml(){
        return "<html><head></head><body>$this->post_content</body></html>";
    }
    public static function getList()
    {
        return  get_posts([
            "numberposts" => -1,
            "post_type" => "genpdf_template",
            "orderby" => "title",
            "order" => "ASC",
        ]);
    }
}
