// Pins functions.php to the installed Web Kilt (@cmu-web/kilt): its version
// and the integrity hashes of the files the theme loads from jsDelivr.
//   node scripts/kilt.cjs          rewrite the pin
//   node scripts/kilt.cjs --check  fail if the pin doesn't match
const fs = require('node:fs');
const path = require('node:path');

const kit = require('@cmu-web/kilt/package.json');
const file = path.join(__dirname, '../forbes-and-moreword/functions.php');
const theme = fs.readFileSync(file, 'utf8');
const problems = [];

let next = theme.replace(/(const FAM_KIT\s*=\s*')([^']+)(')/, (all, open, version, close) => {
	if (version !== kit.version) problems.push(`FAM_KIT is ${version}, not ${kit.version}`);
	return open + kit.version + close;
});
let found = 0;
next = next.replace(/('([^']+)'\s*=>\s*')(sha384-[^']+)(')/g, (all, open, name, hash, close) => {
	found++;
	const want = kit.integrity[name];
	if (!want) throw new Error(`${name} isn't in @cmu-web/kilt ${kit.version}`);
	if (hash !== want) problems.push(`${name} has an old hash`);
	return open + want + close;
});
if (found !== 3) throw new Error(`Expected 3 integrity hashes in functions.php, found ${found}`);

if (!problems.length) {
	console.log(`ok: functions.php is on Web Kilt ${kit.version}`);
} else if (process.argv.includes('--check')) {
	for (const problem of problems) console.error(`functions.php: ${problem}`);
	console.error('Run npm run kilt');
	process.exit(1);
} else {
	fs.writeFileSync(file, next);
	console.log(`functions.php: pinned to Web Kilt ${kit.version}`);
}
