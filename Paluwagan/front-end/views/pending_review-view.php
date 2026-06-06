<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Application Status — TrustFund</title>
    <link rel="stylesheet" href="../../assets/css/global.css" />
</head>
<body>

<div class="centered-container">
    <div class="status-card">
        
        <?php if ($user['status'] === 'denied'): ?>
            <div class="status-icon-box denied">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="app-title">Application Declined</h1>
            <p class="app-description">
                We regret to inform you that your registration parameters did not meet our verification criteria. Your details have been unlinked from active authentication systems. Clear this request below to sign up again.
            </p>
            
            <form action="pending-review.php" method="POST" class="btn-stack">
                <button type="submit" name="delete_account" class="btn-primary danger-action-btn">
                    Reset & Delete Account Data
                </button>
                <a href="../../index.php" class="btn-secondary">Back to Gateway</a>
            </form>

        <?php else: ?>
            <div class="status-icon-box pending">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="app-title">Review in Progress</h1>
            <p class="app-description">
                Your account submission is undergoing validation updates by safety officers. Authenticated credentials will remain limited until validation steps finish. Check back again later!
            </p>
            
            <div class="btn-stack">
                <a href="../../index.php" class="btn-primary">Return to Portal Gate</a>
            </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>