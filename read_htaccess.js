import * as ftp from "basic-ftp";
import * as fs from "fs";

async function readHtaccess() {
    const client = new ftp.Client();
    try {
        await client.access({
            host: "167.235.1.40",
            user: "vue_front",
            password: "kDFb2pKhnkGmMeF5",
            secure: false
        });
        try {
            await client.downloadTo("/tmp/remote_htaccess", "/.htaccess");
            console.log("Remote .htaccess content:");
            console.log(fs.readFileSync("/tmp/remote_htaccess", "utf8"));
        } catch (e) {
            console.log("No .htaccess found in root.");
        }
    } catch (err) {
        console.error(err);
    }
    client.close();
}
readHtaccess();
