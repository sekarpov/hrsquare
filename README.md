# HRSquare

Начальная инфраструктура сайта: PHP 8.4-FPM + Composer, PostgreSQL 17, Redis 7.4, Caddy с автоматическим HTTPS и приложение HRSquare. Ansible устанавливает Docker Engine, Buildx и Compose из официального APT-репозитория на Ubuntu/Debian (amd64/arm64), копирует проект в `/opt/hrsquare` и запускает контейнеры.

## Локальный запуск

Требуются Docker с Compose plugin, Python 3 и свободные порты 8080/8443.

```sh
make init        # один раз: .env с уникальными паролями, права 0600
make up
curl http://localhost:8080
make ps
```

Если `.env` уже создан, пропустите `make init`. `make down` останавливает контейнеры и сохраняет тома. `make logs` показывает логи. PostgreSQL, Redis и PHP-FPM не публикуют порты на хост.

## Сервер bi.sekarpov.online

Inventory уже настроен на `216.57.108.236`, SSH-пользователь `root`, порт 22. Пароли и SSH-ключи сервера в репозитории не хранятся. Для запуска нужен ваш SSH-ключ с доступом к серверу и Ansible на управляющей машине. При другом пользователе измените `ansible_user`; для sudo с паролем используйте `--ask-become-pass`.

1. Установите DNS A-запись `bi.sekarpov.online → 216.57.108.236`. Если есть AAAA, она должна вести на этот же сервер по IPv6, иначе удалите её.
2. Разрешите входящие TCP 80/443 и SSH в сетевом firewall провайдера. Проверьте, что 80/443 не заняты другим сайтом. Эта конфигурация рассчитана на выделенный сервер; для общего сервера требуется подключение к существующему reverse proxy.
3. Выполните:

```sh
cd provisioning
# hosts.yml уже подготовлен; для нового checkout:
# cp hosts.yml.dist hosts.yml
make check
make site
```

После успешного playbook откройте `https://bi.sekarpov.online`. Caddy выпускает и обновляет сертификаты автоматически, сертификаты сохраняются в томе `caddy_data`. Отдельный Certbot не нужен. При запуске только по IP задайте `site_address: http://:80` в inventory.

Повторный provisioning сохраняет `.env`, пароли и данные томов. Исходники `app/` и инфраструктура обновляются из текущего checkout. Playbook не обновляет принудительно уже скачанные образы: обновления планируются отдельно. Минорные теги позволяют получать патчи при явном `docker compose pull`; для строгой воспроизводимости после приёмки зафиксируйте digest образов.

## Деплой приложения

После первоначального provisioning обновления запускаются командой `make deploy`. Настройка SSH-доступа, откат через `make rollback` и Jenkins описаны в [инструкции деплоя](docs/DEPLOYMENT.md).

## Разработка и будущий сайт

```sh
make tools
```

Adminer: `http://localhost:8089`, система PostgreSQL, сервер `postgres`; логин и пароль из `.env`. Mailpit: `http://localhost:8025`, SMTP внутри Docker `mailpit:1025`. Эти инструменты доступны только на loopback хоста и включаются отдельно. На сервере используйте SSH-туннель, например `ssh -L 8089:127.0.0.1:8089 root@216.57.108.236`.

Для сборки фронтенда доступен `docker compose run --rm node npm ci`. Composer: `docker compose exec app composer --version`. Laravel находится в `app/`, его точка входа — `app/public/index.php`. Настройки БД/Redis и APP_KEY передаются через Compose; установка, сборка и миграции выполняются командами ниже.

Том `uploads` предназначен для файлов и доступен через `/storage`. Сейчас используется локальное файловое хранилище; S3, поисковый движок, парсеры, FTP, воркеры очередей и scheduler добавляются при появлении соответствующего кода. Они не нужны текущему MVP. В отличие от примера MySQL заменён на PostgreSQL, Adminer заменяет phpMyAdmin. Redis можно использовать для кэша, сессий и очередей; для критичных очередей нужна отдельная политика хранения и мониторинг.

