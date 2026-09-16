#!/usr/bin/env python3
from pathlib import Path
import secrets
import os
path = Path('.env')
if path.exists():
    raise SystemExit('.env уже существует; файл не изменён')
content = Path('.env.example').read_text()
for key in ('POSTGRES_PASSWORD', 'REDIS_PASSWORD'):
    content = content.replace(key + '=\n', key + '=' + secrets.token_hex(32) + '\n')
fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
with os.fdopen(fd, 'w') as stream:
    stream.write(content)
print('.env создан с уникальными паролями')
