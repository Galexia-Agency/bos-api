<?php
// Redis Setup
$redis = new Redis();
$redis->connect(
    'global-redis', // Host
    6379, // Port
);
$prefix = 'api.galexia.agency:';
$redis->setOption(Redis::OPT_PREFIX, $prefix);

register_shutdown_function(function () use ($redis) {
  $redis->close();
});

function setValueInRedis($key, $value)
{
  global $redis;
  $redis->set($key, $value);
}

function checkAndDeleteValueInRedis($key)
{
  global $redis;
  // Check if the key exists in the store
  if ($redis->exists($key)) {
    
    // Delete the key from the store
    $redis->del($key);
    
    // Return the true to indicate that the value was deleted
    return true;
  }
  
  // If the key doesn't exist, return false
  return false;
}