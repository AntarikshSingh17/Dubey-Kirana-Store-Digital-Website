
<?php
require_once __DIR__ . '/includes/header.php';

$successMessage = '';
$errorMessage = '';

$name = '';
$email = '';
$phone = '';
$subject = '';
$message = '';

$storeEmail = 'dubeykiranastore123@gmail.com';
$storePhone = '9999999999'; // Temporary number: edit when owner provides actual number
$storeAddress = 'Main Road, Near Post Office, Venkatnagar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate form data
    if (
        $name === '' ||
        $email === '' ||
        $phone === '' ||
        $subject === '' ||
        $message === ''
    ) {
        $errorMessage = 'Please fill in all required fields.';

    } elseif (strlen($name) > 100) {
        $errorMessage = 'Name cannot exceed 100 characters.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';

    } elseif (!preg_match('/^[0-9+\-\s()]{10,20}$/', $phone)) {
        $errorMessage = 'Please enter a valid phone number.';

    } elseif (strlen($message) < 10) {
        $errorMessage = 'Message must contain at least 10 characters.';

    } elseif (strlen($message) > 5000) {
        $errorMessage = 'Message cannot exceed 5000 characters.';

    } else {

        // Prevent email header injection
        if (
            preg_match('/[\r\n]/', $email) ||
            preg_match('/[\r\n]/', $subject)
        ) {
            $errorMessage = 'Invalid form information.';

        } else {

            $mailSubject = 'Contact Enquiry - Dubey Kirana Store';

            $mailBody = "
You have received a new contact enquiry from Dubey Kirana Store.

----------------------------------------
CUSTOMER DETAILS
----------------------------------------

Name: {$name}
Email: {$email}
Phone: {$phone}
Subject: {$subject}

----------------------------------------
MESSAGE
----------------------------------------

{$message}

----------------------------------------
Store: Dubey Kirana Store
Website: " . SITE_URL . "
";

            /*
             * Hosting email configuration:
             * The hosting provider must support PHP mail().
             */

            $headers = [];

            $headers[] = 'From: Dubey Kirana Store <' . $storeEmail . '>';
            $headers[] = 'Reply-To: ' . $email;
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'X-Mailer: PHP/' . phpversion();

            $mailSent = mail(
                $storeEmail,
                $mailSubject,
                $mailBody,
                implode("\r\n", $headers)
            );

            if ($mailSent) {

                $successMessage = 'Thank you! Your message has been sent successfully.';

                // Clear form after successful submission
                $name = '';
                $email = '';
                $phone = '';
                $subject = '';
                $message = '';

            } else {

                $errorMessage = 'Sorry, your message could not be sent. Please try again later.';

            }
        }
    }
}
?>

