<?php
session_start();
require('../config/database.php');

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: admin_login.php");
    exit;
}

$msg = '';

if(isset($_POST['add_offer'])){
    $title = trim($_POST['title']);
    $discount = floatval($_POST['discount']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $category_id = intval($_POST['category_id']);

    if(empty($title) || $discount <= 0 || empty($start_date) || empty($end_date) || $category_id <= 0){
        die("All fields are required and must be valid.");
    }

    $image = null;
    if(isset($_FILES['image']) && $_FILES['image']['error'] === 0){
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageDir = 'uploads/offers/';
        if(!is_dir('../'.$imageDir)) mkdir('../'.$imageDir, 0755, true);
        $image = $imageDir . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../'.$image);
    }

    $stmt = $conn->prepare("INSERT INTO offers (title, discount, start_date, end_date, category_id, image) VALUES (?, ?, ?, ?, ?, ?)");
    if(!$stmt) die("Prepare failed: ".$conn->error);
    
    $stmt->bind_param("sdssis", $title, $discount, $start_date, $end_date, $category_id, $image);
    if(!$stmt->execute()) die("Execute failed: ".$stmt->error);
    
    $stmt->close();
    $msg = "Offer added successfully!";
}

$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$offers = $conn->query("SELECT o.*, c.category_name FROM offers o LEFT JOIN categories c ON o.category_id = c.category_id ORDER BY o.start_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Offer Management | Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #7C3AED;       /* Vibrant purple */
    --primary-dark: #6D28D9;  /* Darker purple */
    --primary-light: #C4B5FD; /* Light purple */
    --secondary: #0EA5E9;     /* Sky blue */
    --accent: #F59E0B;        /* Amber */
    --success: #10B981;       /* Emerald */
    --warning: #F59E0B;       /* Amber */
    --danger: #EF4444;        /* Red */
    --info: #8B5CF6;          /* Indigo */
    --dark: #1E293B;          /* Dark blue-gray */
    --darker: #0F172A;        /* Darker blue-gray */
    --light: #F8FAFC;         /* Light background */
    --gray: #64748B;          /* Medium gray */
    --gray-light: #E2E8F0;    /* Light gray */
    --border-radius: 16px;
    --border-radius-sm: 8px;
    --shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 30px 60px rgba(0, 0, 0, 0.15);
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --glass: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.15);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
    min-height: 100vh;
    color: var(--dark);
    line-height: 1.6;
    overflow-x: hidden;
}

.dashboard-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 2rem;
}

/* Header Styles */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3rem;
    padding: 2rem;
    background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    border: 1px solid var(--gray-light);
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
}

.header-content h1 {
    font-size: 2.8rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.header-content p {
    color: var(--gray);
    font-size: 1.1rem;
}

.admin-badge {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    font-weight: 600;
}

.admin-badge i {
    font-size: 1.5rem;
}

/* Notification */
.notification {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(124, 58, 237, 0.1));
    color: var(--success);
    padding: 1.25rem 2rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    border-left: 4px solid var(--success);
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(16, 185, 129, 0.2);
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from { transform: translateX(-100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Main Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2.5rem;
    margin-bottom: 3rem;
}

@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

/* Form Styles */
.form-panel {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    border: 1px solid var(--gray-light);
}

.form-header {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    padding: 1.75rem 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
}

.form-header i {
    font-size: 1.5rem;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.75rem;
    border-radius: 12px;
}

.form-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
}

.form-body {
    padding: 2.5rem 2rem;
}

.form-group {
    margin-bottom: 1.75rem;
    position: relative;
}

.form-label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: var(--dark);
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 1.1rem 1.25rem;
    background: white;
    border: 2px solid var(--gray-light);
    border-radius: var(--border-radius-sm);
    color: var(--dark);
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
    transition: var(--transition);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    transform: translateY(-2px);
}

.form-control::placeholder {
    color: var(--gray);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1.25rem 2rem;
    border: none;
    border-radius: var(--border-radius-sm);
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    width: 100%;
    box-shadow: 0 10px 30px rgba(124, 58, 237, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(124, 58, 237, 0.4);
}

/* Offers Panel */
.offers-panel {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    border: 1px solid var(--gray-light);
}

.offers-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.75rem 2rem;
    background: linear-gradient(135deg, #F8FAFC, #F1F5F9);
    border-bottom: 1px solid var(--gray-light);
}

.offers-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--dark);
}

.offers-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-badge {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    box-shadow: var(--shadow-sm);
}

/* Offers Grid */
.offers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    padding: 2.5rem 2rem;
}

.offer-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
    border: 1px solid var(--gray-light);
    position: relative;
}

