import axios from 'axios';

async function testRouteDataSensors() {
  try {
    // Init session
    const initRes = await axios.post('https://vue2.esoft-eg.com/api/reports/modular/route-data-sensors/init');
    const hashId = initRes.data?.hash_id || initRes.data?.hashId || initRes.data?.hash;
    console.log('Init hash_id:', hashId);
    if (!hashId) {
      console.error('No hash_id returned');
      return;
    }
    // Generate report (provide minimal params)
    const generatePayload = {
      hash_id: hashId,
      imei: '123456789012345', // example IMEI, adjust as needed
      date_from: '2023-01-01 00:00',
      date_to: '2023-01-02 00:00'
    };
    const genRes = await axios.post('https://vue2.esoft-eg.com/api/reports/modular/route-data-sensors/generate', generatePayload);
    console.log('Generate response:', genRes.data);
    // Fetch data
    const fetchRes = await axios.get('https://vue2.esoft-eg.com/api/reports/modular/route-data-sensors/fetch', {
      params: { hash_id: hashId, page: 1, limit: 10 }
    });
    console.log('Fetch response:', fetchRes.data);
  } catch (err) {
    if (err.response) {
      console.error('Error status:', err.response.status);
      console.error('Error data:', err.response.data);
    } else {
      console.error('Error:', err.message);
    }
  }
}

testRouteDataSensors();
