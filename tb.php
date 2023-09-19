
<?php
use Cloudflare\API\Endpoints\Workers;
use Telegram\Bot\Api;

$telegramBotToken = '6427724831:AAHrFaE7fwb-jgI3KnEaC3hEocDwDYW_xb8';
$cloudflareEmail = 'mahanmat10@gmail.com';
$cloudflareKey = 'd1ca6130e8031abee38e9b99a2a55f9c270db';

$telegram = new Api($telegramBotToken);
$workers = new Workers($cloudflareEmail, $cloudflareKey);

function getWorkersUsage() {
    global $workers;

    // Fetch all zones associated with your account
    $zones = $workers->listZones();

    // Fetch Workers details for each zone
    $workersUsage = [];
    foreach ($zones as $zone) {
        $zoneName = $zone->name;
        $workerDetails = $workers->listWorkers($zoneName);

        foreach ($workerDetails as $worker) {
            $workersUsage[$zoneName][$worker->id] = $worker->usage;
        }
    }

    return $workersUsage;
}

function handleIncomingMessage($message) {
    global $telegram;

    switch ($message->text) {
        case '/usage':
            $usage = getWorkersUsage();
            $responseText = "";

            foreach ($usage as $zone => $workers) {
                $responseText .= "*{$zone}*:\n\n";

                foreach ($workers as $workerId => $workerUsage) {
                    $responseText .= "Worker ID: {$workerId}\n";
                    $responseText .= "Requests Served: {$workerUsage->last_request_count}\n";
                    $responseText .= "Bytes Served: {$workerUsage->last_request_bytes}\n\n";
                }
            }

            $telegram->sendMessage([
                'chat_id' => $message->chat->id,
                'text' => $responseText,
                'parse_mode' => 'MarkdownV2'
            ]);
            break;
    }
}

$telegram->setWebhook(['url' => 'https://your-domain.com/cloudflare_bot.php']);
$response = $telegram->getWebhookUpdates();

if ($response->has('message')) {
    handleIncomingMessage($response->getMessage());
}