## Резервные копии

```sh
make backup
```

На сервере provisioning включает ежедневный `pg_dump -Fc` в 02:15 по времени сервера. Копии в `/opt/hrsquare/backups`, хранение 14 дней, лог `/var/log/hrsquare-backup.log`. При неудачном dump незавершённый файл удаляется. Это локальная копия: настройте перенос на независимое хранилище, оповещения о сбоях и ротацию лога перед рабочей эксплуатацией. Файлы uploads и Redis в этот dump не входят; uploads копируйте отдельно согласованно с БД.

Восстановление в **пустую** БД (операция выполняется вручную):

```sh
# Сначала остановите запись приложения. Не восстанавливайте поверх рабочей БД.
docker compose exec -T postgres sh -ec 'pg_restore --exit-on-error --no-owner --no-privileges -U "$POSTGRES_USER" -d "$POSTGRES_DB"' < backups/postgres-YYYYMMDDTHHMMSSZ.dump
```

`POSTGRES_*` инициализируют БД только при первом создании тома. Для смены пароля существующей БД требуется SQL ALTER ROLE и согласованное обновление `.env`; редактирование `.env` само пароль БД не меняет.

## Ошибка монтирования uploads при запуске

Каталог `app/public/storage` должен существовать на хосте до запуска контейнеров: `/app` подключается только для чтения, поэтому Docker не может создать вложенную точку монтирования самостоятельно. Каталог сохранён в репозитории через `.gitkeep`, а Ansible дополнительно создаёт его перед запуском. Создание каталога только в Dockerfile недостаточно: bind mount `./app:/app:ro` скрывает содержимое `/app` из образа.

После обновления файлов повторите `cd provisioning && make site`. Данные томов и `.env` сохраняются; удалять тома не требуется.

## Проверки

`make check` проверяет Compose и синтаксис Ansible. `make up` ждёт healthcheck PostgreSQL, Redis, PHP-FPM и Caddy. PHP healthcheck проверяет соединение с PostgreSQL и авторизацию Redis. Проверяйте HTTP отдельно через `curl`: healthcheck Caddy проверяет сам proxy, а не публичный DNS/TLS.

