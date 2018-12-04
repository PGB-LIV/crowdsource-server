<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

error_reporting(E_ALL);
ini_set('display_errors', true);

require_once '../vendor/autoload.php';

$memcache = new Memcached();
$memcache->addServer('localhost', 11211);

$stats = $memcache->getStats();

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$resultStats = $channel->queue_declare('ResultQueue', false, false, false, false);
$jobStats = $channel->queue_declare('JobQueue', false, false, false, false);
$workStats = $channel->queue_declare('WorkUnitQueue', false, false, false, false);

sleep(1);
$resultStats2 = $channel->queue_declare('ResultQueue', false, false, false, false);
$jobStats2 = $channel->queue_declare('JobQueue', false, false, false, false);
$workStats2 = $channel->queue_declare('WorkUnitQueue', false, false, false, false);

echo 'Jobs Preparing: ' . number_format($workStats[1]) . ' ('. number_format($workStats[1]-$workStats2[1]) .'/s)<br />';
echo 'Jobs Ready: ' . number_format($jobStats[1])  . ' ('. number_format($jobStats2[1]-$jobStats[1]) .'/s)<br />';
echo 'Jobs Processing: ' . number_format($stats['localhost:11211']['curr_items']) .'<br />';
echo 'Jobs Processed: ' . number_format($resultStats[1])  . ' ('. number_format($resultStats2[1]-$resultStats[1]) .'/s)<br />';

echo '<pre>';
print_r($stats);
var_dump($resultStats);
var_dump($jobStats);
var_dump($workStats);