from pathlib import Path
import sys
out=Path(__file__).resolve().parent
sys.path.insert(0,str(out.parent/'v7'))
from build_v7 import B,C,run,blue_panel,students,add_student,users
run('file.new',name='SIAPA v11 • Administrasi sekolah')
run('page.rename',name='Admin • alur dari kiri ke kanan')
def screen(key,title,desc,menu):
    return B.shell(key,title,desc,len(B.frames)*1512,0,active=menu,role='Admin')
f,x,w=screen('ringkasan','Ruang administrasi','Tahun ajaran 2026/2027   •   Data contoh sekolah','Dashboard')
p=blue_panel(f,x,204,w,188,22,'Ringkasan siswa')
B.text(p,26,21,'DIREKTORI SEKOLAH',12,'#DEEAFF',True,400)
B.text(p,26,48,'312 siswa aktif',37,'#FFFFFF',True,520)
B.text(p,26,124,'Kelas 1–6   /   Kelola identitas siswa dan akses portal keluarga.',16,'#DBEEFF',w=900)
for i,(title,sub,action,dest,col) in enumerate([('Data siswa','Periksa nama, kelas, dan status siswa.','Buka direktori','admin','#E4F1FF'),('Akun portal','Eka Wulandari belum memiliki akun.','Tinjau siswa','detail','#DDF6ED'),('Pengguna staf','Kelola akses admin, keuangan, dan yayasan.','Kelola staf','pengguna','#FFF1DE')]):
    width=(w-36)/3
    card=B.box(f,x+i*(width+18),424,width,240,col,18)
    B.text(card,22,23,title,22,bold=True,w=width-44)
    B.text(card,22,79,sub,15,w=width-44)
    B.button(card,22,160,222,action,dest=dest)
students('admin',1512,0,True)
B.button(B.frames['admin'],1040,987,360,'Tinjau Eka Wulandari',dest='detail')
add_student(3024,0)
def form(key,title,desc,menu,heading,fields,buttons):
    f,x,w=screen(key,title,desc,menu)
    p=B.box(f,x,200,w,710)
    B.text(p,28,26,heading,22,bold=True,w=w-56)
    for i,(label,value) in enumerate(fields): B.field(p,28,99+106*i,690,label,value)
    for i,(label,dest) in enumerate(buttons): B.button(p,28+324*i,610,300,label,style='primary' if i==0 else 'secondary',dest=dest)
form('detail','Profil siswa','Eka Wulandari   /   NIS 2410127','Data siswa','Identitas dan akses portal',[('Nama lengkap','Eka Wulandari'),('Kelas','5 • Aktif'),('Akun portal','Belum dibuat')],[('Buat akun portal','akun'),('Ubah data siswa','edit-siswa'),('Kembali ke daftar','admin')])
form('edit-siswa','Ubah data siswa','NIS 2410127 merupakan identitas tetap siswa.','Data siswa','Identitas siswa',[('Nama lengkap','Eka Wulandari'),('Kelas','5'),('Status siswa','Aktif')],[('Simpan perubahan','detail'),('Batal','detail')])
form('akun','Buat akun portal','Akun ini memberi wali akses ke tagihan dan riwayat anaknya.','Data siswa','Konfirmasi akun Eka Wulandari',[('Username dari NIS','2410127'),('Sandi awal','Dibuat sistem dan ditampilkan sekali setelah konfirmasi.')],[('Konfirmasi pembuatan','hasil'),('Kembali','detail')])
form('hasil','Akun portal berhasil dibuat','Contoh hasil pembuatan akun untuk Eka Wulandari.','Data siswa','Serahkan akses kepada wali',[('Username','2410127'),('Sandi awal • contoh simulasi','Contoh7Wali'),('Login pertama','Wali wajib mengganti sandi awal.')],[('Kembali ke ringkasan','ringkasan')])
users(len(B.frames)*1512,0)
p=B.frames['pengguna']
B.button(p,1120,111,280,'Tambah pengguna',dest='tambah-staf')
B.button(p,1020,720,380,'Kelola akun Yosi Rahma',dest='edit-staf')
form('tambah-staf','Tambah pengguna staf','Buat akses sesuai tugas pengguna di sekolah.','Pengguna','Akun baru',[('Nama lengkap','Nama staf'),('Username unik','Huruf, angka, tanda hubung atau garis bawah'),('Peran','Pilih admin, petugas keuangan, atau yayasan'),('Sandi awal','Minimal 8 karakter, mengandung huruf dan angka')],[('Buat pengguna','pengguna'),('Batal','pengguna')])
form('edit-staf','Ubah pengguna staf','Username keuangan   /   Akun Yosi Rahma','Pengguna','Peran dan status akses',[('Nama lengkap','Yosi Rahma'),('Peran','Petugas Keuangan'),('Status akun','Aktif'),('Ganti sandi • opsional','Isi jika perlu mengatur ulang sandi')],[('Simpan perubahan','pengguna'),('Batal','pengguna')])
res=run('command.batch',label='Alur administrasi',commands=B.steps)
def node(ref):return res[int(ref['$ref'][1:])-1]['id']
ids={k:node(v) for k,v in B.frames.items()}
links=[]
for r,d in B.links:
    if B.steps[int(r['$ref'][1:])-1]['params'].get('name')=='Navigasi / Dashboard': d='ringkasan'
    if d in ids:links.append(dict(command='proto.interaction.add',params=dict(id=node(r),dest=ids[d],transition=dict(type='dissolve'))))
run('command.batch',label='Navigasi admin',commands=links)
run('proto.flow.add',start=ids['ringkasan'],name='Mulai • Admin sekolah')
run('file.saveAs',path=str(out/'siapa-admin.figcraft'))
run('file.export',ids=[ids['ringkasan']],format='png',scale=1,path=str(out/'ringkasan.png'))
run('view.zoomAt',x=720,y=540,width=1540,height=1160)
print(len(ids),'layar admin')
