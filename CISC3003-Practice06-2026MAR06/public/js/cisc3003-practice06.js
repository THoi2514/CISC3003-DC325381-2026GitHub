/* add loop and other code here ... in this simple exercise we are not
   going to concern ourselves with minimizing globals, etc */

var subtotal = 0;

// Loop through the parallel arrays to output each cart item and calculate the running subtotal
for (var i = 0; i < filenames.length; i++) {
    outputCartRow(filenames[i], titles[i], quantities[i], prices[i]);
    subtotal += calculateTotal(quantities[i], prices[i]);
}

// Calculate the remaining values using our helper functions
var tax = calculateTax(subtotal);
var shipping = calculateShipping(subtotal);
var grandTotal = calculateGrandTotal(subtotal, tax, shipping);

// Output the final bottom rows of the table
outputTotals(subtotal, tax, shipping, grandTotal);