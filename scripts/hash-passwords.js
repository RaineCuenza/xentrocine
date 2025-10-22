// Generate Hashed Passwords for Seed Data
// Run this script to get bcrypt hashes for the seed.sql file
// Usage: node scripts/hash-passwords.js

const bcrypt = require('bcrypt');

const SALT_ROUNDS = 10;

const passwords = [
  { label: 'Admin', password: 'Admin123!' },
  { label: 'John Doe', password: 'password123' },
  { label: 'Jane Smith', password: 'password123' }
];

async function generateHashes() {
  console.log('Generating bcrypt hashes...\n');
  
  for (const item of passwords) {
    const hash = await bcrypt.hash(item.password, SALT_ROUNDS);
    console.log(`${item.label}:`);
    console.log(`  Password: ${item.password}`);
    console.log(`  Hash: ${hash}\n`);
  }
  
  console.log('Copy these hashes into your seed.sql file!');
}

generateHashes().catch(console.error);