.offer-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-lg);
}

.offer-badge {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    background: linear-gradient(135deg, var(--accent), var(--warning));
    color: white;
    padding: 0.6rem 1.1rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 700;
    z-index: 2;
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
}

.offer-status {
    position: absolute;
    top: 1.25rem;
    left: 1.25rem;
    padding: 0.6rem 1.1rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    z-index: 2;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.status-active {
    background: linear-gradient(135deg, var(--success), #059669);
    color: white;
}

.status-expired {
    background: linear-gradient(135deg, var(--danger), #DC2626);
    color: white;
}

.status-upcoming {
    background: linear-gradient(135deg, var(--warning), #D97706);
    color: white;
}

.offer-media {
    height: 200px;
    position: relative;
    overflow: hidden;
}

.offer-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.offer-card:hover .offer-image {
    transform: scale(1.1);
}

.media-placeholder {
    height: 100%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3.5rem;
}

.offer-content {
    padding: 1.75rem;
    position: relative;
    z-index: 1;
}

.offer-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--dark);
    line-height: 1.4;
}

.offer-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: var(--gray);
}

.offer-category {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(124, 58, 237, 0.1);
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    color: var(--primary);
    font-weight: 500;
}

.offer-dates {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-light);
}

.date-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: var(--gray);
}

.date-item i {
    color: var(--primary);
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    color: var(--gray);
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
    color: var(--gray-light);
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--dark);
}

/* Navigation */
.navigation {
    display: flex;
    justify-content: center;
    margin-top: 3rem;
}

.nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: white;
    color: var(--primary);
    padding: 1rem 2rem;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    border: 2px solid var(--primary);
    box-shadow: var(--shadow-sm);
}

.nav-btn:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(124, 58, 237, 0.3);
}

/* Animations */
@keyframes fadeInUp {
    from { 
        opacity: 0; 
        transform: translateY(40px) scale(0.95);
    }
    to { 
        opacity: 1; 
        transform: translateY(0) scale(1);
    }
}

.offer-card {
    animation: fadeInUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
        padding: 1.5rem;
    }
    
    .header-content h1 {
        font-size: 2.2rem;
    }
    
    .offers-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 1.5rem;
    }
    
    .form-body, .offers-grid {
        padding: 1.5rem;
    }
    
    .offers-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #F1F5F9;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, var(--primary-dark), #0EA5E9);
}

/* Floating Animation */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.floating {
    animation: float 6s ease-in-out infinite;
}

/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Pulse Animation */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.pulse {
    animation: pulse 2s infinite;
}

/* Additional Visual Elements */
.visual-accent {
    position: fixed;
    width: 500px;
    height: 500px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(124, 58, 237, 0.1) 0%, rgba(14, 165, 233, 0.05) 70%, transparent 100%);
    z-index: -1;
}

.accent-1 {
    top: -250px;
    right: -250px;
}

