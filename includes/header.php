<?php
$userInfo = Auth::getUserInfo();
?>
<div class="position-relative iq-banner">
    <nav class="nav navbar navbar-expand-xl navbar-light iq-navbar">
        <div class="container-fluid navbar-inner col-12" style="margin-right: 5px; padding-right: 0;">
            <div class="col-6">
                <!-- <a href="dashBoard.php" class="navbar-brand">
                    
                <h4 class="logo-title">Erundeniya</h4>
                </a> -->
            </div>
            <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
                <i class="icon">
                    <svg width="20px" class="icon-20" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
                    </svg>
                </i>
            </div>
            <div class="align-content-end col-6 align-items-end" style="display: flex; justify-content: end;">
                <a class="py-0 nav-link d-flex align-items-center" href="#" id="navbarDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="assets/images/avatars/01.png" alt="User-Profile"
                        class="theme-color-default-img img-fluid avatar avatar-50 avatar-rounded">
                    <div class="caption ms-3 d-none d-md-block">
                        <h6 class="mb-0 caption-title"><?php echo htmlspecialchars($userInfo['full_name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($userInfo['role_name'] ?? 'User'); ?></small>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="auth.php?action=logout">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="iq-navbar-header" style="height: 215px;">
        <div class="container-fluid iq-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="flex-wrap d-flex justify-content-between align-items-center">
                        <div>
                            <h1>Welcome Back, <?php echo htmlspecialchars($userInfo['full_name']); ?>!</h1>
                            <p>Manage your pharmacy efficiently with our system.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="iq-header-img">
            <img src="assets/images/dashboard/top-header.png" alt="header"
                class="theme-color-default-img img-fluid w-100 h-100 animated-scaleX">
        </div>
    </div>
</div>