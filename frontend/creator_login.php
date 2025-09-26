<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AllQueens254.com</title>
    <style>
        :root {
            --black: #000000;
            --red: #E70008;
            --cream: #F9E4AD;
            --orange: #FF9940;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--black) 0%, #222 100%);
            color: var(--cream);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        
        header {
            background: var(--black);
            color: var(--cream);
            text-align: center;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            border-bottom: 3px solid var(--red);
            width: 100%;
            max-width: 500px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: var(--orange);
            text-shadow: 1px 1px 2px var(--black);
        }
        
        .tagline {
            font-style: italic;
            color: var(--cream);
            font-size: 1rem;
        }
        
        main {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.7);
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            box-shadow: 0 0 20px rgba(231, 0, 8, 0.3);
            border-left: 1px solid var(--red);
            border-right: 1px solid var(--red);
            border-bottom: 1px solid var(--red);
        }
        
        form {
            display: grid;
            grid-gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--orange);
        }
        
        input {
            padding: 0.8rem;
            border: 2px solid var(--red);
            border-radius: 5px;
            background: var(--black);
            color: var(--cream);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255, 153, 64, 0.3);
        }
        
        .btn-group {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        button {
            background: var(--red);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        button:hover {
            background: var(--orange);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 0, 8, 0.4);
        }
        
        .home-btn {
            background: var(--black);
            border: 2px solid var(--orange);
        }
        
        .home-btn:hover {
            background: rgba(255, 153, 64, 0.2);
        }
        
        .home-btn a {
            color: var(--cream);
            text-decoration: none;
            display: block;
        }
        
        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .links a {
            color: var(--orange);
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--cream);
            font-size: 0.9rem;
            max-width: 500px;
        }
        
        footer a {
            color: var(--orange);
            text-decoration: none;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 550px) {
            header, main, footer {
                border-radius: 0;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>AllQueens254.com</h1>
        <p class="tagline">Login to your account</p>
    </header>
    
    <main>
       <form action="../backend/login.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <div class="btn-group">
                <button type="submit">Login</button>
            </div>
            
            <div class="links">
                <a href="creator_register.html">Create Account</a>
                <a href="homepage.html">← Homepage</a>

            </div>
        </form>
    </main>
    
    <footer>
        <p>© 2023 AllQueens254.com | <a href="#">Privacy Policy</a> | <a href="#">Contact Us</a></p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            
            loginForm.addEventListener('submit', function(event) {
                const phoneInput = document.getElementById('phone');
                const passwordInput = document.getElementById('password');
                
                // Simple validation
                if (!phoneInput.value.trim()) {
                    alert('Please enter your phone number');
                    event.preventDefault();
                    return;
                }
                
                if (!passwordInput.value.trim()) {
                    alert('Please enter your password');
                    event.preventDefault();
                    return;
                }
                
                
            });
        });
    </script>
    
</body>
</html>
