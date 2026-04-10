<?php
/**
 * read customers' data
 * format：id;first;last;email;university;address;city;state;country;zip;phone;sales
 */
function readCustomers($filename) {
    $customers = array();
    if (file_exists($filename)) {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            
            $customers[] = explode(';', $line);
        }
    }
    return $customers;
}

/**
 * read customers' order
 * format：order_id;customer_id;isbn;title;category
 */
function readOrders($customerID, $filename) {
    $orders = array();
    if (file_exists($filename)) {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $data = explode(',', $line);
            
            if (isset($data[1]) && trim($data[1]) == trim($customerID)) {
                $orders[] = $data;
            }
        }
    }
    return $orders;
}
?>