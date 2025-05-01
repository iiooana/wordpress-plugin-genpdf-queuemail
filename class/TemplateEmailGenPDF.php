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
    /**
     * @return html for the email
     */
    public function getHtml(){
        return "<html><head></head><body>".nl2br($this->post_content)."</body></html>";
    }
    /**
     * @return posts with post_type=genpdf_template
     */
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
