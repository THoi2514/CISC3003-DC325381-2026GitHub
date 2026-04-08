<?php
// CISC3003 Mid-Term Exam Paper 01 - Part 04
// This file is purposefully saved as a .php file to be rendered by XAMPP
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>dc325381 HOI KAI CHENG</title>
    
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

    <div class="container" id="container">
        <div class="form-container sign-up-container">
            <form action="#">
                <h1>Join Us</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>Use your email to sign up</span>
                <input type="text" placeholder="Full Name" required />
                <input type="email" placeholder="Email" required />
                <input type="password" placeholder="Create Password" required />
                <button type="submit">REGISTER</button>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form action="#">
                <h1>Log In</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>Use your account to sign in</span>
                <input type="email" placeholder="Email" required />
                <input type="password" placeholder="Password" required />
                <a href="#">Forgot Password?</a>
                <button type="submit">SIGN IN</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Hello , Again!</h1>
                    <img src="images/website_7376495.png" alt="Profile Icon" class="vector-image">
                    <p>Log in to stay connected with us</p>
                    <button class="ghost" id="signIn">SIGN IN</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Welcome!</h1>
                    <img src="images/unsecure_10399884.png" alt="Lock Icon" class="vector-image">
                    <p>Enter your details to start your journey</p>
                    <button class="ghost" id="signUp">SIGN UP</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>CISC3003 Web Programming: dc325381 HOI KAI CHENG 2026</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>