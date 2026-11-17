// Compare the theme's pin and integrity hashes with the pinned release manifest
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const manifest = JSON.parse(fs.readFileSync(0, 'utf8'));
const theme = fs.readFileSync(path.join(__dirname, '../forbes-and-moreword/functions.php'), 'utf8');
const version = theme.match(/const FAM_KIT\s*=\s*'([^']+)'/)[1];
assert.equal(version, manifest.version, 'The manifest must match the pinned release');
const files = [...theme.matchAll(/'([^']+)'\s*=>\s*'(sha384-[^']+)'/g)];
assert.equal(files.length, 3, 'Check both stylesheets and the script');
for (const [, file, integrity] of files) {
  assert.equal(integrity, manifest.integrity[file], `Wrong integrity for ${file}`);
}
console.log(`ok: all integrity hashes match Web Kilt ${version}`);
