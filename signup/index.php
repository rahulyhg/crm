<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge" /><![endif]-->
    <title>Samex LLC - CRM Sign Up</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Place favicon.ico in the root directory -->
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <link rel="icon" href="favicon.ico">

    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,700" rel="stylesheet">

    <!-- Unminified version of CSS. Remove these lines if you use the minified version -->
    <link rel="stylesheet" href="css/bootstrap.css" />
    <link rel="stylesheet" href="css/font-awesome.css" />
    <link rel="stylesheet" href="css/swiper.css" />
    <link rel="stylesheet" href="css/aos.css" />
    <link rel="stylesheet" href="css/style.css" />

    <!-- Uncomment the following to use the minified version of CSS. If you do this, please remove the above references -->
    <!-- <link rel="stylesheet" href="css/kairos.min.css" />
    <link rel="stylesheet" href="css/vendor.min.css" /> -->
</head>
<body data-spy="scroll" data-target=".navbar-collapse" data-offset="90">
<!--[if lt IE 8]>
<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
<![endif]-->



<main>
    
    <header class="gradient page header overlap-next color-1">
    <div class="container">

            <h1 class="font-lg color-1">Start your 14-day trial of Samex CRM</h1>
            <p class="lead">Complete the form below to create your account and begin managing your leads & contacts.</p>

    </div>
</header>


    <section class="section sign-up">
    <div class="container shadow py-0 mb-7 bg-1">
        <div class="row">
            <div class="col-12 col-md-8">
                <div class="py-5 px-md-5">
                    <h4 class="mb-4 color-5 font-md">We will need...</h4>

<form action="signup.php" method="post" class="form form-signup" name="form-signup">
	<input type="hidden" name="Signup[tier]" id="signup_tier" value="<?php if(isset($_GET['tier'])) { echo $_GET['tier']; } else { echo 'startup'; } ?>">
	<div class="form-group">
        <label for="signup_username">Full Name</label>
        <input type="text" name="Signup[fullname]" id="signup_fullname" class="form-control bg-1" <?php if(isset($_GET['f'])) { echo 'value="'. $_GET['f'].'"'; } ?> placeholder="Enter your first and last name" required>
    </div>
	
	<div class="form-group">
        <label for="signup_username">Company Name</label>
		<?php if(isset($_GET['cex'])) { ?><small id="emailHelp" class="form-text" style="color:red;">The company name you entered already exists, please choose another</small> <?php } ?>
        <input type="text" name="Signup[companyname]" id="signup_companyname" class="form-control bg-1" <?php if(isset($_GET['c'])) { echo 'value="'.$_GET['c'].'"'; } ?> placeholder="Enter your company name" required>
		<small id="emailHelp" class="form-text text-muted">This will also be your CRM account name.</small>
    </div>
	
    <div class="form-group">
        <label for="signup_email">Email address</label>
		<?php if(isset($_GET['eex'])) { ?><small id="emailHelp" class="form-text" style="color:red;">The email address you entered already exists, please choose another</small> <?php } ?>
        <input type="email" name="Signup[email]" id="signup_email" class="form-control bg-1" <?php if(isset($_GET['e'])) { echo 'value="'.$_GET['e'].'"'; } ?> placeholder="Valid Email" required>
        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
    </div>
	
    <div class="form-group">
		<?php if(isset($_GET['uex'])) { ?><small id="emailHelp" class="form-text" style="color:red;">The username you entered already exists, please choose another</small> <?php } ?>
        <label for="signup_username">Username</label>
        <input type="text" name="Signup[username]" id="signup_username" class="form-control bg-1" <?php if(isset($_GET['u'])) { echo 'value="'.$_GET['u'].'"'; } ?> placeholder="Enter your desired user name" required>
    </div>

    <div class="form-group">
        <label for="signup_password">Password</label>
        <input type="password" name="Signup[password]" id="signup_password" class="form-control bg-1" placeholder="Password" required>
    </div>

    <div class="form-group">
        <label for="signup_password2">Password</label>
        <input type="password" name="Signup[password2]" id="signup_password2" class="form-control bg-1" placeholder="Repeat password" required>
    </div>

    <div class="form-group">
        <button id="signup-submit" data-loading-text="Signing up..." name="submit" type="submit" class="btn btn-primary px-5 py-3">
            Create account
        </button>

        <p class="small">
            By clicking this button you agree our <a href="/privacy-policy" target="_top" class="dotted">Privacy Policy</a> &nbsp;&amp;&nbsp; <a href="/terms-of-service" target="_top" class="dotted">Terms Of Service</a>
        </p>
    </div>
</form>



                </div>
            </div>
            <div class="col-12 col-md-4 overlay overlay-5 alpha-9 image-background cover sign-up-info d-flex">
                <div class="d-flex flex-column content py-5 px-md-5">
                    <h4 class="font-md color-1">Sign up</h4>
					<p class="color-1 alpha-5">Sales Automation <br/><br/>Marketing Automation<br/><br/>Calendar & Productivity Tools</p>
                    <p class="color-1 alpha-5">Experience the full-featured CRM for 14 days with no credit card required!</p>
                </div>
            </div>
        </div>
    </div>
</section>


</main>

<!-- Individuals JS libraries. Remove these lines if you use the minified version -->
<script src="js/jquery.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.easing.min.js"></script>
<script src="js/swiper.jquery.min.js"></script>
<script src="js/aos.min.js"></script>
<script src="js/jquery.validate.min.js"></script>
<script src="js/pace.min.js"></script>
<script src="js/modernizr-2.8.3.min.js"></script>
<!-- <script src="js/forms.min.js"></script> -->
<script src="js/main.js"></script>

<!-- Uncomment the following to use the minified version of JS. If you do this, please remove the above references -->
<!-- <script src="js/lib.min.js"></script>
<script src="js/kairos.min.js"></script> -->

</body>
</html>
