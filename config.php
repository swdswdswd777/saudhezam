<?php
// Configuration management for ionCube Decryption Tool
session_start();

// Security check
if (!file_exists('config/installed.txt')) {
    header('Location: install.php');
    exit;
}

$config = include 'config/config.php';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ar';

// Process configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $newConfig = [
        'version' => $config['version'],
        'installed' => $config['installed'],
        'max_file_size' => (int)$_POST['max_file_size'] * 1024 * 1024,
        'allowed_extensions' => explode(',', str_replace(' ', '', $_POST['allowed_extensions'])),
        'upload_dir' => $_POST['upload_dir'],
        'output_dir' => $_POST['output_dir'],
        'debug' => isset($_POST['debug']),
        'auto_cleanup' => isset($_POST['auto_cleanup']),
        'cleanup_interval' => (int)$_POST['cleanup_interval'],
    ];
    
    $configFile = 'config/config.php';
    $configContent = "<?php\n";
    $configContent .= "// ionCube Decryption Tool Configuration\n";
    $configContent .= "// Updated on " . date('Y-m-d H:i:s') . "\n\n";
    $configContent .= "return " . var_export($newConfig, true) . ";\n";
    $configContent .= "?>";
    
    if (file_put_contents($configFile, $configContent)) {
        $config = $newConfig;
        $success = true;
    } else {
        $error = 'Failed to update configuration';
    }
}

// Clean up old files if auto cleanup is enabled
if ($config['auto_cleanup'] && rand(1, 100) === 1) {
    $this->cleanupOldFiles();
}

function cleanupOldFiles() {
    global $config;
    $directories = [$config['upload_dir'], $config['output_dir']];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) continue;
        
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file) && time() - filemtime($file) > $config['cleanup_interval']) {
                unlink($file);
            }
        }
    }
}

$translations = [
    'ar' => [
        'title' => 'إعدادات أداة فك تشفير ionCube',
        'general' => 'الإعدادات العامة',
        'file_settings' => 'إعدادات الملفات',
        'security' => 'الأمان',
        'maintenance' => 'الصيانة',
        'max_file_size' => 'الحد الأقصى لحجم الملف (MB)',
        'allowed_extensions' => 'امتدادات الملفات المسموحة',
        'upload_dir' => 'مجلد الرفع',
        'output_dir' => 'مجلد الإخراج',
        'debug' => 'وضع التصحيح',
        'auto_cleanup' => 'التنظيف التلقائي',
        'cleanup_interval' => 'فترة التنظيف (ثانية)',
        'save' => 'حفظ التغييرات',
        'back' => 'العودة للأداة',
        'system_info' => 'معلومات النظام',
        'php_version' => 'إصدار PHP',
        'server_software' => 'برنامج الخادم',
        'document_root' => 'مجلد الجذر',
        'current_time' => 'الوقت الحالي',
    ],
    'en' => [
        'title' => 'ionCube Decryption Tool Configuration',
        'general' => 'General Settings',
        'file_settings' => 'File Settings',
        'security' => 'Security',
        'maintenance' => 'Maintenance',
        'max_file_size' => 'Maximum File Size (MB)',
        'allowed_extensions' => 'Allowed File Extensions',
        'upload_dir' => 'Upload Directory',
        'output_dir' => 'Output Directory',
        'debug' => 'Debug Mode',
        'auto_cleanup' => 'Auto Cleanup',
        'cleanup_interval' => 'Cleanup Interval (seconds)',
        'save' => 'Save Changes',
        'back' => 'Back to Tool',
        'system_info' => 'System Information',
        'php_version' => 'PHP Version',
        'server_software' => 'Server Software',
        'document_root' => 'Document Root',
        'current_time' => 'Current Time',
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
            <div class="header-actions">
                <a href="index.php?lang=<?php echo $lang; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?php echo $t['back']; ?>
                </a>
            </div>
        </header>

        <main class="main-content">
            <?php if (isset($success)): ?>
                <div class="alert alert-success" style="display: block;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $lang === 'ar' ? 'تم حفظ الإعدادات بنجاح' : 'Configuration saved successfully'; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error" style="display: block;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="config-form">
                <div class="config-sections">
                    <div class="config-section">
                        <h3><i class="fas fa-sliders-h"></i> <?php echo $t['general']; ?></h3>
                        
                        <div class="form-group">
                            <label for="max_file_size"><?php echo $t['max_file_size']; ?>:</label>
                            <input type="number" id="max_file_size" name="max_file_size" 
                                   value="<?php echo $config['max_file_size'] / 1024 / 1024; ?>" 
                                   min="1" max="100" required>
                        </div>

                        <div class="form-group">
                            <label for="allowed_extensions"><?php echo $t['allowed_extensions']; ?>:</label>
                            <input type="text" id="allowed_extensions" name="allowed_extensions" 
                                   value="<?php echo implode(', ', $config['allowed_extensions']); ?>" 
                                   placeholder="php, enc, encoded" required>
                        </div>
                    </div>

                    <div class="config-section">
                        <h3><i class="fas fa-folder"></i> <?php echo $t['file_settings']; ?></h3>
                        
                        <div class="form-group">
                            <label for="upload_dir"><?php echo $t['upload_dir']; ?>:</label>
                            <input type="text" id="upload_dir" name="upload_dir" 
                                   value="<?php echo $config['upload_dir']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="output_dir"><?php echo $t['output_dir']; ?>:</label>
                            <input type="text" id="output_dir" name="output_dir" 
                                   value="<?php echo $config['output_dir']; ?>" required>
                        </div>
                    </div>

                    <div class="config-section">
                        <h3><i class="fas fa-shield-alt"></i> <?php echo $t['security']; ?></h3>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="debug" <?php echo $config['debug'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <?php echo $t['debug']; ?>
                            </label>
                        </div>
                    </div>

                    <div class="config-section">
                        <h3><i class="fas fa-broom"></i> <?php echo $t['maintenance']; ?></h3>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="auto_cleanup" <?php echo $config['auto_cleanup'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <?php echo $t['auto_cleanup']; ?>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="cleanup_interval"><?php echo $t['cleanup_interval']; ?>:</label>
                            <input type="number" id="cleanup_interval" name="cleanup_interval" 
                                   value="<?php echo $config['cleanup_interval']; ?>" 
                                   min="60" max="86400" required>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_config" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $t['save']; ?>
                    </button>
                </div>
            </form>

            <div class="system-info">
                <h3><i class="fas fa-info-circle"></i> <?php echo $t['system_info']; ?></h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <strong><?php echo $t['php_version']; ?>:</strong>
                        <span><?php echo phpversion(); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong><?php echo $t['server_software']; ?>:</strong>
                        <span><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong><?php echo $t['document_root']; ?>:</strong>
                        <span><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong><?php echo $t['current_time']; ?>:</strong>
                        <span><?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .config-form {
            margin-bottom: 30px;
        }

        .config-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .config-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .config-section h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
        }

        .form-actions {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .system-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #27ae60;
        }

        .system-info h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .config-sections {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>