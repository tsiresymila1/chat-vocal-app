require('dotenv').config();
const { execSync } = require('child_process');

const apiDocsUrl = process.env.API_DOCS;
if (!apiDocsUrl) {
  console.error('Error: API_DOCS environment variable is not set');
  process.exit(1);
}

try {
  execSync(`openapi-typescript ${apiDocsUrl} -o lib/api/openapi.d.ts`, { stdio: 'inherit' });
} catch (error) {
  console.error('Error generating OpenAPI types:', error);
  process.exit(1);
} 