<div class="dashboard-container">
    <div class="welcome-section">
        <div class="welcome-card">
            <div class="avatar-section">
                <div class="avatar">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                </div>
            </div>
            <div class="welcome-content">
                <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                <p>Good to see you again. Here's what's happening with your account.</p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Account Information</h3>
                <span class="card-icon">👤</span>
            </div>
            <div class="card-content">
                <div class="info-item">
                    <label>Full Name:</label>
                    <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                </div>
                <div class="info-item">
                    <label>Username:</label>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-item">
                    <label>Email:</label>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <label>Phone:</label>
                    <span><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Not provided'; ?></span>
                </div>
                <div class="info-item">
                    <label>Member Since:</label>
                    <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
            <div class="card-footer">
                <a href="/profile" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Account Security</h3>
                <span class="card-icon">🔒</span>
            </div>
            <div class="card-content">
                <div class="security-item">
                    <div class="security-info">
                        <strong>Email Verification</strong>
                        <span class="status <?php echo $user['email_verified'] ? 'verified' : 'unverified'; ?>">
                            <?php echo $user['email_verified'] ? 'Verified' : 'Unverified'; ?>
                        </span>
                    </div>
                    <?php if (!$user['email_verified']): ?>
                        <button class="btn btn-secondary btn-sm">Verify Email</button>
                    <?php endif; ?>
                </div>
                
                <div class="security-item">
                    <div class="security-info">
                        <strong>Two-Factor Authentication</strong>
                        <span class="status <?php echo $user['two_factor_enabled'] ? 'enabled' : 'disabled'; ?>">
                            <?php echo $user['two_factor_enabled'] ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                    <button class="btn btn-secondary btn-sm">
                        <?php echo $user['two_factor_enabled'] ? 'Manage' : 'Enable'; ?>
                    </button>
                </div>
                
                <div class="security-item">
                    <div class="security-info">
                        <strong>Last Login</strong>
                        <span><?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></span>
                    </div>
                </div>
                
                <div class="security-item">
                    <div class="security-info">
                        <strong>Login IP</strong>
                        <span><?php echo $user['last_login_ip'] ?? 'Unknown'; ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/profile" class="btn btn-primary">Security Settings</a>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Quick Actions</h3>
                <span class="card-icon">⚡</span>
            </div>
            <div class="card-content">
                <div class="actions-grid">
                    <a href="/profile" class="action-item">
                        <span class="action-icon">✏️</span>
                        <span>Edit Profile</span>
                    </a>
                    <a href="/profile" class="action-item">
                        <span class="action-icon">🔑</span>
                        <span>Change Password</span>
                    </a>
                    <a href="/logout" class="action-item">
                        <span class="action-icon">🚪</span>
                        <span>Sign Out</span>
                    </a>
                    <a href="#" class="action-item">
                        <span class="action-icon">📊</span>
                        <span>Activity Log</span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Recent Activity</h3>
                <span class="card-icon">📈</span>
            </div>
            <div class="card-content">
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon success">✓</div>
                        <div class="activity-content">
                            <strong>Successful Login</strong>
                            <span class="activity-time"><?php echo $user['last_login'] ? date('M j, g:i A', strtotime($user['last_login'])) : 'Never'; ?></span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon info">👤</div>
                        <div class="activity-content">
                            <strong>Account Created</strong>
                            <span class="activity-time"><?php echo date('M j, g:i A', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="#" class="btn btn-secondary">View All Activity</a>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.welcome-section {
    margin-bottom: 3rem;
}

.welcome-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 2rem;
}

.avatar-section {
    flex-shrink: 0;
}

.avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
}

.welcome-content h1 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 0.5rem;
}

.welcome-content p {
    color: #666;
    font-size: 1.1rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.dashboard-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
}

.card-header {
    padding: 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.25rem;
}

.card-icon {
    font-size: 1.5rem;
}

.card-content {
    padding: 1.5rem;
}

.card-footer {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item label {
    font-weight: 500;
    color: #666;
}

.info-item span {
    color: #333;
}

.security-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.security-item:last-child {
    border-bottom: none;
}

.security-info {
    flex: 1;
}

.security-info strong {
    display: block;
    color: #333;
    margin-bottom: 0.25rem;
}

.status {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 500;
}

.status.verified,
.status.enabled {
    background: #d4edda;
    color: #155724;
}

.status.unverified,
.status.disabled {
    background: #f8d7da;
    color: #721c24;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.action-item:hover {
    border-color: #667eea;
    background: #f8f9ff;
    transform: translateY(-2px);
}

.action-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.activity-list {
    space-y: 1rem;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.activity-icon.success {
    background: #28a745;
}

.activity-icon.info {
    background: #17a2b8;
}

.activity-content {
    flex: 1;
}

.activity-content strong {
    display: block;
    color: #333;
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.875rem;
    color: #666;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .welcome-card {
        flex-direction: column;
        text-align: center;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>