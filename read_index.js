import * as ftp from "basic-ftp";
import * as fs from "fs";

async function readIndex() {
    const client = new ftp.Client();
    try {
        await client.access({
            host: "167.235.1.40",
            user: "vue_front",
            password: "kDFb2pKhnkGmMeF5",
            secure: false
        });
        await client.downloadTo("/tmp/remote_index.html", "/index.html");
        console.log("Remote index.html content:");
        console.log(fs.readFileSync("/tmp/remote_index.html", "utf8"));
    } catch (err) {
        console.error(err);
    }
    client.close();
}
readIndex();
