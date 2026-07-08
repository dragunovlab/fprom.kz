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
- **Всего URL**: 492 (491×200, 1×404 — /contact — ИСПРАВЛЕН)
- **Title**: 101 коротких/пустых title — ИСПРАВЛЕНЫ (v0.5)
- **Meta Description**: 321 коротких + 1908 продуктов + 21 страница — ИСПРАВЛЕНЫ (v0.5)
- **H1**: 78 дублей/пустых name_h1 — ИСПРАВЛЕНЫ (v0.6)
- **Битые URL (27+15=42 шт.)**: конкатенированные/утроенные названия — ИСПРАВЛЕНЫ (v0.4 + v0.8)
- **rel=prev/next**: 90 страниц с next, страницы 2+ имеют prev ✅ (не баг, артефакт обхода)
- **Canonical**: 0 страниц без canonical ✅ (только /contact был 404, теперь 200)
- **Микроразметка**: Product, BreadcrumbList, Organization, WebSite — уже были в шаблонах ✅
- **hreflang**: ru единственный активный язык, en/uk выкл. kk не добавлен — нет контента на казахском
- **Тонкий контент**: 0 страниц <100 слов ✅
- **Статические страницы**: meta_descriptions и title заполнены для всех ✅

## Done (всего)
- [x] robots.txt — Host без www, Disallow, Crawl-delay
- [x] 301 www→non-www в index.php
- [x] Sitemap — исключены служебные пути
- [x] Главная — meta_title/description/h1 профессионально переписаны (ключевой запрос + CTA)
- [x] kontakty2 — visible=0
- [x] Ежедневный бэкап БД 03:00
- [x] 53 URL-дубля категорий исправлены + 301
- [x] 27 конкатенированных URL исправлены + 301 (v0.4)
- [x] 15 доп. битых URL исправлены (tripled/dup segments) + 301 (v0.8)
- [x] cat_redirect_map.php — 95 редиректов
- [x] index.php — 301 редиректы работают и по slug, и по /catalog/slug
- [x] Ссылки на старые домены удалены с /o-kompanii, /oplata
- [x] SEO-описания для 483/483 категорий
- [x] Meta-descriptions: 347 категорий + 1908 продуктов + 21 страница (обновлено)
- [x] Title-теги: 101 страница (52 категории + 40 товаров + 9 страниц)
- [x] H1: 78 фиксов (70 дублей + 8 missing)
- [x] /contact — включён (visible=1), отдаёт 200
- [x] Git: 10 коммитов, теги v0.0–v0.8 + v0.8-redirect-slug-fix, GitHub remote
- [x] **Полный SEO-аудит** (v0.9): gzip, security headers, JSON-LD Product, lazy loading, noindex filters, pagination rel, aspect-ratio CLS
- [x] **CSS/HTML в URL продуктов** (v0.10): 4 продукта с border-left/padding/class-именами в url починены
- [x] **17 битых URL продуктов** (v0.10): CSS+non-ASCII+РМ-серия — url перегенерированы, добавлены 301 редиректы
- [x] **product_redirect_map.php** (v0.10): 301 для битых URL продуктов в index.php
- [x] **reduktory.fprom.kz routing** (v0.10): /catalog/ → /reduktory/, удалённые продукты → 301 на серию, удалённые серии → 301 на тип
- [x] **legacy_redirects.php** (v0.11): /products/* → 301 → /* и /catalog/* → 301 → /* (CMS перешёл на no_prefix URL)
- [x] **hasMerchantReturnPolicy** (v0.12): JSON-LD Product — добавлена политика возврата (14 дней, бесплатно)
- [x] **Анализ GSC CSV (999 URL)** (v0.11): корневая причина 404 — смена URL-формата CMS с /products/slug на /slug

## GSC 404 Audit (08.07.2026)
- **999 URL** в CSV (3490 в GSC всего, экспортирована первая страница)
- Разбивка:
  - **fprom.kz /products/... (288)**: CMS перешёл на no_prefix, старые URL с /products/ 404 → **добавлен 301 /products/* → /*** 
  - **fprom.kz direct slugs (504)**: URL без /products/ префикса — работают как no_prefix, но часть продуктов удалена
  - **fprom.kz /catalog/...**: аналогично /products/ → **добавлен 301 /catalog/* → /***
  - **www.fprom.kz (107)**: уже редиректятся (www→non-www в index.php)
  - **reduktory.fprom.kz (98)**: починены умными 301 в v0.10 (404→серия/тип)
- **Блокировка**: nginx кеширует старые 200 ответы для /products/* — редиректы сработают при сбросе кеша/переобходе

## Приоритеты (дальше)
1. **Сбросить nginx кеш** (через Plesk или дождаться expiry) — активирует все 301
2. **Экспорт полного CSV из GSC** (все 3490 URL) для финальной сверки
3. **Регулярный ре-аудит** (Screaming Frog раз в месяц) для контроля
4. **Контент-план** для статических страниц (/o-kompanii, /oplata, /faq)
5. **Мониторинг позиций Google** по ключевым запросам

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
- /fprom.kz/cat_redirect_map.php — 301 на исправленные URL категорий (95 маппингов)
- /fprom.kz/product_redirect_map.php — 301 на исправленные URL продуктов (16 маппингов)
- /fprom.kz/legacy_redirects.php — 301 /products/* → /* и /catalog/* → /* (no_prefix)
- /reduktory.fprom.kz/server/index.js — Node.js роутинг (301 для /catalog/ + умные редиректы 404→серия/тип)
- /reduktory.fprom.kz/data.json — 625 продуктов редукторов
- /fprom.kz/Okay/Helpers/SiteMapHelper.php — исключения из sitemap
- /fprom.kz/backup.php — бэкап БД
- /fprom.kz/backups/ — директория с дампами
- C:\Users\admin\Desktop\script\ — локальный репозиторий
- C:\Users\admin\Desktop\script\internal_all.csv — аудит Screaming Frog
- C:\Users\admin\Desktop\script\audit_report.txt — детальный отчёт по аудиту
