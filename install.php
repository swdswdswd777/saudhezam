<?php
// Installation script for ionCube Decryption Tool
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

class InstallManager {
    private $requirements = [
        'php_version' => '7.4.0',
        'extensions' => ['curl', 'json', 'mbstring', 'zlib'],
        'functions' => ['base64_decode', 'gzinflate', 'gzuncompress', 'str_rot13'],
        'directories' => ['uploads', 'decrypted', 'config', 'assets'],
        'permissions' => ['uploads' => 0755, 'decrypted' => 0755, 'config' => 0755]
    ];
    
    private $status = [];
    
    public function checkRequirements() {
        $this->checkPHPVersion();
        $this->checkExtensions();
        $this->checkFunctions();
        $this->checkDirectories();
        $this->checkPermissions();
        $this->checkWritePermissions();
        
        return $this->status;
    }
    
    private function checkPHPVersion() {
        $current = phpversion();
        $required = $this->requirements['php_version'];
        
        $this->status['php_version'] = [
            'name' => 'PHP Version',
            'required' => $required,
            'current' => $current,
            'status' => version_compare($current, $required, '>=') ? 'success' : 'error',
            'message' => version_compare($current, $required, '>=') ? 
                'PHP version is compatible' : 
                "PHP version {$required} or higher is required"
        ];
    }
    
    private function checkExtensions() {
        foreach ($this->requirements['extensions'] as $ext) {
            $loaded = extension_loaded($ext);
            $this->status['extensions'][$ext] = [
                'name' => $ext,
                'status' => $loaded ? 'success' : 'error',
                'message' => $loaded ? 
                    'Extension is loaded' : 
                    "Extension {$ext} is required but not loaded"
            ];
        }
    }
    
    private function checkFunctions() {
        foreach ($this->requirements['functions'] as $func) {
            $exists = function_exists($func);
            $this->status['functions'][$func] = [
                'name' => $func,
                'status' => $exists ? 'success' : 'error',
                'message' => $exists ? 
                    'Function is available' : 
                    "Function {$func} is required but not available"
            ];
        }
    }
    
    private function checkDirectories() {
        foreach ($this->requirements['directories'] as $dir) {
            $exists = is_dir($dir);
            $this->status['directories'][$dir] = [
                'name' => $dir,
                'status' => $exists ? 'success' : 'warning',
                'message' => $exists ? 
                    'Directory exists' : 
                    "Directory {$dir} will be created"
            ];
        }
    }
    
