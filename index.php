<?php
/**
 * Created by PhpStorm.
 * User: Murod
 * Date: 24.05.2018
 * Time: 5:00
 */
require 'vendor/autoload.php';

// instantiate the App object
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true,
    ]
]);

ORM::configure('mysql:host=localhost;dbname=wtm');
ORM::configure('username', 'root');
ORM::configure('password', '');

/*
 * Получения списка пользователей
 * Метод GET
 * */
$app->get('/users/', function () use ( $app ){
    header('Content-Type: application/json;charset=utf-8');
    $users = ORM::for_table('users')->find_array();
    echo json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

/*
 * Получения одного пользователя по id
 * Метод GET
 * Параметр integer $id //id пользователя
 * */
$app->get('/users/{id}', function (Slim\Http\Request $request, \Slim\Http\Response $response) use ( $app ){
    $id = $request->getAttribute('id');
    $user = ORM::for_table('users')->find_one($id);

    if(!$user){
        echo json_encode([
            'data' => [],
            'code' => 1,
            'message' => 'Запись не найдена или была удалена!',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'code' => 0,
            'message' => 'Данные успешно получены!',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});

/*
 * Регистрация
 * Метод POST
 * Параметр string $username
 * Параметр string $password
 * Параметр string $password2 // Для подверждения пароля
 * Параметр string $email
 * */
$app->post('/register/', function (\Slim\Http\Request $request, \Slim\Http\Response $response) use ( $app ) {
    header('Content-Type: application/json;charset=utf-8');
    $requestData = $request->getParsedBody();

    if($requestData['password'] != $requestData['password2']){
        echo json_encode([
            'code' => 6,
            'message' => 'Пароли не совпадают',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        die;
    }

    $user = ORM::for_table('users')->create();

    $user->username = $requestData['username'];
    $user->password = md5($requestData['password']);
    $user->email = $requestData['email'];
    $user->created_time = date('Y-m-d');

    if($user->save()){
        echo json_encode([
            'code' => 0,
            'message' => 'Данные успешно добавлены',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'code' => 2,
            'message' => 'Возникла ошибка при добавлении данных',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});


/*
 * Авторизация
 * Метод POST
 * Параметр string $username
 * Параметр string $password
 * Return access_token // Это для идентификации пользователя
 * Return expire
 * */
$app->post('/login/', function (\Slim\Http\Request $request, \Slim\Http\Response $response) use ( $app ) {
    header('Content-Type: application/json;charset=utf-8');
    $requestData = $request->getParsedBody();

    $user = ORM::for_table('users')->where_equal('username', $requestData['username'])
        ->where_equal('password', md5($requestData['password']))->find_one();
    if(!$user){
        return json_encode([
            'code' => 7,
            'message' => 'Логин или пароль не верны',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    $token = bin2hex(openssl_random_pseudo_bytes(64));

    $user->access_token = $token;
    $user->token_expire = date('Y-m-d', time()+3*24*3600);

    if($user->save()){
        echo json_encode([
            'data' => [
                'token' => $token,
                'expire' => date('Y-m-d'),
            ],
            'code' => 0,
            'message' => 'Авторизация прошла успешно',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'code' => 2,
            'message' => 'Возникла ошибка при авторизации',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});

/*
 * Добавление пользователя
 * Метод POST
 * Параметр string $username
 * Параметр string $password
 * Параметр string $email
 * */
$app->post('/users/', function (\Slim\Http\Request $request, \Slim\Http\Response $response) use ( $app ) {
    header('Content-Type: application/json;charset=utf-8');
    $requestData = $request->getParsedBody();

    $user = ORM::for_table('users')->create();

    $user->username = $requestData['username'];
    $user->password = md5($requestData['password']);
    $user->email = $requestData['email'];
    $user->created_time = date('Y-m-d');

    if($user->save()){
        echo json_encode([
            'code' => 0,
            'message' => 'Данные успешно добавлены',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'code' => 2,
            'message' => 'Возникла ошибка при добавлении данных',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});

/*
 * Редактирования пользователя
 * Метод PUT
 * Параметр integer $id
 * Параметр string $username
 * Параметр string $password
 * Параметр string $email
 * */
$app->put('/users/', function(\Slim\Http\Request $request, \Slim\Http\Response $response) use ( $app ) {

    $requestData = $request->getParsedBody();

    $user = ORM::for_table('users')->find_one($requestData['id']);

    $user->username = $requestData['username'];
    $user->password = md5($requestData['password']);
    $user->email = $requestData['email'];

    if($user->save()){
        echo json_encode([
            'code' => 0,
            'message' => 'Данные успешно обновлены',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'code' => 3,
            'message' => 'Возникла ошибка при обновлении данных',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});

/*
 * Удаление пользователя
 * Метод DELETE
 * Параметр integer id
 * */
$app->delete('/users/', function(\Slim\Http\Request $request, \Slim\Http\Response $response) use ( $app ) {

    $requestData = $request->getParsedBody();

    $user = ORM::for_table('users')->find_one($requestData['id']);

    if($user->delete()){
        echo json_encode([
            'code' => 0,
            'message' => 'Данные успешно удалены',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'code' => 4,
            'message' => 'Возникла ошибка при удалении данных',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});

//------------------------------------------| Рецепты |--------------------------------------------------------------

$container = $app->getContainer();
$container['upload_directory'] = __DIR__ . '/images';

/*
 * Получения списка рецептов
 * Метод GET
 * */
$app->get('/recipes/', function ($request, $response, $args) use ( $app ){
    header('Content-Type: application/json;charset=utf-8');
    $recipes = ORM::for_table('recipes')->find_array();
    echo json_encode($recipes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

/*
 * Получения одного рецепта по id
 * Метод GET
 * Параметр integer $id //id рецепта
 * */
$app->get('/recipes/{id}', function ($request, $response, $args) use ( $app ){
    $id = $request->getAttribute('id');
    $recipes = ORM::for_table('recipes')->find_one($id);

    if(!$recipes){
        echo json_encode([
            'data' => [],
            'code' => 1,
            'message' => 'Запись не найдена или была удалена!',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'data' => [
                'id' => $recipes->id,
                'title' => $recipes->title,
                'text' => $recipes->text,
                'image' => $recipes->image,
            ],
            'code' => 0,
            'message' => 'Данные успешно получены!',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});

/*
 * Добавления рецепта
 * Метод POST
 * Параметр string $title
 * Параметр text $text
 * Параметр file $image
 * Параметр string(64) $access_token //Для идентификации пользователя
 * */
$app->post('/recipes/', function (\Slim\Http\Request $request, \Slim\Http\Response $response ) use ( $app ) {
    header('Content-Type: application/json;charset=utf-8');
    $requestData = $request->getParsedBody();

    $user = ORM::for_table('users')->where_equal('access_token', $requestData['access_token'])
        ->find_one();

    //Проверка на авторизацию пользователя через access_token
    if(!$user){
        return json_encode([
            'code' => 9,
            'message' => 'Вам необходимо авторизоваться!',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    //Загрузка картини если загружан
    if(is_uploaded_file($_FILES['image']['tmp_name'])){
        $tmp_file = $_FILES['image']['tmp_name'];
        $tmp_name = $_FILES['image']['name'];
        $ext = pathinfo($tmp_name, PATHINFO_EXTENSION);
        $image = 'images/'.time().'.'.$ext;
        if(!move_uploaded_file($tmp_file, $image)){
            return json_encode([
                'code' => 8,
                'message' => 'Файл не загружен',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    $recipes = ORM::for_table('recipes')->create();

    $recipes->title = $requestData['title'];
    $recipes->text = $requestData['text'];
    $recipes->image = '/'.$image;
    $recipes->created_time = date('Y-m-d H:i:s');
    $recipes->created_by = $user->id;

    if($recipes->save()){
        echo json_encode([
            'code' => 0,
            'message' => 'Данные успешно добавлены',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'code' => 2,
            'message' => 'Возникла ошибка при добавлении данных',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});

/*
 * Редактирования рецепта
 * Метод POST
 * Параметр integer $id
 * Параметр string $title
 * Параметр text $text
 * Параметр file $image
 * Параметр string(64) $access_token  //Для идентификации пользователя
 * */
$app->post('/recipes/update/', function(\Slim\Http\Request $request) use ( $app ) {

    $requestData = $request->getParsedBody();

    $user = ORM::for_table('users')->where_equal('access_token', $requestData['access_token'])
        ->find_one();

    //Проверка на авторизацию пользователя через access_token
    if(!$user){
        return json_encode([
            'code' => 9,
            'message' => 'Вам необходимо авторизоваться!',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    $recipes = ORM::for_table('recipes')->find_one($requestData['id']);

    $recipes->title = $requestData['title'];
    $recipes->text = $requestData['text'];

    //Загрузка картини если загружан
    if(is_uploaded_file($_FILES['image']['tmp_name'])){
        $tmp_file = $_FILES['image']['tmp_name'];
        $tmp_name = $_FILES['image']['name'];
        $ext = pathinfo($tmp_name, PATHINFO_EXTENSION);
        $image = 'images/'.time().'.'.$ext;
        if(!move_uploaded_file($tmp_file, $image)){
            return json_encode([
                'code' => 8,
                'message' => 'Файл не загружен',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }else{
            $recipes->image = '/'.$image;
        }
    }

    if($recipes->save()){
        echo json_encode([
            'code' => 0,
            'message' => 'Данные успешно обновлены',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'code' => 3,
            'message' => 'Возникла ошибка при обновлении данных',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});

/*
 * Удаление рецепта
 * Метод DELETE
 * Параметр integer $id
 * Параметр string(64) $access_token  //Для идентификации пользователя
 * */
$app->delete('/recipes/', function(\Slim\Http\Request $request, \Slim\Http\Response $response) use ( $app ) {

    $requestData = $request->getParsedBody();

    $user = ORM::for_table('users')->where_equal('access_token', $requestData['access_token'])
        ->find_one();

    //Проверка на авторизацию пользователя через access_token
    if(!$user){
        return json_encode([
            'code' => 9,
            'message' => 'Вам необходимо авторизоваться!',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    $recipes = ORM::for_table('recipes')->find_one($requestData['id']);

    if($recipes->delete()){
        echo json_encode([
            'code' => 0,
            'message' => 'Данные успешно удалены',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([
            'code' => 4,
            'message' => 'Возникла ошибка при удалении данных',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
});


// Run application
$app->run();
