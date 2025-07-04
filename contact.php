<?php
include 'connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $query = "INSERT INTO contact_messages (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')";
    if (mysqli_query($conn, $query)) {
        echo "<p style='color: green; text-align: center;'>Message sent successfully!</p>";
    } else {
        echo "<p style='color: red; text-align: center;'>Error sending message.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Contact us</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.19.1/css/mdb.min.css" rel="stylesheet">
    <link rel='stylesheet' id='wsl-widget-css' href='https://mdbcdn.b-cdn.net/wp-content/plugins/wordpress-social-login/assets/css/style.css?ver=5.6.2' type='text/css' media='all' />
    <link rel='stylesheet' id='compiled.css-css' href='https://mdbcdn.b-cdn.net/wp-content/themes/mdbootstrap4/css/compiled-4.19.2.min.css?ver=4.19.2' type='text/css' media='all' />
</head>
<body>

    <div class="contactContainer" style="background: url(Images/WhatsApp\ Image\ 2023-04-11\ at\ 10.11.50\ PM.jpeg); height: 100vh; display: flex; align-items: center;">
        <div class="container" style="background-color: black; max-width: 880px;">
            <section class="md-4">
                <h2 class="h1-responsive font-weight-bold text-center my-5" style="color: white; letter-spacing: 2px;">
                    <q> Contact us </q>
                </h2>
                <p class="text-center w-responsive mx-auto mb-5" style="color:#757575; font-weight: bold;">
                    Do you have any questions? Please do not hesitate to contact us directly. Our team will come back to you within a matter of hours to help you.
                </p>

                <div class="row">
                    <div class="col-md-9 mb-md-0 mb-5">
                        <form id="contactform" method="POST" action="contact.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="md-form mb-0">
                                        <input type="text" name="name" class="form-control">
                                        <label for="name" style="font-weight: bold; color: white;">Your Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="md-form mb-0">
                                        <input type="text" name="email" class="form-control">
                                        <label for="email" style="font-weight: bold; color: white;">Your Email</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="md-form mb-0">
                                        <input type="text" name="subject" class="form-control">
                                        <label for="subject" style="font-weight: bold; color: white;">Subject</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="md-form">
                                        <textarea type="text" name="message" rows="3" class="form-control md-textarea"></textarea>
                                        <label for="message" style="font-weight: bold; color: white;">Your Message</label>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center text-md-left">
                                <button class="btn btn-primary" type="submit" style="background-color: #f35938 !important; padding: 9px 99px;border-radius: 20px;">Send</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-3 text-center text-primary">
                        <ul class="list-unstyled mb-0">
                            <li>
                                <i class="fas fa-map-marker-alt fa-2x" style="color: #f35938;"></i>
                                <p class="text-dark" style="color:#757575 !important; font-weight: bold;">Toronto, Ku 3845, PK</p>
                            </li>
                            <li>
                                <i class="fas fa-phone mt-4 fa-2x" style="color: #f35938;"></i>
                                <p class="text-dark" style="color:#757575 !important; font-weight: bold;">+ 111 1111 11 1111</p>
                            </li>
                            <li>
                                <i class="fas fa-envelope mt-4 fa-2x" style="color: #f35938;"></i>
                                <p class="text-dark" style="color:#757575 !important; font-weight: bold;">
                                <a href="/cdn-cgi/l/email-protection" class="__cf_email__" style="color:#757575 !important; font-weight: bold;">mail</a></p>
                            </li>