    private function checkPermissions() {
        foreach ($this->requirements['permissions'] as $dir => $perm) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $perms = fileperms($dir);
            $this->status['permissions'][$dir] = [
                'name' => $dir,
                'required' => decoct($perm),
                'current' => substr(decoct($perms), -3),
                'status' => ($perms & $perm) ? 'success' : 'warning',
                'message' => ($perms & $perm) ? 
                    'Directory permissions are correct' : 
                    "Directory {$dir} may need permission adjustment"
            ];
        }
    }
    
    private function checkWritePermissions() {
        $testDirs = ['uploads', 'decrypted', 'config'];
        
        foreach ($testDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $writable = is_writable($dir);
            $this->status['write_permissions'][$dir] = [
                'name' => $dir,
                'status' => $writable ? 'success' : 'error',
                'message' => $writable ? 
                    'Directory is writable' : 
                    "Directory {$dir} is not writable"
            ];
        }
    }
    
    public function createDirectories() {
        foreach ($this->requirements['directories'] as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    $this->addLog("Created directory: {$dir}");
                } else {
                    $this->addLog("Failed to create directory: {$dir}", 'error');
                }
            }
        }
    }
    
    public function createConfigFile() {
        $config = [
            'version' => '1.0.0',
            'installed' => date('Y-m-d H:i:s'),
            'max_file_size' => 50 * 1024 * 1024,
            'allowed_extensions' => ['php', 'enc', 'encoded'],
            'upload_dir' => 'uploads/',
            'output_dir' => 'decrypted/',
            'debug' => false,
            'auto_cleanup' => true,
            'cleanup_interval' => 3600, // 1 hour
        ];
        
        $configFile = 'config/config.php';
        $configContent = "<?php\n";
        $configContent .= "// ionCube Decryption Tool Configuration\n";
        $configContent .= "// Generated on " . date('Y-m-d H:i:s') . "\n\n";
        $configContent .= "return " . var_export($config, true) . ";\n";
        $configContent .= "?>";
        
        if (file_put_contents($configFile, $configContent)) {
            $this->addLog("Created configuration file: {$configFile}");
            return true;
        } else {
            $this->addLog("Failed to create configuration file: {$configFile}", 'error');
            return false;
        }
    }
    
    public function createSecurityFile() {
        $htaccess = "# Security for config directory\n";
        $htaccess .= "Order allow,deny\n";
        $htaccess .= "Deny from all\n";
        
        $htaccessFile = 'config/.htaccess';
        if (file_put_contents($htaccessFile, $htaccess)) {
            $this->addLog("Created security file: {$htaccessFile}");
            return true;
        } else {
            $this->addLog("Failed to create security file: {$htaccessFile}", 'error');
            return false;
        }
    }
    
    private function addLog($message, $type = 'info') {
        if (!isset($_SESSION['install_log'])) {
            $_SESSION['install_log'] = [];
        }
        
        $_SESSION['install_log'][] = [
            'time' => date('H:i:s'),
            'type' => $type,
            'message' => $message
        ];
    }
    
    public function getInstallLog() {
        return $_SESSION['install_log'] ?? [];
    }
    
    public function isInstalled() {
        return file_exists('config/config.php');
    }
    
    public function markInstalled() {
        $statusFile = 'config/installed.txt';
        return file_put_contents($statusFile, date('Y-m-d H:i:s'));
    }
}

$installer = new InstallManager();
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ar';

// Process installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $installer->createDirectories();
    $installer->createConfigFile();
    $installer->createSecurityFile();
    $installer->markInstalled();
    
    header('Location: install.php?lang=' . $lang . '&step=complete');
    exit;
}

$requirements = $installer->checkRequirements();
$canInstall = true;

// Check if installation is possible
foreach ($requirements as $category => $checks) {
    if (is_array($checks)) {
        if (isset($checks['status'])) {
            // Single check with status
            if ($checks['status'] === 'error') {
                $canInstall = false;
                break;
            }
        } else {
            // Multiple checks
            foreach ($checks as $check) {
                if (is_array($check) && isset($check['status']) && $check['status'] === 'error') {
                    $canInstall = false;
                    break 2;
                }
            }
        }
    }
}

