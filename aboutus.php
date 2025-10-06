<?php
session_start();
// NOTE: Require connection is included for consistency with other pages.
require 'connection.php'; 
$conn = $pdo; 

// --- Names for Founder Section (FINAL UPDATED ROLES) ---
$founders = [
    // Shubham: marketplace handler/developer
    ['name' => 'Shubham P', 'role' => 'Marketplace Developer & Lead', 'desc' => 'Leads development and operation of the core marketplace, focusing on listing, searching, and product management.', 'initials' => 'SP'],
    // Vedant: complete ui/ux
    ['name' => 'Vedant G', 'role' => 'Complete UI/UX Designer', 'desc' => 'Responsible for the entire user interface and user experience, ensuring a clean, intuitive, and consistent design across all pages.', 'initials' => 'VG'],
    // Tejas: forum handler/developer
    ['name' => 'Tejas M', 'role' => 'Forum Developer & Handler', 'desc' => 'Manages development and moderation of the community forum, ensuring smooth discussions and support.', 'initials' => 'TM'],
    // Terell: profile manager
    ['name' => 'Terell C', 'role' => 'User Profile Manager', 'desc' => 'Oversees user data, account security, and all profile settings and management features.', 'initials' => 'TC'],
    // Devang: financer/checkout page devloper
    ['name' => 'Devang P', 'role' => 'Financer & Checkout Developer', 'desc' => 'Handles financial integrations, payment gateways, and the complete checkout process.', 'initials' => 'DP'],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextRig - About Us</title>
    <script src="https://kit.fontawesome.com/4a24449835.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --dark-bg: #222831;
            --medium-bg: #393E46;
            --light-bg: #F8F9FA;
            --text-light: #EEEEEE;
            --text-dark: #333333;
            --accent-grey: #bdc3c7; /* FINAL ACCENT COLOR */
            --border-color: #E0E0E0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        a { text-decoration: none; color: inherit; }
        p { line-height: 1.6; }
        /* ABOUT US Specific Styles */
        .page-hero {
            background-color: var(--medium-bg);
            color: var(--text-light);
            padding: 100px 20px;
            text-align: center;
        }
        .page-hero h1 { font-size: 3rem; margin-bottom: 15px; }
        .page-hero p { font-size: 1.2rem; opacity: 0.8; }
        .page-hero hr { width: 80px; margin: 20px auto; border: 1px solid var(--accent-grey); } 

        .content-section {
            padding: 60px 0;
        }
        .content-section h2 { 
            font-size: 2.2rem; 
            margin-bottom: 40px; 
            text-align: center;
            color: var(--dark-bg);
        }
        
        /* Mission/Challenges Grid */
        .challenges-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .challenge-card {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .challenge-card h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .challenge-card p {
            color: #666;
            font-size: 0.95rem;
        }
        .challenge-card.solution h3 { color: var(--accent-grey); } 

        /* Value Statement Section */
        .value-statement {
            background-color: var(--dark-bg);
            color: var(--text-light);
            padding: 80px 0;
            text-align: center;
        }
        .value-statement h2 {
            color: var(--text-light);
            margin-bottom: 40px;
        }
        .value-statement blockquote {
            font-size: 1.5rem;
            max-width: 800px;
            margin: 0 auto;
            border-left: 5px solid var(--accent-grey); 
            padding-left: 25px;
            line-height: 1.6;
        }
        
        /* Team Section */
        .team-grid {
            display: flex;
            justify-content: center;
            gap: 30px; 
            padding: 30px 0;
            flex-wrap: wrap;
        }
        .team-card {
            text-align: center;
            max-width: 180px; 
        }
        .team-avatar {
            width: 100px; 
            height: 100px;
            border-radius: 50%;
            background-color: var(--accent-grey); 
            color: var(--dark-bg); 
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-weight: 700;
        }
        .team-card h3 {
            font-size: 1.3rem;
            margin-bottom: 5px;
            color: var(--dark-bg);
        }
        .team-card p.role {
            color: var(--accent-grey);
            font-weight: 500;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }
        
        /* Footer */
        .footer { background-color: var(--dark-bg); color: var(--text-light); padding: 30px 0; text-align: center; border-top: 1px solid var(--medium-bg);}
        .footer p { color: #bdc3c7; font-size: 0.9rem; }
    </style>
</head>
<body>
    <?php include 'header.php' ?>
<section class="page-hero">
    <div class="container">
        <h1>Building a Unified Hub for PC Enthusiasts</h1>
        <hr>
        <p>
            NextRig is a web-based platform designed to combine a discussion forum with a specialized marketplace for secondhand PC components, offering a seamless, secure, and user-focused solution.
        </p>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <h2>The Challenge We Solve</h2>
        <div class="challenges-grid">
            
            <div class="challenge-card">
                <h3>Fragmentation of Services</h3>
                <p>Technology forums (like Reddit, Tom’s Hardware) provide discussions but lack direct integration with part trading. Generic marketplaces (like OLX, eBay) lack a focused PC-building community.</p>
            </div>
            
            <div class="challenge-card">
                <h3>Inadequate Search & Filtering</h3>
                <p>General-purpose marketplaces do not support crucial hardware-specific filtering (such as VRAM size or DDR type). This makes locating compatible or desired components inefficient and time-consuming.</p>
            </div>
            
            <div class="challenge-card">
                <h3>Trust and Transparency</h3>
                <p>Generic platforms often face issues of scams, counterfeit listings, and unverified sellers, as they lack a mechanism for focused moderation and reputation building for PC-related transactions.</p>
            </div>
            
        </div>
    </div>
</section>

<section class="content-section" style="background-color: var(--light-bg);">
    <div class="container">
        <h2 style="color: var(--accent-grey);">How NextRig Delivers</h2>
        <div class="challenges-grid">
            
            <div class="challenge-card solution">
                <h3>Unified Experience</h3>
                <p>We combine the features of a discussion forum and a secondhand marketplace into a single, specialized platform, reducing the fragmentation of services.</p>
            </div>
            
            <div class="challenge-card solution">
                <h3>Advanced Filtering</h3>
                <p>We enable users to filter listings by category, specifications, price, and condition, ensuring efficient discovery of components.</p>
            </div>
            
            <div class="challenge-card solution">
                <h3>Community-Driven Trust</h3>
                <p>We build reliability through moderated listings, user profiles, and a secure posting system, providing reliability and transparency within a focused PC-specific marketplace.</p>
            </div>
            
        </div>
    </div>
</section>

<section class="value-statement">
    <div class="container">
        <h2>"Knowledge Sharing Meets Commerce."</h2>
        <blockquote>
            Our platform nurtures community interaction while providing practical utility for users who wish to buy or sell hardware reliably.
        </blockquote>
    </div>
</section>

<section class="content-section" style="border-bottom: none;">
    <div class="container">
        <h2>The NextRig Team</h2>
        <div class="team-grid">
            <?php foreach ($founders as $founder): ?>
            <div class="team-card">
                <div class="team-avatar"><?= htmlspecialchars($founder['initials']) ?></div> 
                <h3><?= htmlspecialchars($founder['name']) ?></h3>
                <p class="role"><?= htmlspecialchars($founder['role']) ?></p>
                <p style="font-size: 0.9rem; color: #666;"><?= htmlspecialchars($founder['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include 'footer.php' ?>
</body>
</html>