<!-- CONTACT PAGE -->
<section class="section">

    <div class="container">

        <!-- Page Heading -->
        <div style="text-align:center; margin-bottom:45px;">

            <span style="
                color:#218838;
                font-weight:700;
                text-transform:uppercase;
                letter-spacing:2px;
            ">
                Get In Touch
            </span>

            <h1 style="
                font-size:36px;
                margin:12px 0;
            ">
                Contact Us
            </h1>

            <p style="
                color:#666;
                max-width:650px;
                margin:0 auto;
                line-height:1.8;
            ">
                Have questions about our products, delivery, or orders?
                Contact Dubey Kirana Store. We are happy to help you.
            </p>

        </div>

        <!-- Contact Layout -->
        <div style="
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
            gap:30px;
            align-items:stretch;
        ">

            <!-- Contact Information -->
            <div style="
                background:linear-gradient(135deg, #218838, #145c2a);
                color:white;
                padding:35px;
                border-radius:18px;
                box-shadow:0 8px 25px rgba(33,136,56,0.15);
            ">

                <h2 style="margin-bottom:15px;">
                    Contact Information
                </h2>

                <p style="
                    color:#e8f5e9;
                    line-height:1.8;
                    margin-bottom:35px;
                ">
                    You can reach us through email or visit our store.
                </p>

                <!-- Email -->
                <div style="margin-bottom:30px;">

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:15px;
                        margin-bottom:12px;
                    ">

                        <div style="
                            width:45px;
                            height:45px;
                            background:rgba(255,255,255,0.15);
                            border-radius:10px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            <i class="fa-solid fa-envelope"></i>
                        </div>

                        <h3>Email Address</h3>

                    </div>

                    <a href="mailto:<?= htmlspecialchars($storeEmail) ?>"
                       style="
                           color:white;
                           text-decoration:none;
                           word-break:break-word;
                       ">

                        <?= htmlspecialchars($storeEmail) ?>

                    </a>

                </div>

                <!-- Phone -->
                <div style="margin-bottom:30px;">

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:15px;
                        margin-bottom:12px;
                    ">

                        <div style="
                            width:45px;
                            height:45px;
                            background:rgba(255,255,255,0.15);
                            border-radius:10px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            <i class="fa-solid fa-phone"></i>
                        </div>

                        <h3>Phone Number</h3>

                    </div>

                    <a href="tel:<?= htmlspecialchars($storePhone) ?>"
                       style="
                           color:white;
                           text-decoration:none;
                       ">

                        <?= htmlspecialchars($storePhone) ?>

                    </a>

                </div>

                <!-- Address -->
                <div>

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:15px;
                        margin-bottom:12px;
                    ">

                        <div style="
                            width:45px;
                            height:45px;
                            background:rgba(255,255,255,0.15);
                            border-radius:10px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>

                        <h3>Store Address</h3>

                    </div>

                    <p style="
                        color:white;
                        line-height:1.8;
                    ">
                        <?= htmlspecialchars($storeAddress) ?>
                    </p>

                </div>

                <!-- Store Message -->
                <div style="
                    margin-top:35px;
                    padding:20px;
                    background:rgba(255,255,255,0.12);
                    border-radius:12px;
                ">

                    <i class="fa-solid fa-basket-shopping"
                       style="font-size:25px; margin-bottom:12px;">
                    </i>

                    <p style="line-height:1.7;">
                        Thank you for choosing Dubey Kirana Store
                        for your daily grocery needs.
                    </p>

                </div>

            </div>

            <!-- Contact Form -->
            <div style="
                background:white;
                padding:35px;
                border-radius:18px;
                box-shadow:0 8px 25px rgba(0,0,0,0.06);
            ">

                <h2 style="margin-bottom:10px;">
                    Send Us a Message
                </h2>

                <p style="
                    color:#777;
                    margin-bottom:25px;
                    line-height:1.7;
                ">
                    Fill in the form below and we will get back to you.
                </p>

                <!-- Success Message -->
                <?php if ($successMessage !== ''): ?>

                    <div style="
                        background:#e8f5e9;
                        color:#218838;
                        padding:15px;
                        border-radius:10px;
                        margin-bottom:20px;
                    ">

                        <i class="fa-solid fa-circle-check"></i>

                        <?= htmlspecialchars($successMessage) ?>

                    </div>

                <?php endif; ?>

                <!-- Error Message -->
                <?php if ($errorMessage !== ''): ?>

                    <div style="
                        background:#fff0f0;
                        color:#c62828;
                        padding:15px;
                        border-radius:10px;
                        margin-bottom:20px;
                    ">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?= htmlspecialchars($errorMessage) ?>

                    </div>

                <?php endif; ?>

                <form method="POST"
                      action="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/contact.php') ?>">

                    <!-- Name -->
                    <div style="margin-bottom:20px;">

                        <label for="name"
                               style="
                                   display:block;
                                   font-weight:600;
                                   margin-bottom:8px;
                               ">
                            Full Name *
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="<?= htmlspecialchars($name) ?>"
                            placeholder="Enter your full name"
                            maxlength="100"
                            required
                            style="
                                width:100%;
                                padding:14px;
                                border:1px solid #ddd;
                                border-radius:8px;
                                box-sizing:border-box;
                            "
                        >

                    </div>

                    <!-- Email -->
                    <div style="margin-bottom:20px;">

                        <label for="email"
                               style="
                                   display:block;
                                   font-weight:600;
                                   margin-bottom:8px;
                               ">
                            Email Address *
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="Enter your email address"
                            maxlength="150"
                            required
                            style="
                                width:100%;
                                padding:14px;
                                border:1px solid #ddd;
                                border-radius:8px;
                                box-sizing:border-box;
                            "
                        >

                    </div>

                    <!-- Phone -->
                    <div style="margin-bottom:20px;">

                        <label for="phone"
                               style="
                                   display:block;
                                   font-weight:600;
                                   margin-bottom:8px;
                               ">
                            Phone Number *
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            id="phone"
                            value="<?= htmlspecialchars($phone) ?>"
                            placeholder="Enter your phone number"
                            maxlength="20"
                            required
                            style="
                                width:100%;
                                padding:14px;
                                border:1px solid #ddd;
                                border-radius:8px;
                                box-sizing:border-box;
                            "
                        >

                    </div>

                    <!-- Subject -->
                    <div style="margin-bottom:20px;">

                        <label for="subject"
                               style="
                                   display:block;
                                   font-weight:600;
                                   margin-bottom:8px;
                               ">
                            Subject *
                        </label>

                        <select
                            name="subject"
                            id="subject"
                            required
                            style="
                                width:100%;
                                padding:14px;
                                border:1px solid #ddd;
                                border-radius:8px;
                                box-sizing:border-box;
                                background:white;
                            "
                        >

                            <option value="">Select Subject</option>

                            <?php
                            $subjectOptions = [
                                'General Inquiry',
                                'Order Related',
                                'Delivery Related',
                                'Product Inquiry',
                                'Payment Issue',
                                'Complaint',
                                'Other'
                            ];
                            ?>

                            <?php foreach ($subjectOptions as $subjectOption): ?>

                                <option
                                    value="<?= htmlspecialchars($subjectOption) ?>"
                                    <?= $subject === $subjectOption ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($subjectOption) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- Message -->
                    <div style="margin-bottom:25px;">

                        <label for="message"
                               style="
                                   display:block;
                                   font-weight:600;
                                   margin-bottom:8px;
                               ">
                            Your Message *
                        </label>

                        <textarea
                            name="message"
                            id="message"
                            rows="5"
                            maxlength="5000"
                            placeholder="Write your message here..."
                            required
                            style="
                                width:100%;
                                padding:14px;
                                border:1px solid #ddd;
                                border-radius:8px;
                                box-sizing:border-box;
                                resize:vertical;
                            "
                        ><?= htmlspecialchars($message) ?></textarea>

                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="btn"
                        style="
                            width:100%;
                            justify-content:center;
                            padding:15px;
                        "
                    >

                        <i class="fa-solid fa-paper-plane"></i>

                        Send Message

                    </button>

                </form>

            </div>

        </div>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>