# GipetauPodcast

Быстрая тёмная WordPress-тема для подкастеров.

## Архитектура

**Гибрид:** PHP-шаблоны + `theme.json` для дизайн-системы.

- `theme.json` — цвета, шрифты, отступы, стили блоков
- PHP-шаблоны — header, footer, page, single, archive, 404, search
- Кастомные шаблоны — Blank, Full Width, With Sidebar

## Характеристики

- **CSS:** < 15KB (один файл, без препроцессоров)
- **JS:** 0 KB по умолчанию. ~400 байт navigation.js подключается только при наличии меню
- **Шрифты:** Google Fonts — Cormorant Garamond + Montserrat + Courier Prime
- **Тема:** тёмная из коробки. Все цвета через CSS-переменные theme.json
- **Блоки:** полная поддержка Gutenberg, editor-styles, wide/full alignment
- **SEO:** семантический HTML5, правильные heading levels, schema-ready

## Хуки для плагинов

Тема предоставляет точки расширения:

| Хук | Где | Зачем |
|------|------|-------|
| `gp_after_content` | single.php, page.php | Плеер, голосование, доп. блоки |
| `gp_comment_callback` (filter) | comments.php | Кастомный рендер комментариев |
| `gp_comment_form` (filter) | comments.php | Замена формы комментариев |

## Структура файлов

```
gipetau-podcast/
├── style.css              # Тема + стили (< 15KB)
├── theme.json             # Дизайн-система
├── functions.php          # Минимальный, чистый
├── screenshot.png
├── header.php / footer.php
├── index.php              # Fallback
├── front-page.php         # Главная
├── single.php             # Запись
├── page.php               # Страница
├── archive.php            # Архив
├── search.php / searchform.php
├── 404.php
├── comments.php           # Hookable
├── sidebar.php
├── parts/
│   ├── content.php        # Карточка записи
│   ├── content-none.php
│   └── content-search.php
├── templates/
│   ├── blank.php          # Без header/footer
│   ├── full-width.php
│   └── with-sidebar.php
├── assets/
│   ├── css/editor.css     # Стили редактора
│   └── js/navigation.js   # Мобильное меню (~400B)
└── inc/                   # Для будущих модулей
```

## Установка

1. Загрузите `gipetau-podcast.zip` через Внешний вид → Темы → Загрузить
2. Активируйте
3. Внешний вид → Меню → создайте и назначьте «Основное меню»
4. Внешний вид → Настроить → загрузите логотип, настройте название

## Плагины (рекомендуемые для подкаста)

Тема **не включает** бизнес-логику. Для подкаста используйте плагины:

- **Плеер + RSS** → кастомный плагин или Jesusin Starter
- **Социальная авторизация** → кастомный плагин (TG, VK, Яндекс)
- **AJAX-комментарии** → кастомный плагин через `gp_comment_form` фильтр
- **Карты Таро** → кастомный плагин через `gp_after_content` хук
- **Голосование** → кастомный плагин
