# Инструкции по сборке DNT Filter Routes

## Требования

- Docker
- Vscode with Dev Containers extension

## Разработка

### Первоначальная настройка

```bash
sh dev.setup.sh
```

### Подключение к дев контейнеру

- /wp - приложение

[Dev Containers: Open Folder in Container...](command:remote-containers.openFolder)

### Контейнер wp

#### установка зависимостей
```bash
composer i
```
#### тесты
```bash
composer run-script phpstan
composer run-script phpcs
```

## Сборка в zip

```bash
sh environment/local/build.sh
```

