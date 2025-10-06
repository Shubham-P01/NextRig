<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - NextRig</title>
    <!-- Link to your main stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <main class="main-content">
        <div class="container">
            <div class="contact-grid">
                <!-- Contact Info Cards -->
                <div class="contact-info-wrapper">
                    <div class="contact-card">
                        <div class="contact-icon"><i class="fas fa-shopping-cart"></i></div>
                        <h3>Sales & Product Inquiries</h3>
                        <p>Questions about our refurbished parts, stock, or placing an order? Contact our sales team.</p>
                        <a href="mailto:sales@nextrig.com" class="contact-link">sales@nextrig.com</a>
                        <a href="tel:+919876543210" class="contact-link">+91 98765 43210</a>
                    </div>
                    <div class="contact-card">
                        <div class="contact-icon"><i class="fas fa-hand-holding-dollar"></i></div>
                        <h3>Selling Your PC Parts</h3>
                        <p>Have used components to sell? Reach out to our purchasing department for a quote.</p>
                        <a href="mailto:purchasing@nextrig.com" class="contact-link">purchasing@nextrig.com</a>
                        <a href="tel:+919876543211" class="contact-link">+91 98765 43211</a>
                    </div>
                    <div class="contact-card">
                        <div class="contact-icon"><i class="fas fa-headset"></i></div>
                        <h3>General Support</h3>
                        <p>For all other questions, including partnerships and media inquiries, contact us here.</p>
                        <a href="mailto:support@nextrig.com" class="contact-link">support@nextrig.com</a>
                    </div>
                </div>

                <!-- Address, Hours & Map Card -->
                <div class="address-card">
                    <h3>Our Office</h3>
                    <p>
                        <strong>NextRig Technologies Pvt. Ltd.</strong><br>
                        #42, Richmond Road,<br>
                        Bengaluru, Karnataka 560025<br>
                        India
                    </p>
                    <hr>
                    <h4><i class="fas fa-clock"></i> Business Hours</h4>
                    <p>
                        Monday - Saturday: 10:00 AM - 6:00 PM (IST)<br>
                        Sunday: Closed
                    </p>
                    <!-- Live Google Map Embed -->
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3888.075954512391!2d77.60423631482201!3d12.966952990858734!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bae15d6c9b8d9e1%3A0x64f62e43f5855015!2sRichmond%20Rd%2C%20Bengaluru%2C%20Karnataka!5e0!3m2!1sen!2sin!4v1672548858548!5m2!1sen!2sin" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
