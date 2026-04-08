import axios from 'axios';

const payload = {
  username: 'coolex-admin',
  password: '123456'
};

axios.post('https://vue2.esoft-eg.com/api/login', payload)
  .then(response => {
    console.log('Login success, status:', response.status);
    console.log('Data:', response.data);
  })
  .catch(error => {
    if (error.response) {
      console.log('Login failed, status:', error.response.status);
      console.log('Data:', error.response.data);
    } else {
      console.error('Error:', error.message);
    }
  });
