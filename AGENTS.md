## Rules
- Commit BEFORE every change: `git add . && git commit -m "..."`. Then make the change.
- Tag meaningful states: `git tag v<VERSION>-<description>`
- After each change: update AGENTS.md if workflow changed.
- Token is in conversation history — never commit, never log.
- Daily DB backup runs at 03:00 via Windows scheduler (fpromDBBackup).
- Before editing DB: always backup the affected table(s).
- FTP: 185.98.5.112, user: script, pass: Nf7-X2p-STR-ADc
- Current state: см. ниже #ProjectState

# Project State — fprom.kz SEO Automation

## Goal
Вывести интернет-магазин на OKay CMS (fprom.kz) в топ-1 Google через AI-автоматизацию.

## Infrastructure
- **Hosting**: Plesk + nginx, PHP 7.2.34 (нет mysqlnd, shell_exec заблокирован)
- **Domain**: fprom.kz (основной хостинга — fortuneprom.kz)
- **DB**: localhost, p-329887_h-37688_fprom1 / 5Ws!p3l6, prefix ok_
- **Path**: /var/www/vhosts/fortuneprom.kz/fprom.kz/
- **Git**: https://github.com/dragunovlab/fprom.kz.git (master)
- **PAT**: (в истории диалога — не коммитить)

## Done
- [x] robots.txt — Host без www, Disallow для служебных путей, Crawl-delay
- [x] 301 www→non-www в index.php (nginx недоступен)
- [x] Sitemap — исключены user/login, register, wishlist, comparison, password_remind (SiteMapHelper.php)
- [x] Главная — meta_title, meta_description оптимизированы (ok_lang_pages)
- [x] kontakty2 — visible=0 (скрыт дубль)
- [x] Ежедневный бэкап БД 03:00 — backup.php (ключ fprom_backup_2026_secret), хранит 14 копий
- [x] Windows scheduler: fpromDBBackup → https://fprom.kz/backup.php?key=...
- [x] Исправлены URL-дубли категорий (53 UPDATE — генерация URL из названий, удаление дублирующихся сегментов)
- [x] 301 редиректы со старых URL на новые — cat_redirect_map.php (52 маппинга), include в index.php
- [x] Удалены ссылки на старые домены (fortune-prom.kz, fortuneprom.all.biz) с /o-kompanii, /oplata
- [x] SEO-описания для 483/483 категорий (шаблон: название + кол-во товаров + родитель + телефон)
- [x] Meta-descriptions заполнены для 72 категорий с пустыми meta_description
- [x] Git init + remote origin, теги: v0.0-initial-state, v0.1-seo-fixes, v0.2-agents-rules
- [x] AGENTS.md с правилами работы

## Screaming Frog Audit (03.07.2026, internal_all.csv)
- **Всего URL**: 492 (491×200, 1×404 — /contact)
- **Title**: 4 missing, ~150 short (<20 chars), 54 long (>70 chars)
- **Meta Description**: 5 missing, **321 short (<100 chars)**, 40 long (>170), 8 truncated (...)
- **H1**: 12 missing; массовые дубли (6× одинаковый H1, 4×, 3× и т.д.)
- **Duplicate titles**: множественные (6×, 4×, 3× и т.д.)
- **Битые URL (30 шт.)**: конкатенированные названия в URL (nasosybytovyenasosy..., oborudovaniekarernyj..., ustanovkigorizont..., bytovyenasosy, katkit...)
- **Canonical**: 3 страницы без canonical
- **Тонкий контент**: 0 страниц <100 слов (хорошо)
- **Статические страницы**: /contact (404/0 слов), /faq (0 desc), /sertifikaty (0 desc), /oplata (28 desc), /o-kompanii (29 desc)

## Приоритеты (Next Steps)
1. **Высокий**: Исправить 30 битых URL категорий — перегенерация URL + 301 редиректы (как делали с 53 дублями)
2. **Высокий**: Meta-descriptions для 321 страницы с короткими описаниями (<100 chars) — шаблонная генерация
3. **Высокий**: Title-теги для ~150 страниц с короткими title (<20 chars)
4. **Средний**: Исправить 12 missing H1 + убрать дубли H1/title
5. **Средний**: Микроразметка Product / BreadcrumbList для товаров
6. **Средний**: hreflang kk (сайт .kz — только ru)
7. **Низкий**: /contact — создать страницу или 301 на /o-kompanii
8. **Низкий**: canonical на 3 страницах без него

## Key Decisions
- Редирект www в index.php (не nginx)
- Мета-данные через ok_lang_pages (не ok_pages)
- Системные страницы исключены из sitemap в коде SiteMapHelper, а не visible=0
- Бэкап через PHP (mysqli), не shell (shell_exec заблокирован)
- URL категорий генерированы из названий (транслит)
- Описания — шаблон (не AI API), 453 за 1 запуск
- PAT получен через GitHub API, удалён из истории

## Relevant Files
- /fprom.kz/robots.txt
- /fprom.kz/index.php — www→non-www + cat_redirect_map.php
- /fprom.kz/cat_redirect_map.php — 301 на исправленные URL категорий
- /fprom.kz/Okay/Helpers/SiteMapHelper.php — исключения из sitemap
- /fprom.kz/backup.php — бэкап БД
- /fprom.kz/backups/ — директория с дампами
- C:\Users\admin\Desktop\script\ — локальный репозиторий
- C:\Users\admin\Desktop\script\internal_all.csv — аудит Screaming Frog
- C:\Users\admin\Desktop\script\audit_report.txt — детальный отчёт по аудиту
