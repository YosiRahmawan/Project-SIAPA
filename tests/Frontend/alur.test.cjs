const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
function element(extra = {}) {
    return {hidden:false, value:'', children:[], listeners:{}, dataset:{}, classList:{toggle(){}},
        addEventListener(type, fn){this.listeners[type]=fn},
        append(...items){this.children.push(...items)},replaceChildren(){this.children=[]},
        querySelectorAll(){return []},setAttribute(){},removeAttribute(){},scrollIntoView(){}, ...extra};
}
const settle = () => new Promise(resolve => setImmediate(resolve));
function finance(response, fail = false) {
    const nodes = new Map();
    const proof = element({required:false});
    const method = element({value:'manual'});
    const selected = {value:'1'};
    const form = element({querySelector(){return selected}, reset(){method.value='manual';proof.value=''}});
    nodes.set('#payment-form',form); nodes.set('#payment-method',method);
    nodes.set('#payment-form input[name="tagihan"]:checked',selected);
    nodes.set('#proof-field',element({hidden:true})); nodes.set('#proof-field input',proof);
    nodes.set('#student-result',element()); nodes.set('#feedback',element());
    nodes.set('#payment-amount',element()); nodes.set('#bill-options',element());
    nodes.set('#student-search',element({elements:{nis:{value:'2410123'}}}));
    for (const key of ['total','paid','amount','left']) nodes.set('#summary-'+key,element());
    const context={document:{querySelector:s=>nodes.get(s)||null, querySelectorAll:()=>[],addEventListener(){},createElement:()=>element()},
        FormData:class {get(){return 'qris'}}, fetch:async()=>({ok:!fail,json:async()=>response}),window:{}};
    vm.createContext(context); vm.runInContext(fs.readFileSync('resources/js/keuangan.js','utf8'),context);
    return {nodes,form,method,proof};
}
test('QRIS selesai: Tunai tidak lagi mewajibkan bukti',async()=>{
    const {nodes,form,method,proof}=finance({status:'menunggu',id:1});
    method.value='qris'; method.listeners.change({target:method});
    assert.equal(proof.required,true);
    form.listeners.submit({preventDefault(){},currentTarget:form}); await settle();
    assert.equal(method.value,'manual'); assert.equal(proof.required,false);
    assert.equal(nodes.get('#proof-field').hidden,true);
});
test('QRIS sebagian: pilihan tagihan memakai sisa yang tersedia',async()=>{
    const {nodes}=finance({siswa:{nama:'Contoh',nis:'2410123',kelas:1,aktif:true},tagihan:[
        {id:1,jenis:'spp',periode:'2026-11',status:'belum',status_tampil:'menunggu_verifikasi',nominal:590000,dibayar:0,sisa_bisa_dibayar:490000},
        {id:2,status:'belum',status_tampil:'menunggu_verifikasi',sisa_bisa_dibayar:0}
    ]});
    const search=nodes.get('#student-search'); search.listeners.submit({preventDefault(){},currentTarget:search}); await settle();
    assert.equal(nodes.get('#payment-form').hidden,false);
    assert.equal(nodes.get('#payment-amount').max,490000);
    assert.equal(nodes.get('#payment-amount').value,490000);
    assert.equal(nodes.get('#bill-options').children.length,1);
});
test('dialog akun terkunci selama permintaan lalu menampilkan kredensial',async()=>{
    let finish;
    const nodes=new Map(); const close=element(); const create=element({dataset:{nis:'2410123'}});
    const result=element(); const dialog=element({querySelectorAll(){return [close]}});
    close.closest=()=>dialog; dialog.close=()=>{dialog.closed=true};
    nodes.set('#account-dialog',dialog); nodes.set('#account-create',create);nodes.set('#account-result',result);
    nodes.set('meta[name="csrf-token"]',{content:'testing'});
    const context={document:{querySelector:s=>nodes.get(s)||null,querySelectorAll:s=>s==='[data-close]'?[close]:[],createElement:()=>element()},
        sessionStorage:{getItem(){return null}},location:{reload(){}},fetch:()=>new Promise(resolve=>finish=resolve)};
    vm.createContext(context);vm.runInContext(fs.readFileSync('resources/js/portal.js','utf8'),context);
    const saving=create.listeners.click(); assert.equal(close.disabled,true);
    let prevented=false; dialog.listeners.cancel({preventDefault(){prevented=true}}); assert.equal(prevented,true);
    finish({ok:true,json:async()=>({username:'2410123',password_awal:'Contoh12345'})}); await saving;
    assert.equal(close.disabled,false);assert.equal(create.hidden,true);
    assert.equal(result.children[1].children[0].value,'Contoh12345');
    assert.equal(dialog.closed,undefined);
});

test('pencarian baru menghapus ringkasan dan feedback lama meski hasil gagal',async()=>{
    const {nodes}=finance({message:'Data siswa tidak ditemukan'},true);
    for(const key of ['total','paid','amount','left']) nodes.get('#summary-'+key).textContent='Rp590.000';
    nodes.get('#feedback').textContent='Pembayaran lama berhasil';
    const search=nodes.get('#student-search');search.listeners.submit({preventDefault(){},currentTarget:search});
    assert.equal(nodes.get('#feedback').hidden,true);
    assert.equal(nodes.get('#feedback').textContent,'');
    for(const key of ['total','paid','amount','left']) assert.equal(nodes.get('#summary-'+key).textContent,'Rp0');
    await settle();
    assert.equal(nodes.get('#payment-form').hidden,true);
    assert.equal(nodes.get('#feedback').hidden,false);
    assert.equal(nodes.get('#feedback').textContent,'Data siswa tidak ditemukan');
});
test('setelah bayar: tagihan siswa dimuat ulang dan pesan sukses tetap tampil',async()=>{
    const {nodes,form}=finance({status:'diterima',id:5,siswa:{nama:'Contoh',nis:'2410123',kelas:1,aktif:true},tagihan:[]});
    form.listeners.submit({preventDefault(){},currentTarget:form}); await settle(); await settle();
    assert.match(nodes.get('#feedback').textContent,/Pembayaran diterima/);
    assert.equal(nodes.get('#feedback').hidden,false);
    assert.notEqual(nodes.get('#student-result').textContent,'Pembayaran tersimpan. Cari siswa kembali untuk memuat tagihan terbaru.');
});
