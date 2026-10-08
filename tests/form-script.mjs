import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync(process.argv[2] || 'emisiones.php', 'utf8');
const scripts = [...source.matchAll(/<script>([\s\S]*?)<\/script>/g)];
assert.equal(scripts.length, 1, 'El formulario debe contener un único script');
assert.equal((source.match(/<\/html>/g) || []).length, 1, 'No debe haber cierres HTML duplicados');
assert.match(source, /<\/script><\/body><\/html>\s*$/, 'No debe quedar código visible después del HTML');
const script = new vm.Script(scripts[0][1]);
const events = {};
const profile = {selectedOptions: [{dataset: {cost: '800'}}], addEventListener: (name, handler) => {events.change = handler;}};
const nodes = {
  profile,
  'quoted-cost': {value: ''},
  price: {textContent: ''},
  'remaining-balance': {dataset: {balance: '2000'}, textContent: ''},
  'balance-warning': {hidden: true},
  'emission-form': {addEventListener: (name, handler) => {events.submit = handler;}},
  'submit-emission': {disabled: false, textContent: ''},
};
script.runInNewContext({document: {getElementById: id => nodes[id] || null}});
events.change();
assert.equal(nodes['quoted-cost'].value, '800');
assert.equal(nodes.price.textContent, '$8.00');
assert.equal(nodes['remaining-balance'].textContent, '$12.00');
assert.equal(nodes['balance-warning'].hidden, true);
profile.selectedOptions[0].dataset.cost = '3000';
events.change();
assert.equal(nodes['quoted-cost'].value, '3000');
assert.equal(nodes['remaining-balance'].textContent, '-$10.00');
assert.equal(nodes['balance-warning'].hidden, false);
events.submit();
assert.equal(nodes['submit-emission'].disabled, true);
assert.equal(nodes['submit-emission'].textContent, 'Enviando solicitud…');
script.runInNewContext({document: {getElementById: () => null}});
console.log('PASS: script válido, HTML sin código sobrante, costo/saldo actualizados y protección del botón.');
