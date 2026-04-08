import * as ftp from "basic-ftp";

async function listAssets() {
    const client = new ftp.Client();
    try {
        await client.access({
            host: "167.235.1.40",
            user: "vue_front",
            password: "kDFb2pKhnkGmMeF5",
            secure: false
        });
        console.log("Listing /assets contents:");
        console.log(await client.list("/assets/"));
        
        console.log("\nListing root contents (for index.html):");
        console.log(await client.list("/"));
    } catch (err) {
        console.error(err);
    }
    client.close();
}
listAssets();
