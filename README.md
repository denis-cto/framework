# Pina2 Framework
Документация в разработке и покрывает только версию 0.2.* (ветка release-0.2)

##Введение

Pina Framework - PHP фреймворк для разработки RESTfull, stateless приложений, со встроенной поддержкой очередей (gearman) и асинхронной обработкой событий. На уровне работы с БД Pina поддерживает автоматическую генерацию структуры таблиц и триггеров на основании классов модели (как альтернативу миграциям).

###Наша стратегия
Придерживаться стандартов PSR, схожего с Laravel именования методов и классов там, где это принципиально не влияет на производительность и идеологию фреймворка.

###Наша идеология
Способствовать разработке производительных асинхронных RESTfull и stateless приложений на PHP. 

Основывается на нескольких принципиальных позициях:

- Структура БД и триггеры - должны храниться в кодовой базе проекта вместе с другой бизнес логикой и генерироваться автоматически.
- Основа роутинга - однозначное отображение RESTfull ресурсов “коллекция/элемент/коллекция…” на контроллеры, позволяющий отказаться от больших таблиц роутинга.
- Модульность с привязкой к пространствам имен (namespace).
- Широкое применение очередей и фоновых процессов для затратных операций (пока на базе gearman).

Pina Framework - это не вещь в себе, он вырос на нескольких реальных проектах со сложной бизнес-логикой. И, надеюсь, вместе с вами мы сможем развить его до нового качественного уровня.

##Установка

Pina не гарантирует в будущем поддержку версий PHP младше PHP7, но в данный момент корректно работает на PHP 5.6+.

Требует PHP-расширений:

- mysqli
- mbstring
- xml

Для начала работы нужно скачать bootstrap версию приложения (скоро будет на github и в composer), обновить зависимости через composer, прописать корректные настройки адреса сайта и БД, и запустить php pinacli.php system.update для обновления БД.

##Настройки

В папке config расположены конфигурационные файлы. Каждый из них представляет собой PHP-файл, возвращающий ассоциативный массив. Для обращения к настройкам используется класс \Pina\Config.

Получить все настройки из файла config/app.php:
```
 \Pina\Config::load(‘app’);
```

Получить пользователя для подключения к БД,
```
\Pina\Config::get(‘db’, ‘user’);
```

##Структура папок:


###Папка app
Здесь в папке default содержится ваше приложение, разбитое на подпапки Layout, Modules, Skin. Layout содержит шаблоны со структурой страниц, Modules содержит контроллеры, шаблоны и классы модулей, а Skin - шаблоны общих визуальных элементов.

Почему все хранится в папке default? Pina поддерживает перегрузку шаблонов для темы оформления сайта. И темы хранятся в папках на одном уровне с default. Таким образом у вас всегда в default оригинальные шаблоны, а папке шаблона - перегруженные.

###Папка bootstrap
Содержит несколько стандартных файлов для запуска фреймворка

###Папка config
Содержит настроечный файлы

###Папка public
Здесь храним файл index.php, который обрабатывает все HTTP запросы, так же здесь храним статику (css, js, картинки).

###Папка tests
Папка для юнит-тестов.

###Папка var
Папка, где хранятся скомпилированные версии шаблонов, временные файлы, логи.

##Обработка ошибок и логирование

...

##Модульность
Приложение разбивается на модули. Каждый модуль - это определенное пространство имен (namespace) внутри Pina\Modules. Например Pina\Modules\Catalog.

Обычно код модуля расположен в папке внутри app/default/Modules (так как автозагрузка по стандарту PSR-4 настроена именно таким образом, что не мешает вам подгрузить новый модуль через composer).

Ключевым элементом модуля является класс Module внутри пространства имен модуля. Именно его система подгружает при инициализации модуля. И именно он указывает на точное местоположение модуля.

##Слой HTTP


###Роутинг

Чтобы повысить эффективность роутинга и избавиться от традиционных таблиц роутинга, связывающих маски адресов с контроллерами, в Pina существует ряд ограничений на структуру системных URL-адресов. Впрочем, если вас не устроит эта система, то фреймворк дает вам возможность зарегистрировать свой диспетчер и обрабатывать произвольные адреса самостоятельно, что очень полезно для CMS систем. Но об этом в другом разделе.

Вернемся к системному роутингу, который должен покрыть нужды большей части вашего приложения.