$translations = [
    'ar' => [
        'title' => 'تثبيت أداة فك تشفير ionCube',
        'welcome' => 'مرحباً بكم في معالج التثبيت',
        'checking' => 'فحص المتطلبات...',
        'requirements' => 'متطلبات النظام',
        'install_btn' => 'تثبيت الأداة',
        'success' => 'نجح',
        'error' => 'خطأ',
        'warning' => 'تحذير',
        'complete' => 'تم التثبيت بنجاح',
        'go_to_tool' => 'الذهاب إلى الأداة',
        'install_log' => 'سجل التثبيت',
    ],
    'en' => [
        'title' => 'ionCube Decryption Tool Installation',
        'welcome' => 'Welcome to the Installation Wizard',
        'checking' => 'Checking requirements...',
        'requirements' => 'System Requirements',
        'install_btn' => 'Install Tool',
        'success' => 'Success',
        'error' => 'Error',
        'warning' => 'Warning',
        'complete' => 'Installation Complete',
        'go_to_tool' => 'Go to Tool',
        'install_log' => 'Installation Log',
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?></title>
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><i class="fas fa-cog"></i> <?php echo $t['title']; ?></h1>
            <p class="subtitle"><?php echo $t['welcome']; ?></p>
        </header>

        <main class="main-content">
            <?php if (isset($_GET['step']) && $_GET['step'] === 'complete'): ?>
                <div class="alert alert-success" style="display: block;">
                    <i class="fas fa-check-circle"></i> <?php echo $t['complete']; ?>
                </div>
                
                <div class="text-center">
                    <a href="index.php?lang=<?php echo $lang; ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> <?php echo $t['go_to_tool']; ?>
                    </a>
                </div>
                
                <?php if (!empty($installer->getInstallLog())): ?>
                    <div class="install-log">
                        <h3><?php echo $t['install_log']; ?></h3>
                        <div class="log-content">
                            <?php foreach ($installer->getInstallLog() as $log): ?>
                                <div class="log-entry log-<?php echo $log['type']; ?>">
                                    <span class="log-time"><?php echo $log['time']; ?></span>
                                    <span class="log-message"><?php echo $log['message']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="requirements-check">
                    <h2><?php echo $t['requirements']; ?></h2>
                    
                    <?php foreach ($requirements as $category => $checks): ?>
                        <div class="requirement-section">
                            <h3><?php echo ucfirst(str_replace('_', ' ', $category)); ?></h3>
                            
                            <?php if (is_array($checks) && isset($checks['status'])): ?>
                                <!-- Single check -->
                                <div class="requirement-item status-<?php echo $checks['status']; ?>">
                                    <i class="fas fa-<?php echo $checks['status'] === 'success' ? 'check' : ($checks['status'] === 'warning' ? 'exclamation-triangle' : 'times'); ?>"></i>
                                    <span class="req-name"><?php echo $checks['name']; ?></span>
                                    <span class="req-status"><?php echo $t[$checks['status']]; ?></span>
                                    <span class="req-message"><?php echo $checks['message']; ?></span>
                                </div>
                            <?php else: ?>
                                <!-- Multiple checks -->
                                <?php foreach ($checks as $check): ?>
                                    <div class="requirement-item status-<?php echo $check['status']; ?>">
                                        <i class="fas fa-<?php echo $check['status'] === 'success' ? 'check' : ($check['status'] === 'warning' ? 'exclamation-triangle' : 'times'); ?>"></i>
                                        <span class="req-name"><?php echo $check['name']; ?></span>
                                        <span class="req-status"><?php echo $t[$check['status']]; ?></span>
                                        <span class="req-message"><?php echo $check['message']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="install-actions">
                    <?php if ($canInstall): ?>
                        <form method="post">
                            <button type="submit" name="install" class="btn btn-success">
                                <i class="fas fa-download"></i> <?php echo $t['install_btn']; ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-error" style="display: block;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $lang === 'ar' ? 'لا يمكن التثبيت بسبب أخطاء في المتطلبات' : 'Cannot install due to requirement errors'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <style>
        .requirements-check {
            margin-bottom: 30px;
        }
        
        .requirement-section {
            margin-bottom: 25px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .requirement-section h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 10px;
            background: white;
            border-radius: 5px;
            gap: 10px;
        }
        
        .requirement-item.status-success {
            border-left: 4px solid #27ae60;
        }
        
        .requirement-item.status-warning {
            border-left: 4px solid #f39c12;
        }
        
        .requirement-item.status-error {
            border-left: 4px solid #e74c3c;
        }
        
        .requirement-item i {
            width: 20px;
            text-align: center;
        }
        
        .status-success i {
            color: #27ae60;
        }
        
        .status-warning i {
            color: #f39c12;
        }
        
        .status-error i {
            color: #e74c3c;
        }
        
        .req-name {
            font-weight: bold;
            min-width: 150px;
        }
        
        .req-status {
            font-weight: bold;
            min-width: 80px;
        }
        
        .req-message {
            color: #666;
            flex: 1;
        }
        
        .install-actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .install-log {
            margin-top: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .log-content {
            max-height: 300px;
            overflow-y: auto;
            background: white;
            padding: 15px;
            border-radius: 5px;
        }
        
        .log-entry {
            display: flex;
            align-items: center;
            padding: 5px 0;
            gap: 15px;
        }
        
        .log-time {
            font-family: monospace;
            color: #666;
            min-width: 70px;
        }
        
        .log-message {
            flex: 1;
        }
        
        .log-info {
            color: #2c3e50;
        }
        
        .log-error {
            color: #e74c3c;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</body>
</html>