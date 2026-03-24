import * as ftp from "basic-ftp";
import path from "path";
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function deployEnv() {
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
        
        const localApi = path.join(__dirname, "fleetriteAPI");
        
        console.log("Uploading .env file to Backend...");
        await client.uploadFrom(path.join(localApi, ".env"), ".env");
        
        console.log(".env Upload finished successfully!");
    } catch (err) {
        console.error("FTP Error: ", err);
    }
    client.close();
}

deployEnv();
