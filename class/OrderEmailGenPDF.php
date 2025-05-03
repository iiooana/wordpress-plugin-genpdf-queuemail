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

    /**
     * Add the order on queue email
     */
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

    /**
     * REMEMBER TO USE START TRANSACTION - COMIT
     * @return array associative with all orders that are into the queue email
     */
    public static function getOrderToSendEmail()
    {
        global $wpdb;
        $table = OrderEmailGenPDF::getTableName();
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE remaining_attemps > 0 AND next_time <= %s ORDER BY next_time ASC LIMIT 1 FOR UPDATE", [date('Y-m-d H:i:s', current_time('timestamp'))]);
        return $wpdb->get_row($query, ARRAY_A);
    }

    /**
     * update queue email status of the order_di
     */
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
    /**
     * update the has_sent_email_admin column of the order
     */
    public static function setHasSentEmailAdmin(int $order_id){
        global $wpdb;
        $table = OrderEmailGenPDF::getTableName();
        $date_time = date('Y-m-d H:i:s', current_time('timestamp'));
        $is_updated = $wpdb->update(
            $table,
            array(
                'has_sent_email_admin' => 1,
                'updated_at' => $date_time,
            ),
            array(
                'order_id' => $order_id,
            ),
            array(
                '%d',
                '%s',
            ),
            array(
                '%d',
            )
        );
        if($is_updated === false){
            $message = var_export(["message" => "[ERROR-GENPDF006]-EMERGENCY CANNOT UPDATE THE TABLE {$table} on has_sent_email_admin", "order_id" => $order_id], true);
            error_log($message);
        }
    }
    /**
     * @return array of order's status that are acceptable 
     */
    public static function getListAcceptsStatus(){
        return ['wc-processing','processing','wc-completed','completed'];
    }

    /**
     * Add queue email for order from wp_user
     * @return bool if the data is updated
     */
    public static function addEmailQueueUser(int $order_id, array $add_info ){
        global $wpdb;
        $table = OrderEmailGenPDF::getTableName();
        $query = $wpdb->prepare("SELECT * FROM {$table} where order_id= %d", [$order_id]);
        $row = $wpdb->get_row($query, ARRAY_A);
        $info = json_decode($row['info'], true);
        $date_time = date('Y-m-d H:i:s', current_time('timestamp'));
        $info[$date_time] = $add_info;
       return $wpdb->update(
            $table,
            array(
                'remaining_attemps' => 1,
                'info' => json_encode($info),
                'next_time' => date('Y-m-d H:i:s', intval(current_time('timestamp')) + 120),
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
    }
}
