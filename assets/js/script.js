class IonCubeDecryptor {
    constructor() {
        this.fileInput = document.getElementById('fileInput');
        this.uploadArea = document.getElementById('uploadArea');
        this.fileInfo = document.getElementById('fileInfo');
        this.progressSection = document.getElementById('progressSection');
        this.actionButtons = document.getElementById('actionButtons');
        this.resultSection = document.getElementById('resultSection');
        this.decryptBtn = document.getElementById('decryptBtn');
        this.downloadBtn = document.getElementById('downloadBtn');
        
        this.selectedFile = null;
        this.decryptedContent = null;
        
        this.initEventListeners();
    }
    
    initEventListeners() {
        // File input change
        this.fileInput.addEventListener('change', (e) => {
            this.handleFileSelect(e.target.files[0]);
        });
        
        // Drag and drop
        this.uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.uploadArea.classList.add('dragover');
        });
        
        this.uploadArea.addEventListener('dragleave', () => {
            this.uploadArea.classList.remove('dragover');
        });
        
        this.uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            this.uploadArea.classList.remove('dragover');
            this.handleFileSelect(e.dataTransfer.files[0]);
        });
        
        // Decrypt button
        this.decryptBtn.addEventListener('click', () => {
            this.startDecryption();
        });
        
        // Download button
        this.downloadBtn.addEventListener('click', () => {
            this.downloadDecryptedFile();
        });
    }
    
    handleFileSelect(file) {
        if (!file) return;
        
        // Validate file
        if (!this.validateFile(file)) {
            return;
        }
        
        this.selectedFile = file;
        this.displayFileInfo(file);
        this.showActionButtons();
    }
    
    validateFile(file) {
        const maxSize = 50 * 1024 * 1024; // 50MB
        const allowedExtensions = ['php', 'enc', 'encoded'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (file.size > maxSize) {
            this.showAlert('error', 'حجم الملف كبير جداً. الحد الأقصى 50MB / File size too large. Maximum 50MB');
            return false;
        }
        
        if (!allowedExtensions.includes(fileExtension)) {
            this.showAlert('error', 'نوع الملف غير مدعوم / Unsupported file type');
            return false;
        }
        
        return true;
    }
    
    displayFileInfo(file) {
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = this.formatFileSize(file.size);
        document.getElementById('fileType').textContent = file.type || 'Unknown';
        document.getElementById('fileStatus').textContent = 'جاهز للمعالجة / Ready for processing';
        document.getElementById('fileStatus').className = 'status-pending';
        
        this.fileInfo.style.display = 'block';
    }
    
    showActionButtons() {
        this.actionButtons.style.display = 'block';
        this.decryptBtn.disabled = false;
    }
    
    startDecryption() {
        if (!this.selectedFile) return;
        
        this.decryptBtn.disabled = true;
        this.downloadBtn.style.display = 'none';
        this.resultSection.style.display = 'none';
        
        this.updateStatus('processing', 'جاري فك التشفير... / Decrypting...');
        this.showProgress();
        
        // Create FormData
        const formData = new FormData();
        formData.append('file', this.selectedFile);
        formData.append('action', 'decrypt');
        
        // Start upload and decryption
        this.uploadAndDecrypt(formData);
    }
    
    uploadAndDecrypt(formData) {
        const xhr = new XMLHttpRequest();
        
        // Progress handler
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                this.updateProgress(percentComplete);
            }
        });
        
        // Success handler
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    this.handleDecryptionResponse(response);
                } catch (error) {
                    this.handleDecryptionError('خطأ في معالجة الاستجابة / Error processing response');
                }
            } else {
                this.handleDecryptionError('خطأ في الخادم / Server error');
            }
        });
        
        // Error handler
        xhr.addEventListener('error', () => {
            this.handleDecryptionError('خطأ في الشبكة / Network error');
        });
        
        // Send request
        xhr.open('POST', 'decrypt.php', true);
        xhr.send(formData);
    }
    
    handleDecryptionResponse(response) {
        this.hideProgress();
        
        if (response.success) {
            this.updateStatus('success', 'تم فك التشفير بنجاح / Decryption successful');
            this.decryptedContent = response.data;
            this.displayDecryptionResult(response);
            this.downloadBtn.style.display = 'inline-flex';
        } else {
            this.handleDecryptionError(response.message || 'خطأ في فك التشفير / Decryption error');
        }
        
        this.decryptBtn.disabled = false;
    }
    
    handleDecryptionError(message) {
        this.hideProgress();
        this.updateStatus('error', message);
        this.showAlert('error', message);
        this.decryptBtn.disabled = false;
    }
    
    displayDecryptionResult(response) {
        const resultContent = document.getElementById('resultContent');
        
        let html = '<div class="result-info">';
        html += '<h4>معلومات فك التشفير / Decryption Information</h4>';
        html += '<ul>';
        html += `<li><strong>اسم الملف الأصلي / Original File:</strong> ${response.originalFile}</li>`;
        html += `<li><strong>اسم الملف المفكوك / Decrypted File:</strong> ${response.decryptedFile}</li>`;
        html += `<li><strong>الحجم الأصلي / Original Size:</strong> ${this.formatFileSize(response.originalSize)}</li>`;
        html += `<li><strong>الحجم بعد فك التشفير / Decrypted Size:</strong> ${this.formatFileSize(response.decryptedSize)}</li>`;
        html += `<li><strong>وقت المعالجة / Processing Time:</strong> ${response.processingTime}s</li>`;
        html += '</ul>';
        html += '</div>';
        
        if (response.preview) {
            html += '<div class="code-preview">';
            html += '<h4>معاينة الكود / Code Preview</h4>';
            html += '<pre><code>' + this.escapeHtml(response.preview) + '</code></pre>';
            html += '</div>';
        }
        
        resultContent.innerHTML = html;
        this.resultSection.style.display = 'block';
    }
    
    downloadDecryptedFile() {
        if (!this.decryptedContent) return;
        
        const blob = new Blob([this.decryptedContent], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = this.selectedFile.name.replace(/\.(enc|encoded)$/, '') + '_decrypted.php';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    showProgress() {
        this.progressSection.style.display = 'block';
        this.updateProgress(0);
    }
    
    hideProgress() {
        this.progressSection.style.display = 'none';
    }
    
    updateProgress(percent) {
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        progressFill.style.width = percent + '%';
        progressText.textContent = Math.round(percent) + '%';
    }
    
    updateStatus(type, message) {
        const statusElement = document.getElementById('fileStatus');
        statusElement.textContent = message;
        statusElement.className = `status-${type}`;
    }
    
    showAlert(type, message) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        alertDiv.style.display = 'block';
        
        // Insert at the beginning of main content
        const mainContent = document.querySelector('.main-content');
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alertDiv.style.display = 'none';
            alertDiv.remove();
        }, 5000);
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    new IonCubeDecryptor();
});

// Add some utility functions for better UX
function showLoading(show = true) {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = show ? 'flex' : 'none';
    }
}

function createLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>معالجة الملف... / Processing file...</p>
        </div>
    `;
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;
    document.body.appendChild(overlay);
}