<?php
include 'includes/book-utilities.inc.php';

// 1. read all customers' data
$allCustomers = readCustomers("data/customers.txt");

// 2. read the specific customers
$selectedCustomerID = isset($_GET['id']) ? $_GET['id'] : null;
$selectedCustomer = null;
$customerOrders = array();

if ($selectedCustomerID) {
   
    foreach ($allCustomers as $cust) {
        if ($cust[0] == $selectedCustomerID) {
            $selectedCustomer = $cust;
            break;
        }
    }
    
    $customerOrders = readOrders($selectedCustomerID, "data/orders.txt");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>CISC3003 Suggested Exercise 10</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://code.getmdl.io/1.1.3/material.blue_grey-orange.min.css">
    <link rel="stylesheet" href="css/demo-styles.css">
    <link rel="stylesheet" href="css/material.min.css">
    <link rel="stylesheet" href="css/styles.css">
    
    <script src="https://code.jquery.com/jquery-1.7.2.min.js" ></script>
    <script src="https://code.getmdl.io/1.1.3/material.min.js"></script>
    <script src="js/jquery.sparkline.2.1.2.js"></script>
    <script src="js/material.min.js"></script>
    <script type="text/javascript">
        $(function() {
           // graph
            $('.inlinesparkline').sparkline('html', {type: 'bar', barColor: 'orange'});
        });
    </script>
</head>

<body>
<div class="mdl-layout mdl-js-layout mdl-layout--fixed-drawer mdl-layout--fixed-header">

    <?php include 'includes/header.inc.php'; ?>
    <?php include 'includes/left-nav.inc.php'; ?>
    
    
    <main class="mdl-layout__content mdl-color--grey-50">
        <section class="page-content">
            <div class="mdl-grid">

              <div class="mdl-cell mdl-cell--7-col card-lesson mdl-card mdl-shadow--2dp">
                <div class="mdl-card__title mdl-color--orange">
                  <h2 class="mdl-card__title-text">Customers</h2>
                </div>
                <div class="mdl-card__supporting-text">
                    <table class="mdl-data-table mdl-shadow--2dp">
                      <thead>
                        <tr>
                          <th class="mdl-data-table__cell--non-numeric">Name</th>
                          <th class="mdl-data-table__cell--non-numeric">University</th>
                          <th class="mdl-data-table__cell--non-numeric">City</th>
                          <th>Sales</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($allCustomers as $cust): ?>
                        <tr>
                          <td class="mdl-data-table__cell--non-numeric">
                            <a href="cisc3003-sugex10-after.php?id=<?php echo $cust[0]; ?>">
                                <?php echo $cust[1] . " " . $cust[2]; ?>
                            </a>
                          </td>
                          <td class="mdl-data-table__cell--non-numeric"><?php echo $cust[4]; ?></td>
                          <td class="mdl-data-table__cell--non-numeric"><?php echo $cust[6]; ?></td>
                          <td><span class="inlinesparkline"><?php echo $cust[11]; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                </div>
              </div>

            <div class="mdl-grid mdl-cell--5-col">
    
                  <div class="mdl-cell mdl-cell--12-col card-lesson mdl-card mdl-shadow--2dp">
                    <div class="mdl-card__title mdl-color--deep-purple mdl-color-text--white">
                      <h2 class="mdl-card__title-text">Customer Details</h2>
                    </div>
                    <div class="mdl-card__supporting-text">
                        <?php if ($selectedCustomer): ?>
                            <h4><?php echo $selectedCustomer[1] . " " . $selectedCustomer[2]; ?></h4>
                            <p><?php echo $selectedCustomer[5]; ?><br>
                               <?php echo $selectedCustomer[6] . ", " . $selectedCustomer[7]; ?><br>
                               <?php echo $selectedCustomer[8] . " " . $selectedCustomer[9]; ?><br>
                               <?php echo $selectedCustomer[10]; ?>
                            </p>
                        <?php else: ?>
                            <p>Select a customer to see details.</p>
                        <?php endif; ?>
                    </div>    
                  </div>

                  <div class="mdl-cell mdl-cell--12-col card-lesson mdl-card mdl-shadow--2dp">
                    <div class="mdl-card__title mdl-color--deep-purple mdl-color-text--white">
                      <h2 class="mdl-card__title-text">Order Details</h2>
                    </div>
                    <div class="mdl-card__supporting-text">       
                        <?php if ($selectedCustomerID): ?>
                            <?php if (count($customerOrders) > 0): ?>
                                <table class="mdl-data-table mdl-shadow--2dp">
                                  <thead>
                                    <tr>
                                      <th class="mdl-data-table__cell--non-numeric">Cover</th>
                                      <th class="mdl-data-table__cell--non-numeric">ISBN</th>
                                      <th class="mdl-data-table__cell--non-numeric">Title</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($customerOrders as $order): ?>
                                    <tr>
                                      <td class="mdl-data-table__cell--non-numeric">
                                          <img src="images/tinysquare/<?php echo $order[2]; ?>.jpg" alt="cover">
                                      </td>
                                      <td class="mdl-data-table__cell--non-numeric"><?php echo $order[2]; ?></td>
                                      <td class="mdl-data-table__cell--non-numeric"><?php echo $order[3]; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                  </tbody>
                                </table>
                            <?php else: ?>
                                <p class="mdl-color-text--red">No order information for the requested customer.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Select a customer to see orders.</p>
                        <?php endif; ?>
                    </div>    
                   </div>

               </div>   
            </div>
        </section>
    </main>    
</div>
</body>
</html>