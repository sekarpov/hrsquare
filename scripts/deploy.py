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
if not re.fullmatch(r'[a-zA-Z0-9][a-zA-Z0-9.-]*', host):
    parser.error('HOST must be an IP address or hostname')
if not re.fullmatch(r'[a-z_][a-z0-9_-]*', user):
    parser.error('Invalid DEPLOY_USER')
if not port.isdecimal() or not 1 <= int(port) <= 65535:
    parser.error('Invalid PORT')
if not re.fullmatch(r'/opt/[a-zA-Z0-9_-]+', remote):
    parser.error('DEPLOY_PATH must be /opt/<project>')
ssh = ['ssh', '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=yes', '-p', port, f'{user}@{host}']
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
except (subprocess.CalledProcessError, OSError) as error:
    raise SystemExit(f'Deployment failed: {error}')
