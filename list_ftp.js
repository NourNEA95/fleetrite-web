import * as ftp from "basic-ftp";

async function list() {
    const client = new ftp.Client();
    try {
        await client.access({
            host: "167.235.1.40",
            user: "vue_back",
            password: "KSsJrpyizBmf5mZA",
            secure: false
        });
        console.log("Root content:");
        console.log(await client.list("/"));
        
        console.log("Checking inc_functions folder:");
        try {
            console.log(await client.list("public/fleetrite_nv_latest_version/func/inc_functions"));
        } catch (e) {
            console.log("No such folder or error: " + e.message);
        }
    } catch (err) {
        console.error("FTP Error: ", err);
    }
    client.close();
}
list();
