<?php
// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve the data using the $_POST superglobal. 
    // Note: The keys inside $_POST[...] must match the 'name' attributes of your HTML inputs.
    $title = $_POST['title'] ?? ''; 
    $description = $_POST['description'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $subject = $_POST['subject'] ?? '';

    // Display the results as requested by the assignment (matching Figure 04)
    echo "<h2>Form Data Echo</h2>";
    echo "<p><strong>Title:</strong> " . htmlspecialchars($title) . "</p>";
    echo "<p><strong>Description:</strong> " . htmlspecialchars($description) . "</p>";
    echo "<p><strong>Genre:</strong> " . htmlspecialchars($genre) . "</p>";
    echo "<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>";
} else {
    echo "No form data submitted.";
}
?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="utf-8">
    <title>CISC3003 Suggested Exercise 09</title>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="js/misc.js"></script>
    <link rel="stylesheet" href="css/reset.css" />
    <link rel="stylesheet" href="css/styles.css" />
</head>
<body>
<?php include 'header.inc.php'; ?>
    
<main>
    <section class="results">
    
    <table>
      <caption class="results__caption">Art Work Saved</caption>
      <tr>
        <td class="results__label">Title</td>    
        <td class="results__value"></td> 
      </tr>
      <tr>
        <td class="results__label">Description</td>    
        <td class="results__value"></td> 
      </tr>
      <tr>
        <td class="results__label">Genre</td>    
        <td class="results__value"></td> 
      </tr>
      <tr>
        <td class="results__label">Subject</td>    
        <td class="results__value"></td> 
      </tr>
      <tr>
        <td class="results__label">Medium</td>    
        <td class="results__value"></td> 
      </tr>   
      <tr>
        <td class="results__label">Year</td>    
        <td class="results__value"></td> 
      </tr>  
      <tr>
        <td class="results__label">Museum</td>    
        <td class="results__value"></td> 
      </tr>          
    </table>
    
    </section>
</main>       
</body>
</html>
