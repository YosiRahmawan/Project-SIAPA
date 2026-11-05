from pathlib import Path
import sys
out=Path(__file__).resolve().parent
sys.path.insert(0,str(out.parent/'v7'))
from build_v7 import B,C,run,dashboard,bills,create_bill,payment,qris_form,verify,notice,receipts,report,students
run('file.new',name='SIAPA v10 • Ruang keuangan')
run('page.rename',name='Petugas keuangan • baca dari kiri')
x=0
def pos():
    global x
    here=x
    x+=1512
    return here
dashboard('dashboard',pos(),0)
dashboard('dashboard-ringkas',pos(),0,True)
students('siswa',pos(),0)
bills('tagihan',pos(),0)
create_bill(pos(),0)
payment(pos(),0)
qris_form(pos(),0)
verify(pos(),0)
notice('sukses','Pembayaran diterima','Penerimaan dan sisa tagihan siswa telah diperbarui.',pos(),0)
x-=920
notice('ditolak','Bukti ditolak','Alasan tersimpan. Tagihan menunggu pembayaran yang dapat dikonfirmasi.',pos(),0,'red')
x-=920
bills('tunggakan',pos(),0,True)
B.button(B.frames['tunggakan'],960,112,238,'Pengingat orang tua','bell',dest='pesan')
for auto in (False,True):
    key='pesan-api' if auto else 'pesan'
    f,cx,cw=B.shell(key,'Pengingat orang tua','Siapkan pesan pribadi berisi tautan ke tagihan dan halaman QRIS BSI.',pos(),0,active='Tunggakan')
    B.button(f,1150,110,245,'Lihat versi manual' if auto else 'Lihat versi API',dest='pesan' if auto else 'pesan-api')
    for i,(lab,val,sub) in enumerate([('Mendekati tempo','12 wali','Jatuh tempo dalam 3 hari'),('Sudah terlambat','8 wali','Masih memiliki sisa tagihan'),('Cara pengiriman','WhatsApp API' if auto else 'Draf WhatsApp','Status dari penyedia' if auto else 'Kirim per chat oleh petugas')]):
        w=(cw-32)/3
        p=B.box(f,cx+i*(w+16),204,w,145,['#DFF0FF','#FFF0DC','#DCF5ED'][i],20)
        B.text(p,22,18,lab,14,w=w-44); B.text(p,22,47,val,26,bold=True,w=w-44); B.text(p,22,103,sub,13,w=w-44)
    p=B.box(f,cx,376,cw,372)
    B.text(p,28,24,'Pratinjau untuk wali Citra Lestari',22,bold=True,w=650)
    B.text(p,28,72,'Jemputan Oktober   /   Kelas 3   /   Lewat jatuh tempo',15,C['muted'],w=720)
    bubble=B.box(p,28,124,cw-56,139,C['mint'],16)
    B.text(bubble,18,15,'Bapak/Ibu wali Citra, tagihan jemputan Rp275.000 jatuh tempo pada 31 Oktober. Mohon buka tagihan Citra di SIAPA untuk membayar melalui QRIS BSI.',17,w=cw-100)
    B.text(bubble,18,91,'Buka tagihan Citra di SIAPA ↗',15,C['green'],True,620)
    for j,many in enumerate((False,True)):
        label=(('Antrekan 20 pesan' if many else 'Antrekan 1 pesan') if auto else ('Siapkan 20 draf' if many else 'Siapkan 1 draf'))
        B.button(p,28+j*260,291,242,label,dest=key+str(j))
    for j,count in enumerate((1,20)):
        result=B.frame(key+str(j),f'{count} wali • '+('Antrean API' if auto else 'Draf manual'),pos(),0,600,420)
        x-=840
        B.text(result,30,35,'SIMULASI   /   PENGINGAT',13,C['green'],True,480)
        B.text(result,30,90,f'{count} pesan masuk antrean' if auto else f'{count} draf siap dibuka',27,bold=True,w=538)
        B.text(result,30,161,'Setiap wali menerima tautan tagihan anaknya. Status kirim mengikuti konfirmasi penyedia.' if auto else 'Petugas membuka draf tiap wali dan menekan Kirim di WhatsApp. Setiap draf memuat tautan tagihan anaknya.',17,w=530)
        B.button(result,30,320,260,'Kembali ke pengingat',dest=key)
receipts('rincian',pos(),0,'Petugas Keuangan')
report(pos(),0,'Petugas Keuangan')
res=run('command.batch',label='Layar keuangan sejajar',commands=B.steps)
def node(ref):return res[int(ref['$ref'][1:])-1]['id']
ids={k:node(v) for k,v in B.frames.items()}
links=[(r,d) for r,d in B.links if d in ids]
for a,b in [('dashboard','dashboard-ringkas'),('dashboard-ringkas','dashboard')]:links.append((B.hits[a]['toggle'],b))
run('command.batch',label='Alur petugas',commands=[dict(command='proto.interaction.add',params=dict(id=node(r),dest=ids[d],transition=dict(type='dissolve'))) for r,d in links])
run('proto.flow.add',start=ids['dashboard'],name='Mulai • Petugas keuangan')
run('file.saveAs',path=str(out/'siapa-keuangan.figcraft'))
run('file.export',ids=[ids['pesan']],format='png',scale=1,path=str(out/'pengingat.png'))
run('view.zoomAt',x=720,y=580,width=1540,height=1230)
print(len(ids),'frames')
