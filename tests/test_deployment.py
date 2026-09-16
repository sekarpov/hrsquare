"""Deployment regression tests using an isolated directory and mocked Docker."""
import fcntl
import io
import os
from pathlib import Path
import subprocess
import tarfile
import tempfile
import unittest

PROJECT = Path(__file__).resolve().parent.parent


class DeploymentTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='hrsquare-deploy-test-')
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.site = self.root / 'site'
        self.source = self.root / 'source'
        self.bin = self.root / 'bin'
        self.bin.mkdir()
        self.secret = 'APP_KEY=stable-secret\nPOSTGRES_PASSWORD=db-secret\n'
        for base, version in [(self.site, 'old'), (self.source, 'new')]:
            for directory in ['app/public', 'app/storage', 'app/bootstrap/cache', 'docker', 'scripts']:
                (base / directory).mkdir(parents=True, exist_ok=True)
            (base / 'app/public/index.php').write_text(version)
            (base / 'app/artisan').touch()
            (base / 'docker-compose.yml').write_text('services: {}\n')
            (base / 'scripts/app-prepare.sh').write_text('#!/bin/sh\nexit 0\n')
            (base / 'scripts/app-prepare.sh').chmod(0o755)
            (base / 'scripts/init.py').write_text('print("environment ready")\n')
        (self.site / '.env').write_text(self.secret)
        (self.site / '.env').chmod(0o600)
        docker = self.bin / 'docker'
        docker.write_text('#!/bin/sh\nprintf "%s\\n" "$*" >> "$MOCK_LOG"\nif [ "${FAIL_BUILD:-0}" = 1 ] && [ "$*" = "compose build app" ]; then exit 1; fi\nexit 0\n')
        docker.chmod(0o755)
        self.env = dict(os.environ, PATH=str(self.bin) + ':' + os.environ['PATH'], MOCK_LOG=str(self.root / 'commands'))

    def run_action(self, action='deploy', **extra):
        stream = io.BytesIO()
        if action == 'deploy':
            with tarfile.open(fileobj=stream, mode='w:gz') as archive:
                for item in ['app', 'docker', 'scripts', 'docker-compose.yml']:
                    archive.add(self.source / item, arcname=item)
        return subprocess.run(['sh', str(PROJECT / 'scripts/deploy-remote.sh'), str(self.site), action], input=stream.getvalue(), env=self.env | extra, capture_output=True)

    def test_deploy_and_rollback_preserve_secrets_and_permissions(self):
        result = self.run_action()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual((self.site / 'app/public/index.php').read_text(), 'new')
        self.assertEqual((self.site / '.env').read_text(), self.secret)
        self.assertEqual((self.site / 'app').stat().st_mode & 0o777, (self.source / 'app').stat().st_mode & 0o777)
        self.assertEqual((self.site / 'app/public/index.php').stat().st_mode & 0o777, (self.source / 'app/public/index.php').stat().st_mode & 0o777)
        with tarfile.open(self.site / '.deploy-previous.tar.gz') as archive:
            self.assertNotIn('.env', archive.getnames())
        result = self.run_action('rollback')
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual((self.site / 'app/public/index.php').read_text(), 'old')
        self.assertEqual((self.site / '.env').stat().st_mode & 0o777, 0o600)
        self.assertFalse(list(self.site.glob('.deploy-????????')))

    def test_prepare_makes_vendor_traversable_under_private_deploy_umask(self):
        prepare = self.site / 'scripts/app-prepare.sh'
        prepare.write_text((PROJECT / 'scripts/app-prepare.sh').read_text())
        vendor = self.site / 'app/vendor'
        vendor.mkdir(mode=0o700)
        self.site.joinpath('app/bootstrap').chmod(0o700)
        result = subprocess.run(['sh', '-c', 'umask 077; exec sh "$1" production', 'test', str(prepare)], env=self.env, capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(vendor.stat().st_mode & 0o777, 0o755)
        self.assertEqual(self.site.joinpath('app/bootstrap').stat().st_mode & 0o777, 0o755)
        self.assertEqual(self.site.joinpath('.env').stat().st_mode & 0o777, 0o600)
        commands = self.root.joinpath('commands').read_text()
        self.assertIn('chmod -R a+rX /app/vendor', commands)

    def test_build_failure_leaves_running_files_untouched(self):
        result = self.run_action(FAIL_BUILD='1')
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual((self.site / 'app/public/index.php').read_text(), 'old')
        self.assertNotIn('compose stop', (self.root / 'commands').read_text())
        self.assertFalse(list(self.site.glob('.deploy-????????')))

    def test_parallel_deployment_is_rejected(self):
        with open(self.site / '.deploy.lock', 'w') as lock:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            result = self.run_action()
            self.assertNotEqual(result.returncode, 0)
            self.assertIn(b'Another deployment', result.stderr)

    def test_missing_rollback_is_explicit(self):
        result = self.run_action('rollback')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn(b'No previous deployment', result.stderr)


class EnvironmentTest(unittest.TestCase):
    def test_initialization_is_idempotent_and_preserves_secrets(self):
        with tempfile.TemporaryDirectory() as directory:
            base = Path(directory)
            (base / '.env.example').write_text('POSTGRES_PASSWORD=\nREDIS_PASSWORD=\nSITE_ADDRESS=https://bi.sekarpov.online\n')
            for _ in range(2):
                subprocess.run(['python3', str(PROJECT / 'scripts/init.py')], cwd=base, check=True, capture_output=True)
                content = (base / '.env').read_text()
                if _ == 0:
                    original = content
                else:
                    self.assertEqual(content, original)
            self.assertIn('APP_KEY=base64:', content)
            self.assertIn('APP_ENV=production', content)
            self.assertIn('SESSION_SECURE_COOKIE=true', content)
            self.assertEqual((base / '.env').stat().st_mode & 0o777, 0o600)


if __name__ == '__main__':
    unittest.main()
