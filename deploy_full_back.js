import * as ftp from "basic-ftp";
import path from "path";
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function deploy() {
    const client = new ftp.Client();
    client.ftp.verbose = true;
    try {
        console.log("Connecting to API FTP (vue_back)...");
        await client.access({
            host: "167.235.1.40",
            user: "vue_back",
            password: "KSsJrpyizBmf5mZA",
            secure: false
        });

        const localApi = path.join(__dirname, "fleetriteAPI");

        console.log("Uploading App files...");
        await client.uploadFromDir(path.join(localApi, "app"), "app");
        
        console.log("Uploading Routes files...");
        await client.uploadFromDir(path.join(localApi, "routes"), "routes");

        console.log("Uploading specific legacy function file...");
        await client.uploadFrom(
            path.join(localApi, "public/fleetrite_nv_latest_version/func/inc_functions/reportGenerateMegeInfo_api.php"),
            "public/fleetrite_nv_latest_version/func/inc_functions/reportGenerateMegeInfo_api.php"
        );

        console.log("Backend Full Upload finished successfully!");
    } catch (err) {
        console.error("Full Backend FTP Error: ", err);
    }
    client.close();
}

deploy();
