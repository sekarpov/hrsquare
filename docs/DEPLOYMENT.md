# Деплой HRSquare

Обычное обновление приложения запускается из корня checkout:

```sh
make deploy
# или явно:
make deploy HOST=216.57.108.236 PORT=22 DEPLOY_USER=deploy DEPLOY_PATH=/opt/hrsquare
```

Команда передаёт по SSH текущие файлы `app/`, `docker/`, `scripts/` и `docker-compose.yml`. Это содержимое рабочей директории, включая незакоммиченные изменения; в Jenkins используется checkout выбранного коммита. `.env`, Python cache, inventory, ключи и локальные backups не отправляются. Сборка PHP-образа выполняется Docker на сервере; локальный Docker, Ansible и registry для обычного деплоя не требуются. На сервере теперь также используется Python 3 и существующий Node build profile.

## Подготовка один раз

На локальной машине нужны Python 3, Make, OpenSSH client и GNU tar. На сервере должны быть выполнены provisioning, создан `.env`, открыты 80/443, настроен DNS `bi.sekarpov.online → 216.57.108.236`. Docker на сервере должен иметь доступ к Docker Hub, Debian APT и PECL для сборки образов. Команда рассчитана на Linux-сервер с GNU coreutils и `flock` (util-linux).

1. Создайте SSH-ключ для деплоя или используйте существующий:

```sh
ssh-keygen -t ed25519 -f ~/.ssh/hrsquare_deploy -C hrsquare-deploy
```

2. В `provisioning/hosts.yml` добавьте в `site.vars` **публичную** часть ключа:

```yaml
      vars:
        site_address: https://bi.sekarpov.online
        deploy_public_keys:
          - 'ssh-ed25519 AAAA... hrsquare-deploy'
```

Используйте всю строку из `~/.ssh/hrsquare_deploy.pub`. Приватный ключ на сервер копировать не нужно. Непустой `deploy_public_keys` управляет всем файлом `authorized_keys` пользователя deploy; перечислите все ключи, которым нужен доступ. Пустой список сохраняет существующий authorized_keys.

3. Обновите provisioning с административным SSH-доступом:

```sh
make -C provisioning site
# Если сервер уже подготовлен, достаточно настроить доступ без перезапуска сайта:
make -C provisioning authorize-deploy
```

Это создаёт пользователя `deploy`, добавляет его в группу Docker и передаёт ему владение `/opt/hrsquare`, сохраняя права `.env` 0600. Участник группы Docker фактически имеет административные возможности на сервере; выделяйте этот доступ доверенным операторам и Jenkins.

4. Добавьте приватный ключ в агент и проверьте ключ сервера:

```sh
ssh-add ~/.ssh/hrsquare_deploy
ssh -p 22 deploy@216.57.108.236 'docker compose version'
```

При первом подключении сравните fingerprint SSH host key с ключом сервера через консоль провайдера, затем подтвердите его сохранение в known_hosts. Скрипт использует `StrictHostKeyChecking=yes` и `BatchMode=yes`, поэтому не запрашивает пароль и не принимает неизвестный host key автоматически. При нескольких ключах можно задать `IdentityFile` и `IdentitiesOnly yes` для этого IP в `~/.ssh/config`.

## Что делает деплой

- Блокирует параллельный деплой и rollback через `.deploy.lock` на сервере.
- Распаковывает пакет в приватный временный каталог, подставляет серверный `.env`, проверяет Compose и собирает PHP-образ, устанавливает Composer dependencies (`--no-dev`) и собирает Vite assets через существующий Node profile. Если сборка не удалась, работающее приложение продолжает работать.
- Сохраняет предыдущие исходники и конфигурацию в `.deploy-previous.tar.gz`.
- Останавливает web/PHP, заменяет файлы и запускает Compose с ожиданием healthcheck. Во время переключения сайт кратковременно недоступен. При изменении конфигурации БД/Redis Compose также может пересоздать соответствующие контейнеры, сохранив тома.
- Проверяет права записи PHP в uploads и HTTP-запрос к `SITE_ADDRESS` из контейнера Caddy; для вашего HTTPS-домена запрос также проверяет сертификат и маршрут из Docker. Ошибка DNS, сетевого hairpin-доступа или TLS приводит к ошибке проверки.

`.env`, PostgreSQL, Redis, uploads, сертификаты и backups сохраняются. `COMPOSE_PROJECT_NAME` на сервере должен оставаться `hrsquare`, чтобы использовались те же тома. Исходники и scripts полностью заменяются; не редактируйте их только на сервере. Деплой устанавливает Composer dependencies, выполняет `npm ci && npm run build`, затем запускает `php artisan migrate --force` до запуска web. Перед изменением схемы БД выполните backup; rollback кода не отменяет миграции. DemoSeeder не запускается. APP_KEY дополняется в серверном `.env` один раз и сохраняется.