.accent-2 {
    bottom: -250px;
    left: -250px;
    background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, rgba(16, 185, 129, 0.05) 70%, transparent 100%);
}
</style>
</head>
<body>
    <!-- Visual Background Accents -->
    <div class="visual-accent accent-1"></div>
    <div class="visual-accent accent-2"></div>

    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-tags"></i> Offer Management</h1>
                <p>Create and manage promotional offers for IBA STORE
                </p>
            </div>
            <div class="admin-badge">
                <i class="fas fa-user-cog"></i>
                <span>Administrator</span>
            </div>
        </header>

        <?php if($msg): ?>
            <div class="notification">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Create Offer Form -->
            <div class="form-panel">
                <div class="form-header">
                    <i class="fas fa-plus-circle"></i>
                    <h2>Create New Offer</h2>
                </div>
                <div class="form-body">
                    <form method="POST" enctype="multipart/form-data" id="offerForm">
                        <div class="form-group">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading"></i> Offer Title
                            </label>
                            <input type="text" id="title" name="title" class="form-control" placeholder="Enter offer title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="discount" class="form-label">
                                <i class="fas fa-percentage"></i> Discount Percentage
                            </label>
                            <input type="number" id="discount" name="discount" step="0.01" class="form-control" placeholder="0.00" required min="0.01" max="100">
                            <small style="color: var(--gray); font-size: 0.85rem; margin-top: 0.5rem; display: block;">Enter a value between 0.01 and 100</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date" class="form-label">
                                <i class="fas fa-calendar-alt"></i> Start Date
                            </label>
                            <input type="date" id="start_date" name="start_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date" class="form-label">
                                <i class="fas fa-calendar-times"></i> End Date
                            </label>
                            <input type="date" id="end_date" name="end_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="image" class="form-label">
                                <i class="fas fa-image"></i> Offer Image
                            </label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                            <small style="color: var(--gray); font-size: 0.85rem; margin-top: 0.5rem; display: block;">Optional: Upload an image for this offer</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id" class="form-label">
                                <i class="fas fa-tag"></i> Category
                            </label>
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">-- Select Category --</option>
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_offer" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Create Offer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Offers Display -->
            <div class="offers-panel">
                <div class="offers-header">
                    <h2><i class="fas fa-list-ul"></i> Current Offers</h2>
                    <div class="offers-stats">
                        <div class="stat-badge">
                            <?php 
                            $offer_count = $offers ? $offers->num_rows : 0;
                            echo $offer_count . " Offer" . ($offer_count != 1 ? 's' : '');
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="offers-grid">
                    <?php if($offers && $offers->num_rows > 0): ?>
                        <?php while($row = $offers->fetch_assoc()): 
                            $today = date('Y-m-d');
                            $status = '';
                            if ($today < $row['start_date']) {
                                $status = 'upcoming';
                            } else if ($today > $row['end_date']) {
                                $status = 'expired';
                            } else {
                                $status = 'active';
                            }
                        ?>
                            <div class="offer-card">
                                <div class="offer-status status-<?= $status ?>">
                                    <?= ucfirst($status) ?>
                                </div>
                                <div class="offer-badge">
                                    -<?= $row['discount'] ?>%
                                </div>
                                
                                <div class="offer-media">
                                    <?php if($row['image']): ?>
                                        <img src="../<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="offer-image">
                                    <?php else: ?>
                                        <div class="media-placeholder">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="offer-content">
                                    <h3 class="offer-title"><?= htmlspecialchars($row['title']) ?></h3>
                                    <div class="offer-meta">
                                        <div class="offer-category">
                                            <i class="fas fa-tag"></i>
                                            <span><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></span>
                                        </div>
                                    </div>
                                    <div class="offer-dates">
                                        <div class="date-item">
                                            <i class="fas fa-play-circle"></i>
                                            <span><?= $row['start_date'] ?></span>
                                        </div>
                                        <div class="date-item">
                                            <i class="fas fa-stop-circle"></i>
                                            <span><?= $row['end_date'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <h3>No Offers Found</h3>
                            <p>Create your first promotional offer to get started</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="navigation">
            <a href="admin_dashboard.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script>
    // Enhanced Interactive Functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Date validation with enhanced UX
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        
        if(startDate && endDate) {
            const today = new Date().toISOString().split('T')[0];
            startDate.min = today;
            
            startDate.addEventListener('change', function() {
                endDate.min = this.value;
                if(endDate.value && endDate.value < this.value) {
                    endDate.value = this.value;
                    showToast('End date adjusted to match start date', 'info');
                }
            });
            
            endDate.addEventListener('change', function() {
                if(this.value < startDate.value) {
                    this.value = startDate.value;
                    showToast('End date cannot be before start date', 'warning');
                }
            });
        }
        
        // Enhanced form validation
        const form = document.getElementById('offerForm');
        if(form) {
            form.addEventListener('submit', function(e) {
                const discount = document.getElementById('discount');
                if(discount && (discount.value < 0.01 || discount.value > 100)) {
                    e.preventDefault();
                    showToast('Discount must be between 0.01% and 100%', 'error');
                    discount.focus();
                }
            });
        }
        
        // Enhanced card animations
        const offerCards = document.querySelectorAll('.offer-card');
        offerCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.style.opacity = '0';
            
            // Enhanced hover effects
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Real-time discount percentage display
        const discountInput = document.getElementById('discount');
        if(discountInput) {
            discountInput.addEventListener('input', function() {
                const value = this.value;
                if(value >= 0.01 && value <= 100) {
                    this.style.borderColor = '#10B981';
                } else {
                    this.style.borderColor = '#EF4444';
                }
            });
        }
        
        // Add floating animation to some elements
        const badges = document.querySelectorAll('.offer-badge, .offer-status');
        badges.forEach(badge => {
            badge.classList.add('floating');
        });
    });
    
    // Toast notification system
    function showToast(message, type = 'info') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.custom-toast');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `custom-toast toast-${type}`;
        
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        
        toast.innerHTML = `
            <i class="fas fa-${icons[type] || 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Add styles for toast
        toast.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: white;
            color: #1E293B;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--${type});
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1000;
            animation: slideInRight 0.3s ease-out;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            font-weight: 500;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-in forwards';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
    
    // Add CSS for toast animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>