import * as ftp from "basic-ftp";
import path from "path";
import { fileURLToPath } from 'url';
import fs from "fs";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function deployModularReports() {
    const client = new ftp.Client();
    client.ftp.verbose = true;
    try {
        console.log("Connecting to Backend FTP...");
        await client.access({
            host: "167.235.1.40",
            user: "vue_back",
            password: "KSsJrpyizBmf5mZA",
            port: 45555,
            secure: false
        });
        
        console.log("Connected. Starting modular report backend deployment...");

        const rootDir = path.join(__dirname, "fleetriteAPI");
        
        // Files to upload
        const filesToUpload = [
            { local: "routes/api.php", remote: "routes/api.php" },
            { local: "config/cors.php", remote: "config/cors.php" },
            { local: "public/.htaccess", remote: "public/.htaccess" },
            { local: "public/debug_legacy_api.php", remote: "public/debug_legacy_api.php" },
            { local: "app/Services/ReportService.php", remote: "app/Services/ReportService.php" },
            { local: "app/Http/Controllers/Api/ReportController.php", remote: "app/Http/Controllers/Api/ReportController.php" },
            { local: "app/Http/Controllers/Api/Reports/CurrentPositionController.php", remote: "app/Http/Controllers/Api/Reports/CurrentPositionController.php" },
            { local: "app/Services/Reports/CurrentPositionReportService.php", remote: "app/Services/Reports/CurrentPositionReportService.php" }
        ];

        // Directories to scan and upload
        const directories = [
            { local: "app/Http/Controllers/Api/Reports", remote: "app/Http/Controllers/Api/Reports" },
            { local: "app/Services/Reports", remote: "app/Services/Reports" }
        ];

        // Process single files
        for (const file of filesToUpload) {
            const localPath = path.join(rootDir, file.local);
            if (fs.existsSync(localPath)) {
                console.log(`Uploading ${file.local} to ${file.remote}...`);
                await client.uploadFrom(localPath, file.remote);
            }
        }

        // Process directories
        for (const dir of directories) {
            const localDirPath = path.join(rootDir, dir.local);
            if (fs.existsSync(localDirPath)) {
                const files = fs.readdirSync(localDirPath);
                for (const file of files) {
                    if (file.endsWith('.php')) {
                        const localFilePath = path.join(localDirPath, file);
                        const remoteFilePath = `${dir.remote}/${file}`;
                        console.log(`Uploading ${localFilePath} to ${remoteFilePath}...`);
                        await client.uploadFrom(localFilePath, remoteFilePath);
                    }
                }
            }
        }

        console.log("Modular Backend Reports Uploaded successfully!");
    } catch (err) {
        console.error("Backend FTP Error: ", err);
    }
    client.close();
}

deployModularReports();
