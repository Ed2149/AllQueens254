<?php
class Cache {
    private static $cache_dir = '../cache/';
    private static $enabled = true;
    
    public static function init() {
        if (!file_exists(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0777, true);
        }
        
        // Enable cache only if directory is writable
        self::$enabled = is_writable(self::$cache_dir);
    }
    
    public static function get($key) {
        if (!self::$enabled) return false;
        
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            $data = file_get_contents($file);
            $data = unserialize($data);
            
            // Check if cache is still valid
            if ($data['expiry'] > time()) {
                return $data['content'];
            } else {
                // Cache expired, delete it
                unlink($file);
            }
        }
        return false;
    }
    
    public static function set($key, $content, $expiry = 3600) {
        if (!self::$enabled) return false;
        
        $file = self::$cache_dir . md5($key) . '.cache';
        $data = [
            'content' => $content,
            'expiry' => time() + $expiry,
            'created' => time()
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public static function delete($key) {
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
    
    public static function clear() {
        $files = glob(self::$cache_dir . '*.cache');
        $success = true;
        foreach ($files as $file) {
            if (file_exists($file)) {
                $success = $success && unlink($file);
            }
        }
        return $success;
    }
    
    public static function cleanup() {
        $files = glob(self::$cache_dir . '*.cache');
        $cleaned = 0;
        foreach ($files as $file) {
            if (file_exists($file)) {
                $data = file_get_contents($file);
                $data = unserialize($data);
                if ($data['expiry'] < time()) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        return $cleaned;
    }
}

// Initialize cache system
Cache::init();

// Optional: Add automatic cache cleanup (runs 1% of the time)
if (rand(1, 100) === 1) {
    Cache::cleanup();
}
?>