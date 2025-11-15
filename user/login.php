<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Clubs Hub - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #ff5c5c 0%, #4871db 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 20px 60px 20px;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 30px;
        }

        .logo-image {
            max-width: 350px;
            width: 100%;
            height: auto;
            display: block;
        }

        .login-card {
            background: rgba(139, 116, 157, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            align-self: center;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            color: #000;
            font-size: 1.2rem;
            margin-bottom: 10px;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 18px 20px;
            font-size: 1.1rem;
            border: none;
            border-radius: 12px;
            background: #FFFFFF;
            color: #999;
        }

        input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 209, 102, 0.3);
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
            background: #A9BFF8;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: white;
            color:#4871db ;
        }

        .forgot-password {
            display: block;
            text-align: center;
            color: #FFF;
            text-decoration: underline;
            font-size: 1.1rem;
            margin-top: 20px;
            transition: opacity 0.3s ease;
        }

        .forgot-password:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .logo-image {
                max-width: 300px;
            }

            .login-card {
                padding: 35px;
            }
        }

        @media (max-width: 480px) {
            .logo-image {
                max-width: 200px;
            }

            .login-card {
                padding: 25px;
            }
 
            label {
                font-size: 1rem;
            }

            input {
                padding: 15px;
                font-size: 1rem;
            }

            .submit-btn {
                font-size: 1.1rem;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
            <img src="tools/pics/adminlogo.png" alt="UniHive logo" class="logo-image">
        <div class="login-card">
            <form id="loginForm" method="post" action="login.php">
                <div class="form-group">
                    <label for="email" style='color:white'>Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter Your University Email" required>
                </div>

                <div class="form-group">
                    <label for="password" style='color:white'>Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter Your Password" required>
                </div>

                <button type="submit" class="submit-btn">Sign In</button>

                <a href="https://adresetpw.ju.edu.jo/" class="forgot-password">Forgot password?</a>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Sign in functionality would go here!');
        });
    </script>
</body>
</html>