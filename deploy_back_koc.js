import * as ftp from "basic-ftp";
import path from "path";
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function deployKocBackend() {
    const client = new ftp.Client();
    client.ftp.verbose = true;
    try {
        console.log("Connecting to Backend FTP...");
        await client.access({
            host: "167.235.1.40",
            user: "vue_back",
            password: "KSsJrpyizBmf5mZA",
            secure: false
        });
        
        console.log("Connected. Uploading KOC backend files...");
        
        const files = [
            {
                local: path.join(__dirname, "fleetriteAPI", "app", "Services", "Reports", "KocDirectorateReportService.php"),
                remote: "/app/Services/Reports/KocDirectorateReportService.php"
            }
        ];

        for (const file of files) {
            console.log(`Uploading ${file.local} to ${file.remote}...`);
            await client.uploadFrom(file.local, file.remote);
        }
        
        console.log("KOC Backend Files Uploaded successfully!");
    } catch (err) {
        console.error("Backend FTP Error: ", err);
    }
    client.close();
}

deployKocBackend();