## Откат и диагностика

```sh
make rollback
```

Откат восстанавливает файлы последнего состояния перед деплоем, пересобирает PHP-образ и повторяет проверки. Доступен один предыдущий вариант; после неудачной активации используйте rollback **до следующего deploy**, чтобы сохранить нужную копию. Откат не восстанавливает БД и не отменяет миграции. Он также требует доступности зависимостей сборки; сохранение образов по release-тегам можно добавить при переходе к registry.

```sh
ssh deploy@216.57.108.236 'cd /opt/hrsquare && docker compose ps'
ssh deploy@216.57.108.236 'cd /opt/hrsquare && docker compose logs --tail=100 app web'
```

Если активация завершилась ошибкой, приложение может оставаться остановленным или частично запущенным; автоматического отката нет. Исправьте причину и повторите deploy либо выполните rollback. Не используйте `docker compose down -v`: это удалит данные.

## Jenkins

В репозитории есть `Jenkinsfile` с checkout средствами Jenkins, проверкой скриптов и запуском `make deploy` через SSH Agent plugin.

1. На Jenkins agent установите `make`, `python3`, GNU tar и OpenSSH client. В вашем Jenkins Dockerfile сейчас добавлен только docker-cli-compose; для Alpine-agent эти утилиты устанавливаются пакетом `make python3 tar openssh-client`. Изменение Jenkins-проекта и перезапуск Jenkins выполняются отдельно.
2. Установите Jenkins SSH Agent plugin. Создайте credential типа **SSH Username with private key**, ID `HRSQUARE_PRODUCTION_SSH`, username `deploy`, приватный ключ с доступом к HRSquare.
3. Добавьте проверенный host key `216.57.108.236` в `~/.ssh/known_hosts` пользователя Jenkins agent. Файл должен сохраняться между запусками; ключ можно получить через `ssh-keyscan`, но fingerprint нужно проверить независимо через консоль сервера.
4. Создайте Pipeline from SCM (или Multibranch Pipeline), задайте URL вашего Git-репозитория HRSquare и путь `Jenkinsfile`. Для приватного репозитория добавьте отдельный SCM credential.
5. Выполните первый запуск для появления параметра `DEPLOY`. Затем **Build with Parameters → DEPLOY=true**. По умолчанию выполняются только проверки. Запуск с DEPLOY обновляет production из выбранного checkout; предоставляйте доступ к запуску только доверенным пользователям и веткам. Для Multibranch храните Jenkinsfile с production credential только в доверенных ветках.

Пароли PostgreSQL/Redis Jenkins не нужны: используется существующий `.env` на сервере. Параллельные сборки одной job отключены, а серверная блокировка также защищает от одновременного запуска с локальной машины или другой job.

## Permission denied (publickey,password)

Эта ошибка возникает до выполнения скрипта на сервере: SSH не принял ключ для пользователя deploy. Добавьте публичный ключ в `deploy_public_keys` файла `provisioning/hosts.yml`, затем выполните `make -C provisioning authorize-deploy` с административным SSH-доступом. Эта команда настраивает пользователя, authorized_keys и владение файлами без пересборки или перезапуска приложения.

Если приватный ключ не загружен в SSH agent, укажите его явно:

```sh
make deploy SSH_KEY=~/.ssh/id_rsa
# Для отдельного ключа:
make deploy SSH_KEY=~/.ssh/hrsquare_deploy
```

Для ключа с passphrase сначала используйте `ssh-add`: деплой не запрашивает пароль интерактивно. Не передавайте `.pub` в SSH_KEY — нужен путь к приватному ключу. Для диагностики: `ssh -v -o IdentitiesOnly=yes -i ~/.ssh/id_rsa deploy@216.57.108.236 true`.

## Первый production-запуск приложения

После успешного `make deploy SSH_KEY=~/.ssh/id_rsa` создайте первого рекрутера через интерактивную Artisan-команду:

```sh
ssh -t deploy@216.57.108.236 "cd /opt/hrsquare && docker compose exec app php artisan hrsquare:create-recruiter admin 'Имя администратора'"
```

Введите безопасный пароль, затем войдите на https://bi.sekarpov.online. APP_ENV должен быть production, APP_URL — https://bi.sekarpov.online, SESSION_SECURE_COOKIE — true. Для существующего сервера с HTTPS initializer заполнит отсутствующие значения автоматически, а provisioning задаёт их явно.

При подготовке приложения vendor и bootstrap получают права чтения/прохода для PHP-FPM; серверный .env остаётся 0600. Перед миграциями deploy проверяет запуск Artisan от www-data, чтобы выявить ошибки прав, скрытые при запуске CLI от root.
