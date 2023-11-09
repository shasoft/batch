<?php

use Shasoft\Batch\BatchError;
use Shasoft\Batch\BatchGroup;
use Shasoft\Batch\BatchManager;
use Shasoft\Batch\BatchPromise;
use Shasoft\Batch\BatchGroupConfig;

// Функция для получения асинхронных данных и их возврата в синхронный код
// Анонимная функция должно возвращать обещание, 
// результат которого и будет возвращен из функции run
$articles = BatchManager::run(function (): BatchPromise {
    // Функция all выполняет массив обещаний 
    // и возвращает массив значений этих обещаний
    return BatchManager::all([
        getArticle(1),
        getArticle(2),
        getArticle(3)
    ]);
});

// Получить информацию о статье
function getArticle(int $id): BatchPromise
{
    // Создаем и возвращаем обещание
    return BatchManager::create(
        // Функция для обработки сгруппированных данных
        function (BatchGroup $group) {
            // Получить список уникальных значений аргументов с номером 0
            $ids = $group->arg(0);
            // Выбрать из БД информацию о статьях
            $articles = sql('SELECT * FROM `articles` WHERE `id` IN (' . implode(',', $ids) . ')');
            // Создадим обещания получения информации о пользователях
            $promises = [];
            foreach ($articles as $article) {
                $promises[] = getUser($article['author_id']);
            }
            BatchManager::all($promises)->then(function (array $users) use ($articles, $group) {
                // Сгруппировать пользователей по идентификатору
                $mUsers = [];
                foreach ($users as $user) {
                    $mUsers[$user['id']] = $user;
                }
                // Проставить информацию об авторе
                foreach ($articles as $article) {
                    $article['author'] = $mUsers[$article['author_id']];
                }
                // Сгруппировать статьи по идентификатору
                $mArticles = [];
                foreach ($articles as $article) {
                    $mArticles[$article['id']] = $article;
                }
                // Вернуть информацию о статьях
                $group->setResult(function (int $id) use ($mArticles) {
                    return $mArticles[$id] ?? null;
                });
            });
        },
        $id
    );
}
// Получить информацию о пользователе
function getUser(int $id): BatchPromise
{
    // Создаем и возвращаем обещание
    return BatchManager::create(
        // Функция для обработки сгруппированных данных
        function (BatchGroup $group) {
            // Получить список уникальных значений аргументов с номером 0
            $ids = $group->arg(0);
            // Выбрать из БД информацию о пользователях
            $users = sql('SELECT * FROM `users` WHERE `id` IN (' . implode(',', $ids) . ')');
            // Сгруппировать пользователей по идентификатору
            $mUsers = [];
            foreach ($users as $user) {
                $mUsers[$user['id']] = $user;
            }
            // Вернуть информацию о пользователе
            $group->setResult(function (int $id) use ($mUsers) {
                return $mUsers[$id] ?? null;
            });
        },
        $id
    );
}

// Получить информацию о пользователе
function getUserEx(int $id): BatchPromise
{
    // Создаем и возвращаем обещание
    return BatchManager::createEx(
        // Функция указания расширенных настроек
        function (BatchGroupConfig $config) {
            // Указываем пониженный приоритет (чтобы эта группа выполнялась последней)
            $config->setPriority(BatchManager::PRIORITY_LOW);
        },
        // Функция для обработки сгруппированных данных
        function (BatchGroup $group) {
            // ...
        },
        $id
    );
}

// Получить информацию о пользователе
function getUserError(int $id): BatchPromise
{
    // Создаем и возвращаем обещание
    return BatchManager::create(
        // Функция для обработки сгруппированных данных
        function (BatchGroup $group) {
            // Получить список уникальных значений аргументов с номером 0
            $ids = $group->arg(0);
            // Выбрать из БД информацию о пользователях
            $users = sql('SELECT * FROM `users` WHERE `id` IN (' . implode(',', $ids) . ')');
            // Сгруппировать пользователей по идентификатору
            $mUsers = [];
            foreach ($users as $user) {
                $mUsers[$user['id']] = $user;
            }
            // Вернуть информацию о пользователе
            $group->setResult(function (int $id) use ($mUsers) {
                // Возвращаем ошибку если по идентификатору ничего не выбралось
                return $mUsers[$id] ?? BatchError::create("Пользователь {$id} не найден");
            });
        },
        $id
    );
}
