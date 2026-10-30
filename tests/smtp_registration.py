"""Exercise PHPMailer against a loopback-only SMTP fixture, never a real inbox."""
from pathlib import Path
import socketserver
import subprocess
import threading

messages=[]
class SMTP(socketserver.StreamRequestHandler):
    def handle(self):
        self.wfile.write(b'220 localhost test SMTP\r\n')
        while True:
            line=self.rfile.readline()
            if not line: return
            verb=line.decode(errors='replace').strip().split(' ',1)[0].upper()
            if verb in ('EHLO','HELO'):
                self.wfile.write(b'250-localhost\r\n250 AUTH LOGIN\r\n')
            elif verb=='AUTH':
                self.wfile.write(b'334 VXNlcm5hbWU6\r\n'); self.rfile.readline()
                self.wfile.write(b'334 UGFzc3dvcmQ6\r\n'); self.rfile.readline()
                self.wfile.write(b'235 Authentication accepted\r\n')
            elif verb=='DATA':
                self.wfile.write(b'354 End with dot\r\n')
                body=[]
                while True:
                    part=self.rfile.readline()
                    if part in (b'.\r\n',b''): break
                    body.append(part)
                messages.append(b''.join(body).decode(errors='replace'))
                self.wfile.write(b'250 Queued locally\r\n')
            elif verb=='QUIT':
                self.wfile.write(b'221 Bye\r\n'); return
            else: self.wfile.write(b'250 OK\r\n')

with socketserver.TCPServer(('127.0.0.1',0),SMTP) as server:
    worker=threading.Thread(target=server.serve_forever,daemon=True); worker.start()
    result=subprocess.run([r'C:\xampp\php\php.exe',str(Path(__file__).with_name('registration.php')),str(server.server_address[1])])
    server.shutdown()
    if result.returncode: raise SystemExit(result.returncode)
    assert len(messages)==1 and '123456' in messages[0] and 'recipient@example.test' in messages[0]
    print('PASS local SMTP fixture received the expected verification message')
