"""Check the exported ZIP against its hash manifest and private-file exclusions."""
from pathlib import Path
import hashlib
import json
import zipfile

archive=Path(__file__).parents[1]/'releases/SmallGrocery-Latest.zip'
with zipfile.ZipFile(archive) as z:
    names={name.replace('\\','/'): name for name in z.namelist()}
    assert all(name.startswith('SmallGrocery/') for name in names)
    for name in names:
        relative=name.removeprefix('SmallGrocery/')
        assert relative!='config.local.php',name
        assert relative.split('/')[0] not in {'var','releases','.git'},name
        assert 'preview-access' not in name and not name.endswith('.log'),name
    for required in ['public/index.php','.htaccess','database/mini_grocery_fresh.sql','database/mini_grocery_update_002_email_otp.sql','scripts/create-owner.php','scripts/export-release.ps1','SETUP-XAMPP.md','vendor/phpmailer/LICENSE']:
        assert 'SmallGrocery/'+required in names,required
    manifest=json.loads(z.read(names['SmallGrocery/RELEASE-MANIFEST.json']))
    expected=set()
    for item in manifest['files']:
        path='SmallGrocery/'+item['path'].replace('\\','/')
        data=z.read(names[path])
        assert hashlib.sha256(data).hexdigest().upper()==item['sha256'].upper(),path
        assert len(data)==item['bytes'],path
        expected.add(path)
    assert expected|{'SmallGrocery/RELEASE-MANIFEST.json'}==set(names)
    for name in ['mini_grocery_fresh.sql','mini_grocery_update_002_email_otp.sql']:
        assert z.read(names['SmallGrocery/database/'+name])==(archive.parent/name).read_bytes(),name
print(f'PASS ZIP: {len(manifest["files"])} source files match their hashes; private/runtime data excluded; standalone SQL matches packaged SQL.')
