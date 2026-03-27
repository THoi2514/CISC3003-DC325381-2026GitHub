<?php
function outputOrderRow($file, $title, $quantity, $price) {
    $amount = $quantity * $price;
    echo "<tr>";
    echo "<td><img src=\"images/books/tinysquare/{$file}\" alt=\"book cover\"></td>";
    echo "<td class=\"mdl-data-table__cell--non-numeric\">{$title}</td>";
    echo "<td>{$quantity}</td>";
    echo "<td>$" . number_format($price, 2) . "</td>";
    echo "<td>$" . number_format($amount, 2) . "</td>";
    echo "</tr>";
}
?>