pipeline {
    agent any
    options {
        timestamps()
        disableConcurrentBuilds()
    }
    parameters {
        booleanParam(name: 'DEPLOY', defaultValue: false, description: 'Deploy this checkout to bi.sekarpov.online')
    }
    environment {
        HOST = '216.57.108.236'
        PORT = '22'
        DEPLOY_USER = 'deploy'
        DEPLOY_PATH = '/opt/hrsquare'
    }
    stages {
        stage('Validate') {
            steps {
                sh 'python3 -m py_compile scripts/deploy.py scripts/init.py'
                sh 'sh -n scripts/deploy-remote.sh scripts/backup.sh'
            }
        }
        stage('Deploy') {
            when { expression { params.DEPLOY } }
            steps {
                sshagent(credentials: ['HRSQUARE_PRODUCTION_SSH']) {
                    sh 'make deploy'
                }
            }
        }
    }
}
