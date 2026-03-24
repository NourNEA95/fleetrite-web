---
description: Deploy the Vue app from localhost to the FTP server (vue.esoft-eg.com)
---

This workflow rebuilds the Vue application into the `dist` folder and uploads it to the remote FTP server (`167.235.1.40`) inside the `/dist` folder using our custom `deploy.js` script.

// turbo-all
1. Build the new `dist` directory. Due to local PowerShell restrictions, we must use `cmd.exe`. Use the `run_command` tool to run: `cmd.exe /c "npm run build"` inside `c:\xampp\htdocs\fleetrite-web`.
2. Wait for the build command to complete successfully.
3. Use the `run_command` tool to deploy the new dist to FTP by running: `node deploy.js` inside `c:\xampp\htdocs\fleetrite-web`.
4. Reply to the user letting them know that the deployment was completely successful.
