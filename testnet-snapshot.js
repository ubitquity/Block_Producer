const https = require('https');
const fs = require('fs');
const { exec } = require('child_process');

// URL of the remote file
const remoteUrl = 'https://snapshots.cindro.net/testnet/latest-snapshot.bin.zst';
// Path to the local file
const localFile = '/home/USER/public_html/snapshots/testnet/latest-snapshot.bin.zst';

// Function to get the remote file's modification time
const getRemoteFileTime = (url) => {
    return new Promise((resolve, reject) => {
        const req = https.request(url, { method: 'HEAD' }, (res) => {
            if (res.statusCode !== 200) {
                reject(new Error(`HTTP status code ${res.statusCode}`));
                return;
            }
            const lastModified = res.headers['last-modified'];
            if (!lastModified) {
                reject(new Error("Could not retrieve the remote file's modification time."));
                return;
            }
            resolve(new Date(lastModified).getTime());
        });
        req.on('error', reject);
        req.end();
    });
};

// Function to download the remote file
const downloadFile = (url, filePath) => {
    return new Promise((resolve, reject) => {
        const fileStream = fs.createWriteStream(filePath);
        https.get(url, (res) => {
            if (res.statusCode !== 200) {
                reject(new Error(`HTTP status code ${res.statusCode}`));
                return;
            }
            res.pipe(fileStream);
            fileStream.on('finish', () => {
                fileStream.close(resolve);
            });
        }).on('error', (err) => {
            fs.unlink(filePath, () => reject(err));
        });
    });
};

// Main function
const syncFile = async () => {
    try {
        const remoteFileTime = await getRemoteFileTime(remoteUrl);
        console.log('Remote file modification time:', new Date(remoteFileTime));

        if (fs.existsSync(localFile)) {
            const localFileTime = fs.statSync(localFile).mtimeMs;
            console.log('Local file modification time:', new Date(localFileTime));

            if (remoteFileTime <= localFileTime) {
                console.log('The local file is up-to-date. No download needed.');
                return;
            }
        }

        console.log('Downloading the remote file...');
        await downloadFile(remoteUrl, localFile);

        console.log('Updating the local file timestamp...');
        fs.utimesSync(localFile, new Date(), new Date(remoteFileTime));

        console.log('File downloaded and timestamp updated successfully.');
    } catch (error) {
        console.error('Error:', error.message);
    }
};

// Run the script
syncFile();
