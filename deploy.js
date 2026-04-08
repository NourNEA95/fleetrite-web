import * as ftp from "basic-ftp";
import path from "path";
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function deploy() {
    const client = new ftp.Client();
    client.ftp.verbose = true;
    try {
        console.log("Connecting to FTP...");
        await client.access({
            host: "167.235.1.40",
            user: "vue_front",
            password: "kDFb2pKhnkGmMeF5",
            port: 45555,
            secure: false
        });
        
        console.log("Connected. Uploading new dist contents to /dist...");
        
        const localDist = path.join(__dirname, "dist");
        
        await client.uploadFromDir(localDist, "/dist");
        
        console.log("Upload to root finished successfully!");
    } catch (err) {
        console.error("FTP Error: ", err);
    }
    client.close();
}

deploy();
