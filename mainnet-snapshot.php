<?php
// URL of the remote file
$remoteUrl = "https://blockproducer.ubitquityx.com/mainnet/latest-snapshot.bin.zst";

// Path to the local file
$localFile = "/home/USER/public_html/snapshots/mainnet/latest-snapshot.bin.zst";

// Initialize cURL session
$ch = curl_init($remoteUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FILETIME, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

// Execute cURL request
$response = curl_exec($ch);
if ($response === false) {
    die("Error: " . curl_error($ch));
}

// Get HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpCode !== 200) {
    die("Error: HTTP status code $httpCode");
}

// Get the last modified time of the remote file
$remoteFileTime = curl_getinfo($ch, CURLINFO_FILETIME);
curl_close($ch);

if ($remoteFileTime === -1) {
    die("Error: Could not retrieve the remote file's modification time.");
}

// Check if the local file exists
if (file_exists($localFile)) {
    // Get the local file's modification time
    $localFileTime = filemtime($localFile);

    // Compare the modification times
    if ($remoteFileTime <= $localFileTime) {
        echo "The local file is up-to-date. No download needed." . PHP_EOL;
        exit;
    }
}

// Download the remote file
$fp = fopen($localFile, 'w');
if (!$fp) {
    die("Error: Unable to open the local file for writing.");
}

$ch = curl_init($remoteUrl);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
if ($response === false) {
    die("Error during file download: " . curl_error($ch));
}

curl_close($ch);
fclose($fp);

// Set the local file's modification time to match the remote file's
if (touch($localFile, $remoteFileTime)) {
    echo "File downloaded and timestamp updated successfully." . PHP_EOL;
} else {
    echo "File downloaded, but failed to update the timestamp." . PHP_EOL;
}
