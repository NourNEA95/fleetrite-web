import * as ftp from "basic-ftp";
import path from "path";
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function deploySingleFile() {
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
        
        console.log("Connected. Uploading specific modified file...");
        
        const localFile = path.join(__dirname, "fleetriteAPI", "app", "Services", "Reports", "DrivesStopsReportService.php");
        const remotePath = "/app/Services/Reports/DrivesStopsReportService.php";
        
        await client.uploadFrom(localFile, remotePath);
        
        console.log("Backend File Uploaded successfully!");
    } catch (err) {
        console.error("Backend FTP Error: ", err);
    }
    client.close();
}

deploySingleFile();
