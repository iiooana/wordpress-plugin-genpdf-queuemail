<?php

namespace GenPDF;

class OrderEmailGenPDF
{

    private $order_id;
    private $next_time;
    private $remaining_attemps;
    private $info;
    private $created_at;
    private $updated_at;

    public function __construct(int $order_id, bool $new = true)
    {
        global $wpdb;
        $table = self::getTableName();
        if ($new === true) {
            $wpdb->insert(
                $table,
                array(
                    'order_id' => $order_id,
                    'next_time' => date('Y-m-d H:i:s',current_time('timestamp')+120),
                ),
                array(
                    '%d',
                    '%s',
                )
            );
        }
    }
    /**
     * @return table name of the model
     */
    private function getTableName()
    {
        return GenPDF::getFullPrefix() . "_orders_email";
    }
}
