import * as ftp from "basic-ftp";
import path from "path";
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function deploy() {
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
        
        console.log("Uploading modified Backend files...");
        
        // Upload App folder
        await client.uploadFromDir(path.join(localApi, "app"), "app");
        // Upload Routes folder
        await client.uploadFromDir(path.join(localApi, "routes"), "routes");
        // Upload Config folder
        await client.uploadFromDir(path.join(localApi, "config"), "config");
        // Upload Public folder (contains legacy PHP scripts)
        await client.uploadFromDir(path.join(localApi, "public"), "public");
        
        console.log("Backend Upload finished successfully!");
    } catch (err) {
        console.error("Backend FTP Error: ", err);
    }
    client.close();
}

deploy();
