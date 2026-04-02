/* add loop and other code here ... in this simple exercise we are not
   going to concern ourselves with minimizing globals, etc */

   const TAX_RATE = 0.10;
   const FREE_SHIPPING_THRESHOLD = 1000;

   let subtotal = 0;

   for (let i = 0; i < filenames.length; i++) {
       let qty = quantities[i];
       let price = prices[i];
       let total = calculateTotal(qty, price);
       subtotal += total;
       outputCartRow(filenames[i], titles[i], qty, price, total);
   }

   let tax = calculateTax(subtotal, TAX_RATE);
   let shipping = calculateShipping(subtotal, FREE_SHIPPING_THRESHOLD);
   let grandTotal = calculateGrandTotal(subtotal, tax, shipping);

   document.write('<tr class="totals">');
   document.write('<td colspan="4">Subtotal</td>');
   document.write('<td>' + outputCurrency(subtotal) + '</td>');
   document.write('</tr>');

   document.write('<tr class="totals">');
   document.write('<td colspan="4">Tax</td>');
   document.write('<td>' + outputCurrency(tax) + '</td>');
   document.write('</tr>');

   document.write('<tr class="totals">');
   document.write('<td colspan="4">Shipping</td>');
   document.write('<td>' + outputCurrency(shipping) + '</td>');
   document.write('</tr>');

   document.write('<tr class="totals focus">');
   document.write('<td colspan="4">Grand Total</td>');
   document.write('<td>' + outputCurrency(grandTotal) + '</td>');
   document.write('</tr>');