Pina требует от вас организовывать структуру URL в соответствии с REST-методологией. То есть структурировать URL-адреса по принципу “Коллекция/Элемент”. Таким образом список книг имел бы адрес /books, а книга с ID=5 имела бы адрес /books/5. Книги пользователя alex имели бы адрес /users/alex/books. Система интуитивно понятна, но имеет важное ограничение:
Вы не можете задать коллекцию внутри коллекции минуя элемент. То есть адрес /users/books будет трактоваться как пользователь books внутри коллекции users.

Это ограничение дает возможность однозначно отображать множество коллекций на контроллеры, отбросив части с элементами. Например, GET-запрос к ресурсу /users/alex/books будет обрабатываться группой контроллеров в папке проекта /users/books

Обратите внимание, что за один структурный элемент ресурса отвечает целая группа контроллеров. Ниже объясню, почему мы пришли к такому положению дел.

HTTP протокол поддерживает разные методы запросов, но обычно используются GET, POST, PUT, DELETE, так как их очень удобно сопоставлять с типовыми CRUD-операциями,


HTTP-метод | CRUD-операция
------------- | ------
POST | CREATE (создание)
GET | READ (чтение)
PUT | UPDATE (обновление)
DELETE | DELETE (удаление)


Если трактовать HTTP-методы, как CRUD операции, то сопоставив их с ресурсами в терминах “Коллекция/Элемент” получим следующие трактовки


HTTP-метод | Объект | Пример | Трактовка
------------- | -------- | -------- | -----------
GET | Коллекция | GET /users | Получить список пользователей
GET | Элемент | GET /users/alex | Получить данные пользователя alex
POST | Коллекция | POST /users | Добавить пользователя в коллекцию
POST | Элемент | POST /users/alex | Добавить пользователя в коллекцию с определенным идентификатором
PUT | Коллекция | PUT /users | Обновить информацию о пользователях
PUT | Элемент | PUT /users/alex | Обновить данные о пользователе alex
DELETE | Коллекция | DELETE /users | Удалить пользователей
DELETE | Элемент | DELETE /users/alex | Удалить пользователя alex

Таким образом над одной коллекцией можно задать разнообразный набор действий. Обычно в других фреймворках эти действия задаются методами одного класса-контроллера. Но мы заметили, что эти методы практически не связаны друг с другом, так как реализуют принципиально разную логику и разделили их на несколько типовых контроллеров для каждого типа действия. Эти контроллеры мы проименовали так, как обычно именуют методы класса-контроллера и положили в папку, соответствующей обрабатываемой коллекции.

Пример запроса | Обработчик
------------------| ------------
GET /users | /users/index.php
GET /users/alex | /users/show.php
**GET /users/create** | **/users/create.php**
POST /users | /users/store.php
POST /users/alex | /users/store.php
PUT /users | /users/update.php
PUT /users/alex | /users/update.php
DELETE /users | /users/destroy.php
DELETE /users/alex | /users/destroy.php

Обратите внимание, что в целом контроллер определяется HTTP-методом. 

Но для GET-запросов есть исключение. Система отличает:

- коллекцию (index.php), 
- элемент (show.php),
- и ресурс для ввода информации для создания нового элемента (create.php). 

Как это будет работать для вложенных коллекций:

Пример запроса | Обработчик
------------------ | ------------
GET /users/alex/books | /users/books/index.php
GET /users/alex/books/5 | /users/books/show.php
GET /users/alex/books/create | /users/books/create.php
POST /users/alex/books | /users/books/store.php
POST /users/alex/books/5 | /users/books/store.php
PUT /users/alex/books | /users/books/update.php
PUT /users/alex/books/5 | /users/books/update.php
DELETE /users/alex/books | /users/books/destroy.php
DELETE /users/alex/books/5 | /users/books/destroy.php

Контроллеры хранятся в папке frontend в корне модуля. Так как модулей у нас может много, то надо определять, какой модуль отвечает за какую коллекцию. Для этого модулю достаточно подтвердить факт владения коллекцией одной строчкой:
```
Route::own(‘/users’, __NAMESPACE__); //первым параметром указываем группу контроллеров, 
//вторым параметром надо указать модуль, он однозначно идентифицируется его пространством имен.
```

После выполнения этой команды модуль начинает отвечать за коллекцию /users и все вложенные коллекции этой коллекции, если другой модуль не заявил своего права на вложенную коллекцию.

Таким образом модуль пользователей может владеть коллекцией пользователей и по умолчанию владеть всеми вложенными коллекциями, но какой-то другой модуль (например, модуль книг) может объявить право на коллекцию книг пользователя:
```
Route::own(‘/users/books’, __NAMESPACE__);
```

