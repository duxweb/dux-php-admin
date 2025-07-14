<?php

require_once 'vendor/autoload.php';

use Core\App;

// 初始化应用
App::bootstrap();

echo "=== DuxLite 缓存系统测试 ===\n\n";

try {
    // 获取缓存实例（默认类型）
    $cache = App::cache();
    echo "✅ 缓存实例创建成功\n";
    
    // 测试基本缓存操作
    echo "\n--- 基本缓存操作测试 ---\n";
    
    // 设置缓存
    $result = $cache->set('test_key', 'Hello DuxLite Cache!', 3600);
    echo "设置缓存: " . ($result ? "✅ 成功" : "❌ 失败") . "\n";
    
    // 获取缓存
    $value = $cache->get('test_key');
    echo "获取缓存: " . ($value === 'Hello DuxLite Cache!' ? "✅ 成功" : "❌ 失败") . " - 值: $value\n";
    
    // 检查缓存是否存在
    $exists = $cache->has('test_key');
    echo "检查缓存存在: " . ($exists ? "✅ 存在" : "❌ 不存在") . "\n";
    
    // 删除缓存
    $deleted = $cache->delete('test_key');
    echo "删除缓存: " . ($deleted ? "✅ 成功" : "❌ 失败") . "\n";
    
    // 再次检查
    $existsAfterDelete = $cache->has('test_key');
    echo "删除后检查: " . (!$existsAfterDelete ? "✅ 已删除" : "❌ 仍存在") . "\n";
    
    // 测试批量操作
    echo "\n--- 批量缓存操作测试 ---\n";
    
    $multiData = [
        'user:1' => ['id' => 1, 'name' => 'Alice'],
        'user:2' => ['id' => 2, 'name' => 'Bob'],
        'user:3' => ['id' => 3, 'name' => 'Charlie']
    ];
    
    $setMultiResult = $cache->setMultiple($multiData, 3600);
    echo "批量设置缓存: " . ($setMultiResult ? "✅ 成功" : "❌ 失败") . "\n";
    
    $getMultiResult = $cache->getMultiple(['user:1', 'user:2', 'user:3']);
    echo "批量获取缓存: " . (count($getMultiResult) === 3 ? "✅ 成功" : "❌ 失败") . "\n";
    foreach ($getMultiResult as $key => $value) {
        echo "  $key: " . json_encode($value) . "\n";
    }
    
    $deleteMultiResult = $cache->deleteMultiple(['user:1', 'user:2', 'user:3']);
    echo "批量删除缓存: " . ($deleteMultiResult ? "✅ 成功" : "❌ 失败") . "\n";
    
    // 测试不同类型的缓存
    echo "\n--- 不同缓存类型测试 ---\n";
    
    try {
        $fileCache = App::cache('file');
        echo "文件缓存实例: ✅ 可用\n";
        
        $fileCache->set('file_test', 'File cache works!', 3600);
        $fileValue = $fileCache->get('file_test');
        echo "文件缓存测试: " . ($fileValue === 'File cache works!' ? "✅ 成功" : "❌ 失败") . "\n";
    } catch (Exception $e) {
        echo "文件缓存: ❌ 错误 - " . $e->getMessage() . "\n";
    }
    
    try {
        $redisCache = App::cache('redis');
        echo "Redis缓存实例: ✅ 可用\n";
        
        $redisCache->set('redis_test', 'Redis cache works!', 3600);
        $redisValue = $redisCache->get('redis_test');
        echo "Redis缓存测试: " . ($redisValue === 'Redis cache works!' ? "✅ 成功" : "❌ 失败") . "\n";
    } catch (Exception $e) {
        echo "Redis缓存: ❌ 错误 - " . $e->getMessage() . "\n";
    }
    
    // 测试缓存过期
    echo "\n--- 缓存过期测试 ---\n";
    
    $cache->set('expire_test', 'This will expire', 2);
    echo "设置2秒过期的缓存: ✅ 完成\n";
    
    $immediateValue = $cache->get('expire_test');
    echo "立即获取: " . ($immediateValue ? "✅ 存在" : "❌ 不存在") . "\n";
    
    echo "等待3秒...\n";
    sleep(3);
    
    $expiredValue = $cache->get('expire_test');
    echo "3秒后获取: " . ($expiredValue === null ? "✅ 已过期" : "❌ 仍存在") . "\n";
    
} catch (Exception $e) {
    echo "❌ 缓存测试失败: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== 缓存测试完成 ===\n";