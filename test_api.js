import axios from "axios";

async function testApi() {
    try {
        console.log("Testing vue2.esoft-eg.com modular route...");
        const res = await axios.post("https://vue2.esoft-eg.com/api/reports/modular/route-data-sensors/init");
        console.log("Response:", res.status, res.data);
    } catch (err) {
        console.error("Error:", err.response ? err.response.status : err.message);
        if (err.response) console.error("Body:", err.response.data);
    }
}
testApi();
