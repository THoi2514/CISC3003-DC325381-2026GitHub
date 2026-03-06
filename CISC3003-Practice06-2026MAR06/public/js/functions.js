/* define functions here */

// Calculates the total amount for a single item
function calculateTotal(quantity, price) {
    return quantity * price;
}

// Outputs the HTML markup for a single cart item row
function outputCartRow(filename, title, quantity, price) {
    var amount = calculateTotal(quantity, price);
    document.write('<tr>');
    // Assumes images are stored in a relative "images/" folder based on standard practices
    document.write('  <td><img src="images/' + filename + '" alt="' + title + '"></td>');
    document.write('  <td>' + title + '</td>');
    document.write('  <td>' + quantity + '</td>');
    document.write('  <td>$' + price.toFixed(2) + '</td>');
    document.write('  <td>$' + amount.toFixed(2) + '</td>');
    document.write('</tr>');
}

// Calculates the 10% tax based on the subtotal
function calculateTax(subtotal) {
    return subtotal * 0.10; 
}

// Returns the flat shipping rate
function calculateShipping(subtotal) {
    return 40.00; 
}

// Calculates the final grand total
function calculateGrandTotal(subtotal, tax, shipping) {
    return subtotal + tax + shipping;
}

// Outputs the HTML markup for the four totals rows at the bottom
function outputTotals(subtotal, tax, shipping, grandTotal) {
    document.write('<tr class="totals">');
    document.write('  <td colspan="4">Subtotal</td>');
    document.write('  <td>$' + subtotal.toFixed(2) + '</td>');
    document.write('</tr>');
    
    document.write('<tr class="totals">');
    document.write('  <td colspan="4">Tax</td>');
    document.write('  <td>$' + tax.toFixed(2) + '</td>');
    document.write('</tr>');
    
    document.write('<tr class="totals">');
    document.write('  <td colspan="4">Shipping</td>');
    document.write('  <td>$' + shipping.toFixed(2) + '</td>');
    document.write('</tr>');
    
    document.write('<tr class="totals focus">');
    document.write('  <td colspan="4">Grand Total</td>');
    document.write('  <td>$' + grandTotal.toFixed(2) + '</td>');
    document.write('</tr>');
}