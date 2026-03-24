import * as ftp from "basic-ftp";
import path from "path";
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function deploy() {
    const client = new ftp.Client();
    client.ftp.verbose = true;

    try {
        // 1. Deploy Frontend
        console.log("--- STARTING FRONTEND DEPLOY ---");
        await client.access({
            host: "167.235.1.40",
            user: "vue_front",
            password: "kDFb2pKhnkGmMeF5",
            secure: false
        });
        
        const localDist = path.join(__dirname, "dist");
        console.log("Uploading dist contents to /dist...");
        await client.uploadFromDir(localDist, "/dist");
        console.log("Frontend Upload finished successfully!");
        client.close();

        // 2. Deploy Backend
        console.log("\n--- STARTING BACKEND REVERSION DEPLOY ---");
        const backendClient = new ftp.Client();
        backendClient.ftp.verbose = true;
        await backendClient.access({
            host: "167.235.1.40",
            user: "vue_back",
            password: "KSsJrpyizBmf5mZA",
            secure: false
        });

        const localApi = path.join(__dirname, "fleetriteAPI");

        // CLEANUP REMOTE MODULAR FILES
        console.log("Cleaning up modular files from remote server...");
        try {
            await backendClient.cd("/app/Http/Controllers/Api/Reports");
            await backendClient.clearWorkingDir();
            await backendClient.cd("..");
            await backendClient.removeDir("Reports");
            console.log("Remote Reports folder deleted.");
        } catch (e) {
            console.log("Remote Reports folder not found or already deleted.");
        }

        try {
            await backendClient.cd("/app/Services");
            await backendClient.remove("GeneralInfoReportService.php");
            console.log("Remote GeneralInfoReportService.php deleted.");
        } catch (e) {
            console.log("Remote GeneralInfoReportService.php not found.");
        }

        // UPLOAD LEGACY BACKEND
        console.log("Uploading legacy Backend files...");
        await backendClient.cd("/");
        await backendClient.uploadFromDir(path.join(localApi, "app"), "app");
        await backendClient.uploadFromDir(path.join(localApi, "routes"), "routes");
        await backendClient.uploadFromDir(path.join(localApi, "config"), "config");

        console.log("Backend Reversion finished successfully!");
        backendClient.close();

        console.log("\n--- REVERSION COMPLETE ---");

    } catch (err) {
        console.error("Reversion Deployment Error: ", err);
    } finally {
        if (!client.closed) client.close();
    }
}

deploy();
