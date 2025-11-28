<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();
$userInfo = Auth::getUserInfo();

// Get user details
$stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u 
                        LEFT JOIN roles r ON u.role_id = r.role_id 
                        WHERE u.user_id = ?");
$stmt->bind_param("i", $userInfo['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user statistics
$stats = [];

// Total sales by this user
$salesStmt = $conn->prepare("SELECT COUNT(*) as total_sales, 
                              COALESCE(SUM(net_amount), 0) as total_revenue 
                              FROM sales WHERE user_id = ?");
$salesStmt->bind_param("i", $userInfo['user_id']);
$salesStmt->execute();
$stats = $salesStmt->get_result()->fetch_assoc();

// Sales today
$todaySalesStmt = $conn->prepare("SELECT COUNT(*) as today_sales, 
                                   COALESCE(SUM(net_amount), 0) as today_revenue 
                                   FROM sales 
                                   WHERE user_id = ? AND DATE(sale_date) = CURDATE()");
$todaySalesStmt->bind_param("i", $userInfo['user_id']);
$todaySalesStmt->execute();
$todayStats = $todaySalesStmt->get_result()->fetch_assoc();

// Last login (you can add a last_login field to users table later)
$joinedDate = date('M d, Y', strtotime($user['created_at']));

// Helper function to display email or N/A
$displayEmail = !empty($user['email']) ? htmlspecialchars($user['email']) : 'N/A';
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>E. W. D. Erundeniya</title>

    <link rel="shortcut icon" href="assets/images/logoblack.png">
    <link rel="stylesheet" href="assets/css/core/libs.min.css">
    <link rel="stylesheet" href="assets/css/hope-ui.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/custom.min.css?v=5.0.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">

    <style>
        .profile-header {
            background: linear-gradient(135deg, #1fa832ff 0%, #764ba2 100%);
            padding: 40px 0;
            color: white;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: #05a005ff;
            margin: 0 auto 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .info-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .info-value.na-text {
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
    </div>

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="conatiner-fluid content-inner mt-n5 py-0">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="container">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="text-center">
                        <h2 class="mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p class="mb-1">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <span class="badge bg-success text-dark"><?php echo htmlspecialchars($user['role_name']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e3f2fd; color: #1976d2;">
                            <svg width="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 12H16M8 16H16M12 20H7C5.89543 20 5 19.1046 5 18V6C5 4.89543 5.89543 4 7 4H17C18.1046 4 19 4.89543 19 6V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </div>
                        <h6 class="text-muted mb-2">Total Sales</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_sales']); ?></h3>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e8f5e9; color: #388e3c;">
                            <svg width="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2V22M17 5H9.5C8.57174 5 7.6815 5.36875 7.02513 6.02513C6.36875 6.6815 6 7.57174 6 8.5C6 9.42826 6.36875 10.3185 7.02513 10.9749C7.6815 11.6313 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 12.3687 16.9749 13.0251C17.6313 13.6815 18 14.5717 18 15.5C18 16.4283 17.6313 17.3185 16.9749 17.9749C16.3185 18.6313 15.4283 19 14.5 19H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </div>
                        <h6 class="text-muted mb-2">Total Revenue</h6>
                        <h3 class="mb-0">Rs. <?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fff3e0; color: #f57c00;">
                            <svg width="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 12H16M8 16H16M12 20H7C5.89543 20 5 19.1046 5 18V6C5 4.89543 5.89543 4 7 4H17C18.1046 4 19 4.89543 19 6V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </div>
                        <h6 class="text-muted mb-2">Today's Sales</h6>
                        <h3 class="mb-0"><?php echo number_format($todayStats['today_sales']); ?></h3>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fce4ec; color: #c2185b;">
                            <svg width="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2V22M17 5H9.5C8.57174 5 7.6815 5.36875 7.02513 6.02513C6.36875 6.6815 6 7.57174 6 8.5C6 9.42826 6.36875 10.3185 7.02513 10.9749C7.6815 11.6313 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 12.3687 16.9749 13.0251C17.6313 13.6815 18 14.5717 18 15.5C18 16.4283 17.6313 17.3185 16.9749 17.9749C16.3185 18.6313 15.4283 19 14.5 19H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </div>
                        <h6 class="text-muted mb-2">Today's Revenue</h6>
                        <h3 class="mb-0">Rs. <?php echo number_format($todayStats['today_revenue'], 2); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="info-card">
                        <h5 class="mb-4">Personal Information</h5>
                        <div class="info-row">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Username</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value <?php echo $displayEmail === 'N/A' ? 'na-text' : ''; ?>">
                                <?php echo $displayEmail; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Role</span>
                            <span class="info-value">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Member Since</span>
                            <span class="info-value"><?php echo $joinedDate; ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="info-card">
                        <h5 class="mb-4">Account Settings</h5>
                        <button class="btn btn-primary w-100" onclick="showEditProfileModal()">
                            <svg width="20" class="me-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13.7476 20.4428H21.0002" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                <path d="M12.78 3.79479C13.5557 2.86779 14.95 2.73186 15.8962 3.49173L17.6295 4.83879C18.669 5.46719 18.992 6.80311 18.3494 7.82259L8.81195 19.7645C8.49578 20.1589 8.01583 20.3918 7.50291 20.3973L3.86353 20.443L3.04353 16.9723C2.92866 16.4843 3.04353 15.9718 3.3597 15.5773L12.78 3.79479Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            Edit Profile
                        </button>
                    </div>

                    <div class="info-card mt-4">
                        <h5 class="mb-3">Quick Actions</h5>
                        <a href="pos.php" class="btn btn-primary w-100 mb-2">
                            <svg width="20" class="me-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                            New Sale
                        </a>
                        <?php if ($user['role_id'] == 3): ?>
                            <button class="btn btn-outline-secondary w-100" disabled title="Access denied for Cashiers">
                                <svg width="20" class="me-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg ">
                                    <path d="M8 12H16M8 16H16M12 20H7C5.89543 20 5 19.1046 5 18V6C5 4.89543 5.89543 4 7 4H17C18.1046 4 19 4.89543 19 6V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                                View Sales History
                            </button>
                        <?php else: ?>
                            <a href="sales_history.php" class="btn btn-outline-primary w-100">
                                <svg width="20" class="me-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg ">
                                    <path d="M8 12H16M8 16H16M12 20H7C5.89543 20 5 19.1046 5 18V6C5 4.89543 5.89543 4 7 4H17C18.1046 4 19 4.89543 19 6V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                                View Sales History
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="profileForm">
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name (Required)</label>
                            <input type="text" class="form-control" id="fullName" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email (Optional)</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="abc@gmail.com">
                            <small class="text-muted">Leave empty if not available</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateProfile()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/libs.min.js"></script>
    <script src="assets/js/core/external.min.js"></script>
    <script src="assets/js/hope-ui.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>

    <script>
        const editProfileModal = new bootstrap.Modal(document.getElementById('editProfileModal'));

        function showEditProfileModal() {
            editProfileModal.show();
        }

        function updateProfile() {
            const fullName = document.getElementById('fullName').value.trim();
            const email = document.getElementById('email').value.trim();

            if (!fullName) {
                Swal.fire('Error', 'Full name is required', 'error');
                return;
            }

            fetch('api/update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        full_name: fullName,
                        email: email || null
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success', 'Profile updated successfully', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'An error occurred', 'error');
                });
        }
    </script>
</body>

</html>