<?php

namespace WooPoints;

class Points {
    
    private $user_id;
    private $expiration;
    private $current_points;

    public function __construct($user_id, $expiration) {
        $this->user_id = $user_id;
        $this->expiration = $expiration;
        $this->update_current_points();
    }
    
    public function update_current_points() {
        $this->current_points = $this->load_current_points();
    }
    
    public function get_current_points() {
        return apply_filters('wc_points_get_current_user_points', $this->current_points, $this);
    }
    
    private function load_current_points() {
        global $wpdb;
        
        $result = $wpdb->get_results($wpdb->prepare("SELECT IFNULL(SUM(points), 0) AS points FROM " .
                $wpdb->prefix . "points_transaction WHERE user_id = %d", $this->user_id));
        
        return $result[0]->points;
    }
    
    public function insert_transaction($points, $codeword = '', $order_id = 0, $description = '') {
        global $wpdb;
        if ($points == 0) {
            return false;
        }
        if (empty($codeword)) {
            $codeword = $points > 0 ? 'credit' : 'debit';
        }
        $data = [
            'user_id' => $this->user_id,
            'entry' => current_time('mysql'),
            'order_id' => $order_id,
            'points' => $points,
            'current_points' => $this->get_current_points() + $points,
            'codeword' => $codeword,
            'inserted_by' => get_current_user_id(),
            'description' => $description
        ];
        if ($points > 0 && $this->expiration > 0) {
            $data['expired'] = date('Y-m-d H:i:s', strtotime('+' . $this->expiration . ' days'));
        }
        $insert = $wpdb->insert($wpdb->prefix . 'points_transaction', apply_filters('wc_points_transaction_data', $data, $this));
        if (!$insert) {
            throw new \Exception($wpdb->last_error);
        }
        $this->load_current_points();
        do_action('wc_points_after_transaction', $wpdb->insert_id, $data, $this);
        return $wpdb->insert_id;
    }
    
    public function extract($limit = 10, $page = 1) {
        global $wpdb, $wc_points;
        $offset = ($page - 1) * $limit;
        $total = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) AS total FROM {$wpdb->prefix}points_transaction WHERE user_id = %d", 
                $this->user_id)
        );
        $transactions = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}points_transaction WHERE user_id = %d "
            . "ORDER BY id DESC LIMIT %d, %d",
            $this->user_id, $offset, $limit)
        );
        $format_date = get_option('date_format') . ' ' . get_option('time_format');
        foreach ($transactions as $key => $transaction) {
            $transactions[$key]->entryFormated = date($format_date, strtotime($transaction->entry));
            $transactions[$key]->description = $transaction->description . ' '
                    . ($transaction->order_id ? '- Order: ' . $transaction->order_id : '');
            $transactions[$key]->pointsFormated = $wc_points->number_format($transaction->points);
            if ($transaction->expired !== '0000-00-00 00:00:00') {
                $transactions[$key]->expiredFormated = date($format_date, strtotime($transaction->expired));
            }
        }
        $data = [
            'total' => $total,
            'data' => $transactions
        ];
        return apply_filters('wc_points_extract', $data, $limit, $page, $this);
    }
    
}