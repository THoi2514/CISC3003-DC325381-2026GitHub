<?php
  include 'data.inc.php';
  include 'functions.inc.php';
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8">
    <title>CISC3003 Practice 09</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://code.getmdl.io/1.3.0/material.indigo-pink.min.css">
    <script defer src="https://code.getmdl.io/1.3.0/material.min.js"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    
<div class="mdl-layout mdl-js-layout mdl-layout--fixed-drawer mdl-layout--fixed-header">
            
  <?php include 'header.inc.php'; ?>
  <?php include 'left.inc.php'; ?>
  
  <main class="mdl-layout__content mdl-color--grey-50">
    <div class="page-content">
      <header>
        <h4>Order Summaries</h4>
        <p>Examine your customer orders</p>
      </header>   
      
      <section class="mdl-grid">
          <div class="mdl-cell mdl-cell--3-col">
            <div class="mdl-card mdl-shadow--2dp card-lesson">
              <div class="mdl-card__title mdl-color--orange">
                <h2 class="mdl-card__title-text">My Orders</h2>
              </div>
              <div class="mdl-card__supporting-text">            
                  <ul class="mdl-list">
                      <?php
                        // Requirement: Use a loop to output the list of items in My Orders
                        for ($i = 500; $i <= 540; $i+=10) {
                            echo "<li><a href=\"#\">Order #{$i}</a></li>";
                        }
                      ?>               
                  </ul>   
              </div>    
            </div> 
          </div>

          <div class="mdl-cell mdl-cell--9-col">
            <div class="mdl-card mdl-shadow--2dp" style="width: 100%;">
              <div class="mdl-card__title mdl-color--deep-purple mdl-color-text--white">
                <h2 class="mdl-card__title-text">Selected Order: #520</h2>
              </div>
              <div class="mdl-card__supporting-text">
                  <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp" style="width: 100%;">
                   <caption>Customer: <strong>Mount Royal University</strong></caption>
                    <thead>
                      <tr>
                        <th>Cover</th>
                        <th class="mdl-data-table__cell--non-numeric">Title</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        // Output rows using the function
                        outputOrderRow($file1, $title1, $quantity1, $price1);
                        outputOrderRow($file2, $title2, $quantity2, $price2);
                        outputOrderRow($file3, $title3, $quantity3, $price3);
                        outputOrderRow($file4, $title4, $quantity4, $price4);

                        // Calculations
                        $subtotal = ($quantity1 * $price1) + ($quantity2 * $price2) + ($quantity3 * $price3) + ($quantity4 * $price4);
                        
                        // The shipping value will be $200 unless the subtotal is above $10,000, in which case it will be $100.
                        $shipping = ($subtotal > 10000) ? 100 : 200; 
                        
                        $grandTotal = $subtotal + $shipping;
                      ?>
                    </tbody>
                    <tfoot>
                        <tr class="totals">
                            <td colspan="4">Subtotal</td>
                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                        <tr class="totals">
                            <td colspan="4">Shipping</td>
                            <td>$<?php echo number_format($shipping, 2); ?></td>
                        </tr> 
                        <tr class="grandtotals">
                            <td colspan="4">Grand Total</td>
                            <td>$<?php echo number_format($grandTotal, 2); ?></td>
                        </tr>                            
                    </tfoot>          
                  </table>
              </div>
            </div> 
          </div>
      </section>
    </div>
  </main>
</div>
</body>
</html>