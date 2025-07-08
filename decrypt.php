<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set memory limit and execution time for large files
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

class IonCubeDecryptor {
    private $uploadDir = 'uploads/';
    private $outputDir = 'decrypted/';
    private $maxFileSize = 52428800; // 50MB
    private $allowedExtensions = ['php', 'enc', 'encoded'];
    
    public function __construct() {
        $this->ensureDirectories();
    }
    
    private function ensureDirectories() {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function processRequest() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }
            
            $file = $_FILES['file'];
            
            // Validate file
            $this->validateFile($file);
            
            // Process file
            $result = $this->decryptFile($file);
            
            return $this->successResponse($result);
            
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    private function validateFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $this->getUploadErrorMessage($file['error']));
        }
        
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum limit (50MB)');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('Unsupported file type');
        }
        
        // Check if file is actually PHP/encrypted content
        $tempFile = $file['tmp_name'];
        $content = file_get_contents($tempFile, false, null, 0, 1024);
        
        if (!$this->isEncryptedContent($content)) {
            throw new Exception('File does not appear to be encrypted with ionCube');
        }
    }
    
    private function isEncryptedContent($content) {
        // Check for ionCube signatures
        $signatures = [
            'ionCube',
            'HR+cPu',
            'protected by ionCube',
            'This file is protected by ionCube',
            'ionCube24',
            'ionCube Encoder',
            'ionCube PHP Encoder'
        ];
        
        foreach ($signatures as $signature) {
            if (strpos($content, $signature) !== false) {
                return true;
            }
        }
        
        // Check for encoded patterns
        if (preg_match('/^<\?php\s+\/\*\s*ionCube/i', $content)) {
            return true;
        }
        
        // Check for base64 encoded patterns
        if (preg_match('/^<\?php\s+eval\s*\(/i', $content)) {
            return true;
        }
        
        return false;
    }
    
    private function decryptFile($file) {
        $startTime = microtime(true);
        
        // Generate unique filename
        $timestamp = time();
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        $uploadedFile = $this->uploadDir . $timestamp . '_' . $originalName . '.' . $extension;
        $decryptedFile = $this->outputDir . $timestamp . '_' . $originalName . '_decrypted.php';
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadedFile)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Read file content
        $content = file_get_contents($uploadedFile);
        if ($content === false) {
            throw new Exception('Failed to read uploaded file');
        }
        
        // Attempt to decrypt
        $decryptedContent = $this->attemptDecryption($content);
        
        // Save decrypted content
        if (file_put_contents($decryptedFile, $decryptedContent) === false) {
            throw new Exception('Failed to save decrypted file');
        }
        
        $endTime = microtime(true);
        
        // Generate preview
        $preview = $this->generatePreview($decryptedContent);
        
        // Clean up uploaded file
        unlink($uploadedFile);
        
        return [
            'originalFile' => $file['name'],
            'decryptedFile' => basename($decryptedFile),
            'originalSize' => $file['size'],
            'decryptedSize' => strlen($decryptedContent),
            'processingTime' => round($endTime - $startTime, 2),
            'preview' => $preview,
            'downloadUrl' => $decryptedFile
        ];
    }
    
    private function attemptDecryption($content) {
        // Try multiple decryption methods
        $methods = [
            'decryptBase64Method',
            'decryptEvalMethod',
            'decryptGzMethod',
            'decryptGenericMethod',
            'decryptAdvancedMethod'
        ];
        
        foreach ($methods as $method) {
            try {
                $result = $this->$method($content);
                if ($result && $this->isValidPHP($result)) {
                    return $result;
                }
            } catch (Exception $e) {
                // Continue to next method
                continue;
            }
        }
        
        // If no method worked, try manual extraction
        return $this->extractSourceCode($content);
    }
    
    private function decryptBase64Method($content) {
        // Look for base64 encoded content
        if (preg_match('/base64_decode\s*\(\s*["\']([^"\']+)["\']\s*\)/i', $content, $matches)) {
            $decoded = base64_decode($matches[1]);
            if ($decoded !== false) {
                return $decoded;
            }
        }
        
        // Multiple base64 layers
        $temp = $content;
        for ($i = 0; $i < 10; $i++) {
            if (preg_match('/base64_decode\s*\(\s*["\']([^"\']+)["\']\s*\)/i', $temp, $matches)) {
                $decoded = base64_decode($matches[1]);
                if ($decoded !== false) {
                    $temp = $decoded;
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        
        return $temp;
    }
    
    private function decryptEvalMethod($content) {
        // Look for eval() calls with encoded content
        if (preg_match('/eval\s*\(\s*(.+?)\s*\)\s*;/s', $content, $matches)) {
            $evalContent = $matches[1];
            
            // Try to decode the eval content
            if (preg_match('/base64_decode\s*\(\s*["\']([^"\']+)["\']\s*\)/i', $evalContent, $base64Matches)) {
                return base64_decode($base64Matches[1]);
            }
            
            // Try gzinflate
            if (preg_match('/gzinflate\s*\(\s*base64_decode\s*\(\s*["\']([^"\']+)["\']\s*\)\s*\)/i', $evalContent, $gzMatches)) {
                return gzinflate(base64_decode($gzMatches[1]));
            }
            
            // Try str_rot13
            if (preg_match('/str_rot13\s*\(\s*base64_decode\s*\(\s*["\']([^"\']+)["\']\s*\)\s*\)/i', $evalContent, $rotMatches)) {
                return str_rot13(base64_decode($rotMatches[1]));
            }
        }
        
        return false;
    }
    
    private function decryptGzMethod($content) {
        // Look for gzinflate/gzuncompress
        if (preg_match('/gzinflate\s*\(\s*base64_decode\s*\(\s*["\']([^"\']+)["\']\s*\)\s*\)/i', $content, $matches)) {
            return gzinflate(base64_decode($matches[1]));
        }
        
        if (preg_match('/gzuncompress\s*\(\s*base64_decode\s*\(\s*["\']([^"\']+)["\']\s*\)\s*\)/i', $content, $matches)) {
            return gzuncompress(base64_decode($matches[1]));
        }
        
        return false;
    }
    
    private function decryptGenericMethod($content) {
        // Generic pattern matching for encoded strings
        $patterns = [
            '/["\']([A-Za-z0-9+\/=]{100,})["\']/i',
            '/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*["\']([A-Za-z0-9+\/=]{50,})["\']/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $encodedString) {
                    $decoded = base64_decode($encodedString);
                    if ($decoded !== false && $this->isValidPHP($decoded)) {
                        return $decoded;
                    }
                }
            }
        }
        
        return false;
    }
    
    private function decryptAdvancedMethod($content) {
        // Advanced decryption for complex ionCube files
        $cleanContent = $content;
        
        // Remove PHP tags for processing
        $cleanContent = str_replace(['<?php', '<?', '?>'], '', $cleanContent);
        
        // Look for complex patterns
        if (preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*(.+?);/s', $cleanContent, $matches)) {
            $expression = $matches[1];
            
            // Try to evaluate safe expressions
            if (preg_match('/["\']([^"\']+)["\']/i', $expression, $stringMatches)) {
                $testString = $stringMatches[1];
                
                // Check if it's base64
                if (base64_decode($testString, true) !== false) {
                    $decoded = base64_decode($testString);
                    if ($this->isValidPHP($decoded)) {
                        return $decoded;
                    }
                }
            }
        }
        
        return false;
    }
    
    private function extractSourceCode($content) {
        // Last resort: try to extract readable PHP code
        $lines = explode("\n", $content);
        $extractedCode = "<?php\n";
        $extractedCode .= "// Partially recovered from ionCube encrypted file\n";
        $extractedCode .= "// Some functionality may be missing\n\n";
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip obvious encoded lines
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $line) && strlen($line) > 50) {
                continue;
            }
            
            // Skip eval and other suspicious functions
            if (preg_match('/eval\s*\(|base64_decode\s*\(|gzinflate\s*\(/i', $line)) {
                continue;
            }
            
            // Include lines that look like PHP code
            if (preg_match('/^(\$|class|function|if|else|for|while|return|echo|print)/i', $line)) {
                $extractedCode .= $line . "\n";
            }
        }
        
        return $extractedCode;
    }
    
    private function isValidPHP($content) {
        // Check if content looks like valid PHP
        if (empty($content)) {
            return false;
        }
        
        // Should contain PHP opening tag
        if (!preg_match('/<\?php|\<\?/', $content)) {
            return false;
        }
        
        // Should not be just encoded data
        if (preg_match('/^[A-Za-z0-9+\/=\s]+$/', $content)) {
            return false;
        }
        
        return true;
    }
    
    private function generatePreview($content, $maxLines = 50) {
        $lines = explode("\n", $content);
        $preview = array_slice($lines, 0, $maxLines);
        
        // Add truncation notice if needed
        if (count($lines) > $maxLines) {
            $preview[] = "\n... (truncated, showing first {$maxLines} lines of " . count($lines) . " total lines)";
        }
        
        return implode("\n", $preview);
    }
    
    private function getUploadErrorMessage($error) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File too large (exceeds php.ini limit)';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File too large (exceeds form limit)';
            case UPLOAD_ERR_PARTIAL:
                return 'File upload was interrupted';
            case UPLOAD_ERR_NO_FILE:
                return 'No file uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'No temporary directory';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Cannot write to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    private function successResponse($data) {
        return json_encode([
            'success' => true,
            'data' => $data['decryptedContent'] ?? file_get_contents($data['downloadUrl']),
            'originalFile' => $data['originalFile'],
            'decryptedFile' => $data['decryptedFile'],
            'originalSize' => $data['originalSize'],
            'decryptedSize' => $data['decryptedSize'],
            'processingTime' => $data['processingTime'],
            'preview' => $data['preview']
        ]);
    }
    
    private function errorResponse($message) {
        return json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
}

// Process the request
$decryptor = new IonCubeDecryptor();
echo $decryptor->processRequest();
?>