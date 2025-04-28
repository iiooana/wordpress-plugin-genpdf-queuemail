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

    public function __construct(int $order_id)
    {
        global $wpdb;
        $table = OrderEmailGenPDF::getTableName();

        $wpdb->insert(
            $table,
            array(
                'order_id' => $order_id,
                'next_time' => date('Y-m-d H:i:s', current_time('timestamp') + 120),
            ),
            array(
                '%d',
                '%s',
            )
        );
    }
    /**
     * @return table name of the model
     */
    public static function getTableName()
    {
        return GenPDF::getFullPrefix() . "_orders_email";
    }

    public static function getOrdersToSendEmails()
    {
        global $wpdb;
        $table = OrderEmailGenPDF::getTableName();
        $query = $wpdb->prepare("SELECT * FROM {$table} where remaining_attemps > 0 and next_time <= %s", [date('Y-m-d H:i:s', current_time('timestamp'))]);
        return $wpdb->get_results($query, ARRAY_A);
    }

    public static function UpdateOrderEmail(int $order_id, array $add_info, bool $is_ok = false)
    {
        global $wpdb;
        $table = OrderEmailGenPDF::getTableName();
        $query = $wpdb->prepare("SELECT * FROM {$table} where order_id= %d", [$order_id]);
        $row = $wpdb->get_row($query, ARRAY_A);
        if (empty($row) && empty($row['order_id'])) {
            $message = var_export(["message" => "[ERROR-GENPDF004]The order_id does not exists in {$table}.", "order_id" => $order_id], true);
            error_log($message);
        }
        $date_time = date('Y-m-d H:i:s', current_time('timestamp'));
        $remaining_attemps = intval($row['remaining_attemps']);
        $remaining_attemps = $remaining_attemps > 0 ? $remaining_attemps - 1 : 0;
        //success
        if($is_ok === true){
            $remaining_attemps = 0;
        }
        $info = json_decode($row['info'], true);
        $info[$date_time] = $add_info;
       
        $is_updated = $wpdb->update(
            $table,
            array(
                'remaining_attemps' => $remaining_attemps,
                'info' => json_encode($info),
                'next_time' => date('Y-m-d H:i:s', intval(current_time('timestamp')) +60 ),
                'updated_at' => $date_time,
            ),
            array(
                'order_id' => $row['order_id'],
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
            ),
            array(
                '%d',
            )
        );
        if($is_updated === false){
            $message = var_export(["message" => "[ERROR-GENPDF005]-EMERGENCY CANNOT UPDATE THE TABLE {$table}", "order_id" => $order_id,"add_info" => $add_info], true);
            error_log($message);
        }
    }
}
