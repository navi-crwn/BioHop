// Refresh the vendored payment module (hop-pay) and the format list that goes with it.
//
//   node scripts/vendor-hop-pay.mjs <path-to-a-PayHop-checkout> [ref]      (ref defaults to origin/main)
//   node scripts/vendor-hop-pay.mjs --check
//
// Reads web/src/lib/pay/hop-pay.js of navi-crwn/PayHop at <ref> with `git show` (the working tree is not touched),
// writes it unchanged under a short provenance header to resources/js/vendor/hop-pay.js, then rebuilds
// resources/pay/formats.json: the format list comes from the module's own `formats` export, the form of each format
// from resources/pay/fields.json. A format that has no form there stops the script, so a new format never ships half
// done. Commit both outputs; nothing is fetched at run time. `--check` only verifies that the two committed files agree.
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { dirname, resolve } from 'node:path';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const OUT = resolve(root, 'resources/js/vendor/hop-pay.js');
const FORMATS = resolve(root, 'resources/pay/formats.json');
const FIELDS = resolve(root, 'resources/pay/fields.json');

export async function buildFormats() {
    const mod = await import(pathToFileURL(OUT).href + '?v=' + Date.now());
    const fields = JSON.parse(readFileSync(FIELDS, 'utf8'));
    const missing = Object.keys(mod.formats).filter((id) => !fields[id]);
    if (missing.length) throw new Error('hop-pay has format(s) without a form in resources/pay/fields.json: ' + missing.join(', '));
    const list = {};
    for (const [id, meta] of Object.entries(mod.formats)) {
        const f = fields[id];
        if (!f.fields.some((x) => x.key === f.payee)) throw new Error(id + ': payee field "' + f.payee + '" is not in its form');
        list[id] = { name: f.name, group: meta.group, confidence: meta.confidence, country: meta.country, currencies: meta.currencies, payee: f.payee, note: f.note, fields: f.fields };
    }
    return JSON.stringify(list, null, 4) + '\n';
}

async function main() {
    const args = process.argv.slice(2);
    if (args[0] === '--check') {
        if ((await buildFormats()) !== readFileSync(FORMATS, 'utf8')) {
            console.error('resources/pay/formats.json is out of date: run this script with a PayHop checkout');
            process.exit(1);
        }
        console.log('formats.json agrees with the vendored hop-pay');
        return;
    }
    const [dir, ref = 'origin/main'] = args;
    if (!dir) {
        console.error('usage: node scripts/vendor-hop-pay.mjs <PayHop checkout> [ref]  |  --check');
        process.exit(2);
    }
    const git = (...a) => execFileSync('git', ['-C', dir, ...a], { encoding: 'utf8' });
    const sha = git('rev-parse', '--verify', ref + '^{commit}').trim();
    const src = git('show', sha + ':web/src/lib/pay/hop-pay.js');
    const header = [
        '/*',
        ' * Vendored copy of hop-pay, the payment QR payload module of PayHop (https://github.com/navi-crwn/PayHop,',
        ' * web/src/lib/pay/hop-pay.js) at commit ' + sha + '.',
        ' * Licence: MIT, Copyright (c) 2026 Ivan Novskies (hel.ink), the same terms as BioHop (see LICENSE and CREDITS.md).',
        ' * Do not edit by hand: refresh it with `node scripts/vendor-hop-pay.mjs <PayHop checkout> [ref]`.',
        ' * It is bundled with the public page; nothing is loaded from pay.hel.ink or any other host.',
        ' */',
        '',
    ].join('\n');
    writeFileSync(OUT, header + src);
    writeFileSync(FORMATS, await buildFormats());
    console.log('vendored hop-pay from PayHop ' + sha + '; formats: ' + Object.keys(JSON.parse(readFileSync(FORMATS, 'utf8'))).join(', '));
}

if (process.argv[1] && pathToFileURL(resolve(process.argv[1])).href === import.meta.url) await main();
