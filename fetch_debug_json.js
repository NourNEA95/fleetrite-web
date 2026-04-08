import * as ftp from "basic-ftp";
import fs from "fs";

async function fetchDebugJson() {
    const client = new ftp.Client();
    try {
        await client.access({
            host: "167.235.1.40",
            user: "vue_back",
            password: "KSsJrpyizBmf5mZA",
            secure: false
        });
        
        console.log("Fetching storage/drives_stops_fail.json...");
        await client.downloadTo("fleetriteAPI/storage/drives_stops_fail.json", "storage/drives_stops_fail.json");
        console.log("File fetched successfully!");
    } catch (err) {
        console.log("Error fetching file: " + err.message);
    }
    client.close();
}

fetchDebugJson();
