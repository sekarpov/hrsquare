#!/usr/bin/env python3
"""Create/extend the root environment without rotating existing secrets."""
from pathlib import Path
import base64
import os
import secrets
path=Path('.env')
content=path.read_text() if path.exists() else Path('.env.example').read_text()
values={}
for line in content.splitlines():
    if '=' in line and not line.startswith('#'):
        key,value=line.split('=',1); values[key]=value
site=values.get('SITE_ADDRESS','http://localhost')
defaults={'POSTGRES_PASSWORD':secrets.token_hex(32),'REDIS_PASSWORD':secrets.token_hex(32),'APP_KEY':'base64:'+base64.b64encode(secrets.token_bytes(32)).decode(),'APP_ENV':'production' if site.startswith('https://') else 'local','APP_URL':site if site!='http://localhost' else 'http://localhost:8080','SESSION_SECURE_COOKIE':'true' if site.startswith('https://') else 'false'}
for key,value in defaults.items():
    if not values.get(key):
        if key in values:
            content='\n'.join((key+'='+value) if line.startswith(key+'=') else line for line in content.splitlines())+'\n'
        else: content=content.rstrip('\n')+'\n'+key+'='+value+'\n'
fd=os.open(path,os.O_WRONLY|os.O_CREAT|os.O_TRUNC,0o600)
with os.fdopen(fd,'w') as stream: stream.write(content)
path.chmod(0o600)
print('.env подготовлен; существующие пароли и APP_KEY сохранены')