Установка Docker следует [официальной документации](https://docs.docker.com/engine/install/ubuntu/); HTTPS и хранение сертификатов — [официальному образу Caddy](https://hub.docker.com/_/caddy).

## HRSquare Application

HRSquare — рабочее пространство для ведения кандидатов и оценки результатов и потенциала по 9-Box. Приложение установлено непосредственно в `app/`: Laravel 12, PHP 8.4, Vue 3 SPA, TypeScript, Vite, Vue Router, Pinia, Axios, PrimeVue и Tailwind CSS. Frontend находится в `app/resources/js`, готовые assets — `app/public/build`. Node используется для сборки и не работает постоянно. Backend читает DB/Redis environment из существующего Compose; главным конфигурационным файлом остаётся корневой `.env`, отдельный `app/.env` не требуется.

Форма Candidate Assessment использует готовые Card, Accordion, Message, RadioButton и поля PrimeVue; компоновка и оформление выполняются через Tailwind CSS 4 с официальным Vite-плагином. Классы Tailwind имеют префикс `tw:`, Preflight отключён, чтобы сохранить оформление существующих страниц и матрицы. Новые стандартные элементы следует брать из PrimeVue, а расположение, отступы и адаптивность задавать через Tailwind. Кастомный NineBoxMatrix сохраняет утверждённые цвета, тексты и mapping.


### Установка и запуск

```sh
make init      # создаёт или дополняет корневой .env, сохраняет пароли и APP_KEY
make up        # PHP build, Composer install, npm ci/build, запуск, миграции
make seed      # только для APP_ENV=local/demo/testing
```

Откройте `http://localhost:8080`. Для нового checkout нужны Docker/Compose, Python 3, Make и сетевой доступ для Composer/npm/образов. `make up` автоматически устанавливает зависимости и собирает SPA: отдельно запускать backend или Vite dev server не нужно. Повторный запуск не удаляет БД и не меняет существующие секреты. `make app-init` — alias `make up`.

Дополнительные команды:

```sh
make frontend  # npm ci + TypeScript check + production Vite build в Node profile
make migrate   # php artisan migrate --force
make seed      # idempotent demo seed (12 кандидатов, история из 1–3 оценок и черновики)
make test      # PHPUnit на PostgreSQL в отдельной database hrsquare_test
make check     # инфраструктура + Python/shell syntax
```

`make test` создаёт только отдельную тестовую БД **в существующем PostgreSQL**, без нового контейнера/сервера/тома. Тесты используют RefreshDatabase; защита запрещает запуск на рабочей БД. PHPUnit использует array cache/session, чтобы не затрагивать рабочие Redis-сессии. PHP-зависимости тестирования ставятся локальным `make up`; production Composer install использует `--no-dev`.

### Demo accounts

| Роль | Логин | Пароль |
|---|---|---|
| Рекрутер | recruiter | recruiter |
| Менеджер 1 | manager1 | manager1 |
| Менеджер 2 | manager2 | manager2 |

DemoSeeder запрещён при `APP_ENV=production`, даже с `--force`. Для production первоначальный рекрутер создаётся вручную с безопасным паролем:

```sh
docker compose exec app php artisan hrsquare:create-recruiter admin 'Имя администратора'
```

Пароль вводится скрыто; затем пользователей можно добавлять в интерфейсе. Для demo на отдельном стенде установите `APP_ENV=demo` в корневом `.env`, пересоздайте app через `make up`, затем `make seed`. Не используйте публичные demo-пароли для рабочих персональных данных.

### Роли и доступ

RECRUITER видит всех кандидатов и управляет кандидатами и пользователями. MANAGER также может создавать кандидатов; создавший менеджер автоматически добавляется в hiring managers на backend и сохраняет доступ к карточке. MANAGER видит и оценивает только кандидатов, где он назначен hiring manager. Редактирование и удаление кандидатов доступны рекрутеру. Ограничение применяется SQL scope и Laravel Policies, включая прямые запросы карточек и оценок (чужой кандидат возвращает 403). Любой пользователь с доступом к кандидату может создать новую оценку; редактировать и завершать черновик может только его автор. Пользователей выбирают searchable multi-select с серверным поиском и проверкой роли/активности.

Кандидат без оценок удаляется. Кандидат с историей не удаляется: используйте REJECTED. Пользователь со связями не удаляется: отключите isActive; смена роли связанного пользователя запрещена. Последний активный рекрутер защищён от удаления/отключения. Неактивный пользователь не может войти, а его существующая сессия отзывается при следующем запросе.

Auth использует Laravel session/cookie на том же origin, Redis sessions/cache, CSRF, HttpOnly, SameSite=Lax и Secure cookie на HTTPS. JWT и access token в localStorage отсутствуют. Axios получает CSRF cookie через `/api/csrf`, ошибки валидации приходят в camelCase `errors`, а 401/403/404/419 обрабатываются централизованно. Login rate-limited. Caddy — единственный reverse proxy; Laravel доверяет его forwarding headers через адрес непосредственного соединения, PHP-FPM не опубликован на хост.

### Оценки и расчёт

Каждая CandidateAssessment — отдельное интервью. DRAFT допускает пустые баллы; COMPLETED требует все шесть целых баллов 1–4. Для завершённых оценок нет обычного редактирования: исправление делается новой оценкой. Текущая оценка — последняя COMPLETED по `completed_at DESC, id DESC`; черновики не влияют на неё. Eloquent one-of-many выбирает её в SQL, eager loading и фильтры работают без загрузки всей истории или N+1.

RESULT = **сумма** taskScale, resultImpact, personalContribution / 3. POTENTIAL = **сумма** learningAgility, adaptability, initiative / 3. Уровень определяется по неокруглённому среднему, в DTO среднее округляется до двух знаков. Финальный и предварительный расчёты выполняет backend через AssessmentCalculator, AssessmentLevelCalculator и NineBoxCalculator; Vue не дублирует пороги или mapping.

**Временные бизнес-правила, требуют подтверждения HR:** LOW `[1,2.5)`, MEDIUM `[2.5,3.5)`, HIGH `[3.5,4]`. Пороги централизованы в `app/config/assessment.php`. Изменение порогов применяется к новым/редактируемым черновикам; сохранённые COMPLETED сохраняют исходный результат. Шкала HRSquare 1–4 является сдвинутым представлением исходной шкалы BI Group 0–3. Точные бизнес-пороги LOW/MEDIUM/HIGH требуют финального подтверждения HR. Вопросы, уточнения, сильные ответы и расшифровки шкалы централизованы в `app/config/assessment.php` и сформулированы для интервьюера о кандидате в третьем лице. Calibration signal и main risk требуют согласования с HR.

Миграция `2026_09_16_000002_shift_assessment_scale` атомарно переводит старые баллы на +1, сохраняет NULL, пересчитывает средние/уровни/9-Box, не меняя факты, авторов, статусы и audit timestamps. COMPLETED остаются immutable в обычном API. Перед production deploy выполните backup; web и app должны быть остановлены на время смены шкалы (существующий deploy делает это). Откат одного кода к старой шкале после этой миграции несовместим: требуется согласованный откат данных/миграции, обычный make rollback миграции не отменяет.

| POTENTIAL / RESULT | LOW | MEDIUM | HIGH |
|---|---|---|---|
| HIGH | M1 | S1 | B1 |
| MEDIUM | M2 | S2 | B2 |
| LOW | M3 | S3 | B3 |

Пример серверных фильтров:

```text
GET /api/candidates?status=HIRED&resultLevel=HIGH&potentialLevel=MEDIUM&nineBoxCell=B2
```

Также доступны search, position, city, company, division, project, managerId, recruiterId, page, perPage (1–100), sort, direction. Search ищет подстроку без учёта регистра, остальные текстовые фильтры — точное значение. Сортировка разрешена по fullName, position, city, company, division, project, status, createdAt, updatedAt. История пагинируется отдельно.

### Storage и deployment

`./app:/app:ro` сохранён. Только `app/storage` и `app/bootstrap/cache` подключены на запись для Laravel; PHP entrypoint создаёт runtime-каталоги и настраивает владельца www-data. Существующий том uploads по-прежнему подключён в `/app/public/storage`; storage:link не запускается. Для будущих файлов public disk направлен непосредственно на эту точку монтирования. Загрузки файлов в MVP нет.

`make deploy` и `make rollback` сохранены, подготовка дополнена Composer install, Node production build и `artisan migrate --force`. APP_KEY хранится в серверном корневом `.env` и не вращается. Demo seed при деплое не запускается. Откат возвращает код/assets, но не отменяет миграции БД — изменения схемы должны оставаться совместимыми. [Полная инструкция](docs/DEPLOYMENT.md).

### Проверки и документация библиотек

Unit/feature tests покрывают пороги, все 9 клеток, authentication/CSRF/rate limit, роли и прямой доступ, CRUD, неизменяемость completed, history/current, исключение drafts, фильтры current 9-Box, pagination и отсутствие N+1. UI рассчитан на desktop/tablet с горизонтальным scroll таблицы и перестроением форм.

Использованы [документация Laravel 12](https://laravel.com/docs/12.x) и [официальная настройка PrimeVue с Vite](https://primevue.org/vite/).
