# Custom Moodle Analytics Reports - Полная документация

## 📋 Содержание
1. [Установка](#установка)
2. [Настройка](#настройка)
3. [Использование](#использование)
4. [API Документация](#api-документация)
5. [Разработка](#разработка)
6. [Troubleshooting](#troubleshooting)

---

## 🚀 Установка

### Системные требования
- **Moodle**: 4.1, 4.2, 4.3, 4.4, 4.5
- **PHP**: 7.4 или выше
- **База данных**: MySQL 5.7+, PostgreSQL 10+, MariaDB 10.3+
- **JavaScript**: ES6+ поддержка

### Пошаговая установка

#### Шаг 1: Скопируйте файлы плагина
```bash
# Перейдите в директорию Moodle
cd /path/to/moodle

# Скопируйте плагин
cp -r /workspace/local/customreports local/
```

#### Шаг 2: Установите права доступа
```bash
chown -R www-data:www-data local/customreports
chmod -R 755 local/customreports
```

#### Шаг 3: Запустите установку в Moodle
1. Войдите в Moodle как администратор
2. Перейдите в **Site administration > Notifications**
3. Нажмите **Upgrade Moodle database now**
4. Дождитесь завершения установки таблиц

---

## ⚙️ Настройка

### Capabilities (Права доступа)

| Capability | Описание | Роли по умолчанию |
|------------|----------|-------------------|
| `local/customreports:viewdashboard` | Просмотр dashboard | Manager, Teacher |
| `local/customreports:viewcourses` | Просмотр отчетов по курсам | Manager, Teacher |
| `local/customreports:viewengagement` | Просмотр вовлеченности | Manager, Teacher |
| `local/customreports:viewtimetracking` | Просмотр отслеживания времени | Manager, Teacher |
| `local/customreports:exportdata` | Экспорт данных | Manager, Admin |
| `local/customreports:managescheduled` | Управление расписанием | Manager, Admin |

### Настройка cron задач

```bash
# Настройте cron для Moodle
* * * * * php /path/to/moodle/admin/cli/cron.php
```

---

## 📊 Использование

### Dashboard (Главная панель)

**URL**: `/local/customreports/index.php`

#### Виджеты:

1. **Site Overview** - Общая статистика платформы
2. **Course Progress** - Прогресс по курсам (гистограмма)
3. **Popular Courses** - Топ-10 популярных курсов
4. **Daily Activities** - Активность за 7/30/90 дней (линейный график)
5. **Real-time Users** - Пользователи онлайн (автообновление 30с)
6. **Inactive Users** - Неактивные студенты (круговая диаграмма)
7. **Certificates Stats** - Статистика сертификатов

### Отчеты

#### Course Progress Report
- Таблица всех курсов с процентом завершения
- Цветовая индикация: 🔴 0-25%, 🟡 26-50%, 🟠 51-75%, 🟢 76-100%
- Фильтры по категории, дате, когорте
- Экспорт в CSV/Excel/PDF

#### Student Engagement Report
- Индекс вовлеченности (0-100 баллов)
- Метрики: время, посещения, активности, форумы, задания
- Классификация: HIGH (≥80), MEDIUM (≥50), LOW (<50)
- Топ студентов и группа риска

#### Time Tracking Report
- LMS level: активность по дням
- Course level: время по курсам
- Activity level: время по активностям
- Heatmap: активность по дням/часам

---

## 🔌 API Документация

### Web Services Endpoints

```javascript
// Dashboard данные
Ajax.call([{
    methodname: 'local_customreports_get_dashboard_data',
    args: {}
}]);

// Прогресс курсов
Ajax.call([{
    methodname: 'local_customreports_get_course_progress',
    args: { courseid: 123 }
}]);

// Вовлеченность
Ajax.call([{
    methodname: 'local_customreports_get_engagement_data',
    args: { courseid: 123, timestart: 1678800000, timeend: 1678900000 }
}]);

// Отслеживание времени
Ajax.call([{
    methodname: 'local_customreports_get_timetracking_data',
    args: { courseid: 123, level: 'lms' }
}]);

// Heatmap
Ajax.call([{
    methodname: 'local_customreports_get_heatmap',
    args: { days: 30 }
}]);

// Экспорт
Ajax.call([{
    methodname: 'local_customreports_export_report',
    args: {
      reporttype: 'engagement',
      format: 'excel',
      filename: 'report'
    }
}]);
```

---

## 👨‍💻 Разработка

### Структура плагина

```
local/customreports/
├── admin/                  # Настройки
├── amd/src/               # JavaScript исходники
├── classes/
│   ├── export/           # Экспорт
│   ├── external/         # API endpoints
│   ├── privacy/          # GDPR
│   ├── report/           # Отчеты
│   ├── task/             # Cron задачи
│   └── utils/            # Утилиты
├── db/                   # База данных
├── templates/            # Mustache шаблоны
├── tests/                # Тесты
└── docs/                 # Документация
```

### Создание нового отчета

```php
namespace local_customreports\report;

class mycustomreport {
    public function get_data($params) {
        global $DB;
        $sql = "SELECT ... FROM {mytable}";
        return $DB->get_records_sql($sql, $params);
    }
}
```

### Запуск тестов

```bash
php vendor/bin/phpunit local/customreports/tests/
```

---

## 🔧 Troubleshooting

### Частые проблемы

**Плагин не отображается**:
```bash
php admin/cli/purge_caches.php
```

**Медленная загрузка**:
```sql
CREATE INDEX idx_log_timecreated ON mdl_logstore_standard_log(timecreated);
CREATE INDEX idx_user_lastaccess ON mdl_user(lastaccess);
```

**Экспорт не работает**:
```bash
composer require phpoffice/phpspreadsheet
```

---

## 📄 Лицензия

GPL-3.0

## 📦 Зависимости

- Chart.js 4.x
- jQuery 3.x (встроен в Moodle)
- Bootstrap 4/5 (встроен в Moodle)
- PHPSpreadsheet (опционально)
- TCPDF (встроен в Moodle)
