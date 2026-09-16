#!/usr/bin/env python3
"""Upload an explicit deployment payload and apply it over SSH."""
import argparse
import os
from pathlib import Path
import re
import shlex
import subprocess
import tempfile

parser = argparse.ArgumentParser()
parser.add_argument('action', choices=['deploy', 'rollback'], nargs='?', default='deploy')
args = parser.parse_args()
root = Path(__file__).resolve().parent.parent
host = os.environ.get('HOST', '216.57.108.236')
user = os.environ.get('DEPLOY_USER', 'deploy')
port = os.environ.get('PORT', '22')
remote = os.environ.get('DEPLOY_PATH', '/opt/hrsquare')
identity = os.environ.get('SSH_KEY', '')
if not re.fullmatch(r'[a-zA-Z0-9][a-zA-Z0-9.-]*', host):
    parser.error('HOST must be an IP address or hostname')
if not re.fullmatch(r'[a-z_][a-z0-9_-]*', user):
    parser.error('Invalid DEPLOY_USER')
if not port.isdecimal() or not 1 <= int(port) <= 65535:
    parser.error('Invalid PORT')
if not re.fullmatch(r'/opt/[a-zA-Z0-9_-]+', remote):
    parser.error('DEPLOY_PATH must be /opt/<project>')
ssh = ['ssh', '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=yes', '-p', port]
if identity:
    key_path = Path(identity).expanduser()
    if not key_path.is_file():
        parser.error(f'SSH_KEY does not exist: {key_path}')
    ssh += ['-i', str(key_path), '-o', 'IdentitiesOnly=yes']
ssh += [f'{user}@{host}']
# Upload happens under the same remote lock as build and activation.
command = ' '.join(shlex.quote(x) for x in ['sh', '-c', (root/'scripts/deploy-remote.sh').read_text(), 'hrsquare-deploy', remote, args.action])
try:
    if args.action == 'rollback':
        subprocess.run(ssh + [command], check=True)
    else:
        with tempfile.TemporaryFile() as payload:
            subprocess.run(['tar', '--exclude=.env', '--exclude=__pycache__', '--exclude=*.pyc', '-czf', '-', 'docker-compose.yml', 'docker', 'app', 'scripts'], cwd=root, stdout=payload, check=True)
            payload.seek(0)
            subprocess.run(ssh + [command], stdin=payload, check=True)
except subprocess.CalledProcessError as error:
    if error.returncode == 255:
        raise SystemExit(f'SSH connection to {user}@{host}:{port} failed. Check authorized_keys, SSH_KEY and known_hosts. Run make -C provisioning authorize-deploy to configure deployment access.')
    raise SystemExit(f'{args.action} failed (exit {error.returncode}); see output above.')
except OSError as error:
    raise SystemExit(f'Deployment failed: {error.strerror}')
