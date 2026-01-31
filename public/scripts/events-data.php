<?php
$events = json_decode(file_get_contents('../../storage/events.json'), true);
echo 'const eventsData = ' . json_encode($events) . ';';
?>
