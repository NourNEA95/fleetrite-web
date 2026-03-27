import * as ftp from "basic-ftp";
import path from "path";
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function deploy() {
    const client = new ftp.Client();
    client.ftp.verbose = true;
    try {
        console.log("Connecting to API FTP...");
        await client.access({
            host: "167.235.1.40",
            user: "vue_back",
            password: "KSsJrpyizBmf5mZA",
            secure: false
        });

        console.log("Connected. Uploading API files from c:/xampp/htdocs/fleetrite-web/fleetriteAPI...");

        const localApiDir = "c:/xampp/htdocs/fleetrite-web/fleetriteAPI";

        // We'll upload file by file or use uploadFromDir 
        // Note: basic-ftp's uploadFromDir will upload everything. 
        // We should ideally filter, but for now we'll rely on what sftp.json suggested.
        // For a quick fix, let's just upload the changed files or the whole dir if small enough.
        // Actually, let's just use a simple upload for the specific files we changed to be safe.

        const filesToUpload = [
            ".htaccess",
            "app/Http/Controllers/Api/TrackingController.php",
            "app/Http/Controllers/Api/HistoryController.php",
            "app/Http/Controllers/Api/ObjectSettingsController.php",
            "app/Http/Controllers/Api/ReportController.php",
            "app/Http/Controllers/Api/Reports/GeneralInformationController.php",
            "app/Http/Controllers/Api/Reports/GeneralAccuracyController.php",
            "app/Http/Controllers/Api/Reports/GeneralMergedController.php",
            "app/Http/Controllers/Api/Reports/TravelSheetController.php",
            "app/Models/GsUser.php",
            "app/Models/GsObject.php",
            "app/Models/GsUserObject.php",
            "app/Models/ModularReportSession.php",
            "app/Traits/HandlesModularReportSessions.php",
            "app/Models/GsProfile.php",
            "app/Models/Report.php",
            "app/Models/GeneratedReport.php",
            "app/Services/ReportService.php",
            "app/Services/Reports/GeneralInformationService.php",
            "app/Services/Reports/GeneralAccuracyService.php",
            "app/Services/Reports/GeneralMergedService.php",
            "app/Services/Reports/TravelSheetService.php",
            "routes/api.php",
            "config/cors.php",
            "database/migrations/2026_03_14_015203_create_reports_table.php"
        ];

        for (const file of filesToUpload) {
            const localPath = path.join(localApiDir, file);
            const remotePath = "/" + file;
            const remoteDir = path.posix.dirname(remotePath);
            
            console.log(`Ensuring directory ${remoteDir} exists...`);
            await client.ensureDir(remoteDir);
            await client.cd('/');

            console.log(`Uploading ${localPath} to ${remotePath}...`);
            await client.uploadFrom(localPath, remotePath);
        }

        console.log("API files uploaded successfully!");

        console.log("Removing temporary log viewer for security...");
        await client.remove("/public/log_viewer.php").catch(e => console.log("Log viewer already gone or error: " + e.message));

    } catch (err) {
        console.error("API FTP Error: ", err);
    }
    client.close();
}

deploy();
