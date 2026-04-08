import fs from 'fs';
const content = fs.readFileSync('dist_old_index.js', 'utf8');
const match = content.match(/baseURL\s*:\s*([^,^\}]+)/g);
console.log(match);
