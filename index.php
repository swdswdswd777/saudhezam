<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set memory limit and execution time for large files
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Create necessary directories
$directories = ['uploads', 'decrypted', 'config'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Configuration
$config = [
    'max_file_size' => 50 * 1024 * 1024, // 50MB
    'allowed_extensions' => ['php', 'enc', 'encoded'],
    'upload_dir' => 'uploads/',
    'output_dir' => 'decrypted/',
];

// Language settings (Arabic/English)
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'ar';
$translations = [
    'ar' => [
        'title' => 'أداة فك تشفير ملفات ionCube PHP',
        'subtitle' => 'أداة متكاملة لفك تشفير ملفات PHP المشفرة بواسطة ionCube',
        'upload_file' => 'رفع الملف',
        'select_file' => 'اختر الملف المشفر',
        'decrypt_btn' => 'فك التشفير',
        'download' => 'تحميل',
        'file_info' => 'معلومات الملف',
        'status' => 'الحالة',
        'success' => 'نجح فك التشفير',
        'error' => 'خطأ',
        'processing' => 'جاري المعالجة...',
        'drag_drop' => 'اسحب وأفلت الملف هنا أو انقر للاختيار',
        'supported_formats' => 'الصيغ المدعومة: PHP, ENC, ENCODED',
        'max_size' => 'الحد الأقصى للحجم: 50MB',
    ],
    'en' => [
        'title' => 'ionCube PHP Decryption Tool',
        'subtitle' => 'Comprehensive tool for decrypting ionCube encrypted PHP files',
        'upload_file' => 'Upload File',
        'select_file' => 'Select encrypted file',
        'decrypt_btn' => 'Decrypt',
        'download' => 'Download',
        'file_info' => 'File Information',
        'status' => 'Status',
        'success' => 'Decryption successful',
        'error' => 'Error',
        'processing' => 'Processing...',
        'drag_drop' => 'Drag and drop file here or click to select',
        'supported_formats' => 'Supported formats: PHP, ENC, ENCODED',
        'max_size' => 'Maximum size: 50MB',
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
            <h1><i class="fas fa-unlock-alt"></i> <?php echo $t['title']; ?></h1>
            <p class="subtitle"><?php echo $t['subtitle']; ?></p>
            <div class="lang-switcher">
                <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
                <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            </div>
        </header>

        <main class="main-content">
            <div class="upload-section">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3><?php echo $t['drag_drop']; ?></h3>
                    <p><?php echo $t['supported_formats']; ?></p>
                    <p><?php echo $t['max_size']; ?></p>
                    <input type="file" id="fileInput" accept=".php,.enc,.encoded" hidden>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-file-upload"></i> <?php echo $t['select_file']; ?>
                    </button>
                </div>
            </div>

            <div class="file-info" id="fileInfo" style="display: none;">
                <h3><?php echo $t['file_info']; ?></h3>
                <div class="info-grid">
                    <div class="info-item">
                        <strong><?php echo $lang === 'ar' ? 'اسم الملف:' : 'File Name:'; ?></strong>
                        <span id="fileName"></span>
                    </div>
                    <div class="info-item">
                        <strong><?php echo $lang === 'ar' ? 'الحجم:' : 'Size:'; ?></strong>
                        <span id="fileSize"></span>
                    </div>
                    <div class="info-item">
                        <strong><?php echo $lang === 'ar' ? 'النوع:' : 'Type:'; ?></strong>
                        <span id="fileType"></span>
                    </div>
                    <div class="info-item">
                        <strong><?php echo $t['status']; ?>:</strong>
                        <span id="fileStatus" class="status-pending"><?php echo $lang === 'ar' ? 'في الانتظار' : 'Pending'; ?></span>
                    </div>
                </div>
            </div>

            <div class="progress-section" id="progressSection" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">0%</div>
            </div>

            <div class="action-buttons" id="actionButtons" style="display: none;">
                <button type="button" class="btn btn-success" id="decryptBtn">
                    <i class="fas fa-key"></i> <?php echo $t['decrypt_btn']; ?>
                </button>
                <button type="button" class="btn btn-secondary" id="downloadBtn" style="display: none;">
                    <i class="fas fa-download"></i> <?php echo $t['download']; ?>
                </button>
            </div>

            <div class="result-section" id="resultSection" style="display: none;">
                <div class="result-content">
                    <h3><?php echo $lang === 'ar' ? 'نتائج فك التشفير' : 'Decryption Results'; ?></h3>
                    <div id="resultContent"></div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <p><?php echo $lang === 'ar' ? 'أداة فك تشفير ionCube - جميع الحقوق محفوظة' : 'ionCube Decryption Tool - All rights reserved'; ?></p>
        </footer>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>