Такой подход делает роутинг простым и быстрым.


###Контроллеры
Итак, мы разобрались, где должен лежать файл контроллера, чтобы обрабатывать определенные URL-адреса. Теперь поговорим о том, как работает такой контроллер.

Основная идея контроллера в Pina состоит в том, что контроллер должен принимать на вход параметры, отправлять на выход результаты вычислений, но о том, как именно будут интерпретированы эти результаты и как именно будут они отображены, он не должен беспокоиться.

Для реализации этих целей контроллер работает с классом \Pina\Request.

Получить параметры можно так: ```Request::param(‘id’);```

Записать результат так: ```Request::result(‘data’, $data);```

Предположим, наш контроллер на основе параметра ‘post_id’ должен получить из базы данных запись в блоге и вернуть её в качестве результата.

```
$postId = Request::param(‘post_id’);
$post = PostGateway::instance()->find($postId);
Request::result(‘post’, $post); 
```

Усложним задачу. Теперь если запись не найдена надо возвращать страницу 404:

```
$postId = Request::param(‘post_id’);
$post = PostGateway::instance()->find($postId);
if (empty($post)) {
	return Request::notFound();
}
Request::result(‘post’, $post); 
```

Если не передан обязательный параметр post_id возвращать код ошибки 400 bad request:

```
$postId = Request::param(‘post_id’);
if (empty($postId)) {
	return Request::badRequest();
}
$post = PostGateway::instance()->find($postId);
if (empty($post)) {
	return Request::notFound();
}
Request::result(‘post’, $post); 
```

####Валидация
…

####Вложенные запросы.
Контроллер может обрабатывать, как и внешний HTTP-запрос, так и внутренний вызов из представления или другого контроллера. Это позволяет использовать контроллеры для обработки отдельных блоков HTML-страницы, подключая и отключая их прямо из шаблонизатора.

####Представление (View)

У контроллера может быть несколько разных типов представлений. В данный момент поддерживаются два основных: JSON-представление и HTML-представление.

Чтобы получить JSON-представление достаточно обратиться к ресурсу с расширением .json. В этом случае все данные, переданные как результаты запроса, упакуются в json-объект. В целом проектировать контроллер нужно таким образом, чтобы результаты его работы коррелировали с сутью запроса к нему. Думать о его результатах отдельно от его представления, даже если основным представлением будет HTML, а не JSON.

Для построения HTML-представления используется шаблонизатор Smarty. Шаблон лежит в той же папке, что и контроллер, называется также (за исключением расширения: tpl, а не php). У одного контроллера может быть несколько HTML-представлений. Выбор представления управляется параметром display.

Например:

Ресурс | Контроллер | Шаблон
------ | ---------- | ------
books/5 | books/show.php | books/show.tpl
books/5?display=edit | books/show.php | books.edit.tpl

Только обработчики GET-запросов могут иметь HTML-представление, это действия:

- index
- show
- create

Обработчики POST, PUT, DELETE-запросов возвращают JSON, или используют HTTP-заголовки для сигнализации о сделанных изменениях.

####Шаблоны

Smarty - мощный шаблонизатор, с основными его функциями вы можете познакомиться на официальном сайте Smarty, я же изложу основные приемы и smarty-функции, которые мы используем для строительства приложения на основе Pina.

####Родительский шаблон (Layout)

В папке app/default/Layout вы можете определить родительский шаблон, в который будут вписаны результаты отрисовки запроса. По умолчанию используется шаблон main.tpl, но вы можете в конкретном отображении поменять родительский шаблон через инструкцию,

{extends layout=”single”}

В родительский шаблон результат отрисовки будет передан в качестве переменной {$content}.

Обычно простой вставки результатов отрисовки запроса в какое-то одно место родительского шаблона не достаточно. Например, нам кроме центральной части надо заполнять заголовок и тег title.

Родительский шаблон мог бы выглядеть так:
main.tpl

```
<html>
<head>
  <title>{place name=”title”}</title>
</head>
<body>
<h1>{place name=”title”}</h1>
<article>{$content}</article>
</body>
</html>
```

Чтобы отдельно прокинуть контент в область для title, в дочернем шаблоне надо использовать smarty-функцию {content}   

show.tpl
```
{content name=title}{$post.post_title}{/content}

<p>{$post.post_text}</p>
```
