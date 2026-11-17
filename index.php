<?php

// Configuration
$apiKey   = "h523hDtETbkJ3nSJL323hjYLXbCyDaRZ";
$clientId = 100;
$baseUrl  = "https://api.recruitment.shq.nz";

/**
 * Call API and return decoded JSON
 */
function callApi($url)
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        die("Failed to call API: " . htmlspecialchars($url));
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Invalid JSON Response: " . json_last_error_msg());
    }

    return $data;
}

// Get Domains
$domainsUrl = "{$baseUrl}/domains/{$clientId}?api_key={$apiKey}";
$domains = callApi($domainsUrl);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DNS Zones Report</title>
</head>
<body>

<h1>Business Systems International Domains</h1>

<h2>Domains & Zones</h2>

<table border=1>
    <tr>
        <th>Domain Name</th>
        <th>Zone Name</th>
        <th>Zone URI</th>
        <th>Zone ID</th>
    </tr>
	<?php // echo '<pre>'; print_r($domains); echo '</pre>'; exit; ?>

    <?php foreach ($domains as $domain): ?>

        <?php
        $domainName = $domain['name'] ?? '';

        if (!empty($domain['zones'])):
            foreach ($domain['zones'] as $zone):

                $zoneUri = $zone['uri'] ?? '';
                $zoneId  = basename($zoneUri);
        ?>

        <tr>
            <td><?= htmlspecialchars($domainName) ?></td>
            <td><?= htmlspecialchars($zone['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($zoneUri) ?></td>
            <td><?= htmlspecialchars($zoneId) ?></td>
        </tr>

        <?php
            endforeach;
        endif;
        ?>

    <?php endforeach; ?>

</table>

<h2>DNS Records</h2>

<table border=1>
    <tr>
        <th>Domain</th>
        <th>Zone</th>
        <th>Type</th>
        <th>Name</th>
    </tr>

<?php

$seenRecords = [];

foreach ($domains as $domain) {

    if (empty($domain['zones'])) {
        continue;
    }

    foreach ($domain['zones'] as $zone) {

        $zoneUri = $zone['uri'] ?? '';
        $zoneId = basename(parse_url($zoneUri, PHP_URL_PATH));

        if (empty($zoneId)) {
            continue;
        }

        $recordsUrl = "{$baseUrl}/zones/{$zoneId}?api_key={$apiKey}";

        $records = callApi($recordsUrl);
		//echo '<pre>'; print_r($records); echo '</pre>'; exit;
        
        if (isset($records['records'])) {
            $records = $records['records'];
        } elseif (isset($records['data'])) {
            $records = $records['data'];
        } elseif (isset($records['type'])) {
            $records = [$records];
        }

        if (!is_array($records)) {
            continue;
        }

        foreach ($records as $record) {

            if (!is_array($record)) {
                continue;
            }

            $type = $record['type'] ?? '';
            $name = $record['name'] ?? '';

            $key = ($domain['name'] ?? '') . '|' .
                   ($zone['name'] ?? '') . '|' .
                   $type . '|' .
                   $name;

            if (isset($seenRecords[$key])) {
                continue;
            }

            $seenRecords[$key] = true;

            ?>

            <tr>
                <td><?= htmlspecialchars($domain['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($zone['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($type) ?></td>
                <td><?= htmlspecialchars($name) ?></td>
            </tr>

            <?php
        }
    }
}

?>

</table>

</body>
</html>