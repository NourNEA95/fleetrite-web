import axios from 'axios';

async function login() {
  const res = await axios.post('https://vue2.esoft-eg.com/api/login', {
    username: 'coolex-admin',
    password: '123456'
  });
  return res.data?.token;
}

async function testRouteDataSensors() {
  const token = await login();
  if (!token) {
    console.error('Login failed, no token');
    return;
  }
  const authHeader = { Authorization: `Bearer ${token}` };

  // Init session
  const initRes = await axios.post('https://vue2.esoft-eg.com/api/reports/modular/route-data-sensors/init', {}, { headers: authHeader });
  const hashId = initRes.data?.hash_id || initRes.data?.hashId || initRes.data?.hash;
  console.log('Init hash_id:', hashId);
  if (!hashId) {
    console.error('No hash_id returned');
    return;
  }

  // Generate report (example IMEI, adjust as needed)
  const generatePayload = {
    hash_id: hashId,
    imei: '123456789012345', // replace with a valid IMEI in your system
    date_from: '2023-01-01 00:00',
    date_to: '2023-01-02 00:00'
  };
  const genRes = await axios.post('https://vue2.esoft-eg.com/api/reports/modular/route-data-sensors/generate', generatePayload, { headers: authHeader });
  console.log('Generate response:', genRes.data);

  // Fetch data
  const fetchRes = await axios.get('https://vue2.esoft-eg.com/api/reports/modular/route-data-sensors/fetch', {
    headers: authHeader,
    params: { hash_id: hashId, page: 1, limit: 10 }
  });
  console.log('Fetch response:', fetchRes.data);
}

testRouteDataSensors();
