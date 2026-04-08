import * as ftp from "basic-ftp";
import * as fs from "fs";

async function checkJS() {
    const client = new ftp.Client();
    try {
        await client.access({
            host: "167.235.1.40",
            user: "vue_front",
            password: "kDFb2pKhnkGmMeF5",
            secure: false
        });
        await client.downloadTo("/tmp/remote_bundle.js", "/assets/ReportViewerPage-C17jM2hl.js");
        const content = fs.readFileSync("/tmp/remote_bundle.js", "utf8");
        console.log("Searching for modular fetch string...");
        if (content.includes("/api/reports/modular/route-data-sensors/fetch")) {
            console.log("STRING FOUND! The bundle is correct.");
        } else {
            console.log("STRING NOT FOUND! The bundle is STALE.");
            // Print a snippet around where it should be
            const pos = content.indexOf("/api/reports/paginated");
            if (pos !== -1) {
                console.log("Legacy string found at pos " + pos);
                console.log("Snippet: " + content.substring(pos - 50, pos + 100));
            }
        }
    } catch (err) {
        console.error(err);
    }
    client.close();
}
checkJS();
