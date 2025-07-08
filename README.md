# أداة فك تشفير ionCube PHP | ionCube PHP Decryption Tool

أداة ويب متكاملة لفك تشفير ملفات PHP المشفرة بواسطة ionCube مع واجهة مستخدم سهلة ومتوافقة مع استضافة cPanel.

A comprehensive web tool for decrypting ionCube encrypted PHP files with an easy-to-use interface compatible with cPanel hosting.

## المميزات | Features

### العربية
- **واجهة مستخدم سهلة**: واجهة بسيطة وسهلة الاستخدام باللغتين العربية والإنجليزية
- **رفع متقدم للملفات**: دعم السحب والإفلات مع التحقق من صحة الملفات
- **فك تشفير متقدم**: دعم طرق متعددة لفك التشفير
- **متوافق مع cPanel**: سهولة التثبيت على استضافة cPanel
- **آمن**: حماية متقدمة للملفات وإعدادات الأمان
- **تنظيف تلقائي**: إزالة الملفات المؤقتة تلقائياً
- **معاينة الكود**: إمكانية معاينة الكود المفكوك
- **تحميل فوري**: تحميل الملفات المفكوكة مباشرة

### English
- **Easy User Interface**: Simple and user-friendly interface in both Arabic and English
- **Advanced File Upload**: Drag and drop support with file validation
- **Advanced Decryption**: Multiple decryption methods support
- **cPanel Compatible**: Easy installation on cPanel hosting
- **Secure**: Advanced file protection and security settings
- **Auto Cleanup**: Automatic temporary file removal
- **Code Preview**: Preview decrypted code functionality
- **Instant Download**: Direct download of decrypted files

## متطلبات النظام | System Requirements

- PHP 7.4 أو أحدث | PHP 7.4 or higher
- Apache/Nginx web server
- إضافات PHP المطلوبة | Required PHP extensions:
  - `curl`
  - `json`
  - `mbstring`
  - `zlib`
- دوال PHP المطلوبة | Required PHP functions:
  - `base64_decode`
  - `gzinflate`
  - `gzuncompress`
  - `str_rot13`

## التثبيت | Installation

### التثبيت التلقائي | Automatic Installation

1. **رفع الملفات | Upload Files**
   ```bash
   # رفع جميع الملفات إلى مجلد الويب
   # Upload all files to web directory
   ```

2. **تشغيل معالج التثبيت | Run Installation Wizard**
   ```
   http://yourdomain.com/install.php
   ```

3. **اتباع التعليمات | Follow Instructions**
   - فحص المتطلبات | Check requirements
   - إنشاء المجلدات | Create directories
   - تكوين الإعدادات | Configure settings

### التثبيت اليدوي | Manual Installation

1. **إنشاء المجلدات | Create Directories**
   ```bash
   mkdir uploads decrypted config assets/css assets/js
   chmod 755 uploads decrypted config
   ```

2. **تكوين الإعدادات | Configure Settings**
   ```php
   // config/config.php
   <?php
   return [
       'max_file_size' => 50 * 1024 * 1024,
       'allowed_extensions' => ['php', 'enc', 'encoded'],
       'upload_dir' => 'uploads/',
       'output_dir' => 'decrypted/',
       'debug' => false,
       'auto_cleanup' => true,
       'cleanup_interval' => 3600,
   ];
   ```

3. **تكوين الأمان | Security Configuration**
   ```apache
   # .htaccess
   # Already configured in the provided .htaccess file
   ```

## الاستخدام | Usage

### الاستخدام الأساسي | Basic Usage

1. **الوصول للأداة | Access Tool**
   ```
   http://yourdomain.com/
   ```

2. **رفع الملف | Upload File**
   - اختر الملف المشفر | Select encrypted file
   - أو استخدم السحب والإفلات | Or use drag and drop

3. **فك التشفير | Decrypt**
   - انقر على زر "فك التشفير" | Click "Decrypt" button
   - انتظر اكتمال المعالجة | Wait for processing to complete

4. **تحميل النتيجة | Download Result**
   - انقر على زر "تحميل" | Click "Download" button
   - احفظ الملف المفكوك | Save the decrypted file

### الإعدادات المتقدمة | Advanced Configuration

1. **الوصول للإعدادات | Access Settings**
   ```
   http://yourdomain.com/config.php
   ```

2. **تخصيص الإعدادات | Customize Settings**
   - حجم الملف الأقصى | Maximum file size
   - أنواع الملفات المسموحة | Allowed file types
   - إعدادات التنظيف | Cleanup settings

## الأمان | Security

### إعدادات الأمان | Security Settings

- **حماية المجلدات | Directory Protection**: منع الوصول المباشر للملفات الحساسة
- **التحقق من الملفات | File Validation**: فحص أنواع وأحجام الملفات
- **التنظيف التلقائي | Auto Cleanup**: إزالة الملفات المؤقتة
- **حماية من الهجمات | Attack Protection**: حماية من الهجمات الشائعة

### أفضل الممارسات | Best Practices

1. **تحديث كلمات المرور | Update Passwords**
   ```php
   // يُنصح بحماية الأداة بكلمة مرور
   // Recommend protecting the tool with password
   ```

2. **المراقبة | Monitoring**
   - مراقبة السجلات | Monitor logs
   - فحص الملفات المرفوعة | Check uploaded files

3. **النسخ الاحتياطي | Backup**
   - نسخ احتياطية منتظمة | Regular backups
   - نسخ الإعدادات | Backup configurations

## استكشاف الأخطاء | Troubleshooting

### مشاكل شائعة | Common Issues

1. **خطأ في رفع الملفات | File Upload Error**
   ```
   السبب: حجم الملف كبير جداً
   الحل: زيادة قيمة upload_max_filesize في php.ini
   
   Cause: File size too large
   Solution: Increase upload_max_filesize in php.ini
   ```

2. **خطأ في فك التشفير | Decryption Error**
   ```
   السبب: الملف غير مشفر بـ ionCube
   الحل: التأكد من أن الملف مشفر بـ ionCube
   
   Cause: File not encrypted with ionCube
   Solution: Ensure file is ionCube encrypted
   ```

3. **خطأ في الصلاحيات | Permission Error**
   ```bash
   chmod 755 uploads decrypted config
   chown www-data:www-data uploads decrypted config
   ```

### السجلات | Logs

- **سجل الأخطاء | Error Log**: فحص ملفات سجل الأخطاء
- **سجل التثبيت | Installation Log**: مراجعة سجل التثبيت
- **سجل النشاط | Activity Log**: مراقبة النشاط

## التطوير | Development

### هيكل المشروع | Project Structure

```
saudhezam/
├── index.php          # الواجهة الرئيسية | Main interface
├── decrypt.php        # معالج فك التشفير | Decryption processor
├── install.php        # معالج التثبيت | Installation wizard
├── config.php         # إدارة الإعدادات | Configuration manager
├── .htaccess         # إعدادات الأمان | Security settings
├── assets/           # الأصول | Assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── uploads/          # مجلد الرفع | Upload directory
├── decrypted/        # مجلد الإخراج | Output directory
└── config/           # مجلد الإعدادات | Configuration directory
```

### المساهمة | Contributing

1. Fork المشروع | Fork the project
2. إنشاء فرع جديد | Create a new branch
3. إضافة التغييرات | Add your changes
4. إرسال Pull Request | Submit a Pull Request

## الترخيص | License

هذا المشروع مرخص تحت رخصة MIT - راجع ملف LICENSE للتفاصيل.

This project is licensed under the MIT License - see the LICENSE file for details.

## الدعم | Support

- **GitHub Issues**: لتقديم التقارير والاقتراحات
- **Documentation**: مراجعة التوثيق المتكامل
- **Community**: الانضمام للمجتمع المطور

## إخلاء المسؤولية | Disclaimer

هذه الأداة مخصصة للاستخدام القانوني والتعليمي فقط. المستخدم مسؤول عن التأكد من أن استخدام الأداة يتوافق مع القوانين المحلية وحقوق الملكية الفكرية.

This tool is intended for legal and educational use only. Users are responsible for ensuring that their use of the tool complies with local laws and intellectual property rights.