<?php

use App\Http\Middleware\AcceptJson;
use App\Http\Middleware\BackofficeMiddleware;
use App\Http\Middleware\CheckLoginSession;
use App\Http\Middleware\RefreshToken;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'middleware' => AcceptJson::class], function () use ($router) {
    $router->group(['namespace' => 'App\Http\Controllers'], function () use ($router) {
        $router->group(['prefix' => 'auth'], function () use ($router) {
            $router->post('/create', 'AuthController@create');
            $router->post('/create-token', 'AuthController@createToken');
        });

        $router->group(['prefix' => 'data'], function () use ($router) {
            $router->get('/get/{className}/{id}/{relations?}', 'DataController@index');
            $router->get('/combobox', 'DataController@comboboxData');
            $router->get('/list', 'DataController@list');
            $router->get('/statistics', 'DataController@statistics');
        });

        $router->group(['prefix' => 'crud'], function () use ($router) {
            $router->put('/create/{model}', 'CrudController@create');
            $router->patch('/update/{model}', 'CrudController@update');
            $router->delete('/delete/{model}', 'CrudController@delete');
            $router->post('/restore/{model}', 'CrudController@restore');
            $router->delete('/force-delete', 'CrudController@forceDelete');
        });

        $router->group(['prefix' => 'umami'], function () use ($router) {
            $router->get('/pageviews', 'UmamiController@getPageviews');
        });

        $router->group(['prefix' => 'recipe'], function () use ($router) {
            $router->get('/random', 'RecipeController@getRandom');
        });

        $router->group(['prefix' => 'push'], function () use ($router) {
            $router->post('/send', 'NotificationController@sendNotification');
        });
    });

    // Needs login session
    $router->group(['middleware' => CheckLoginSession::class], function () use ($router) {
        $router->group(['prefix' => 'auth', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
            $router->post('/logout', 'AuthController@logout');
            $router->post('/delete-account', 'AuthController@deleteAccount');
            $router->post('/update-config', 'AuthController@updateConfig');
            $router->get('/me', 'AuthController@me');
        });

        $router->group(['middleware' => RefreshToken::class], function () use ($router) {
            $router->group(['prefix' => 'auth', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
                $router->get('/me', 'AuthController@me');
            });
        });

        $router->group(['prefix' => 'recipe', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
            $router->put('/create', 'RecipeController@create');
            $router->patch('/update', 'RecipeController@update');
            $router->post('/update-status', 'RecipeController@updateStatus');
            $router->post('/add-to-book', 'RecipeController@addToBook');
            $router->delete('/delete/{id}', 'RecipeController@delete');
            $router->patch('/set-privacy', 'RecipeController@setPrivacy');
        });

        $router->group(['prefix' => 'draft', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
            $router->post('/save', 'RecipeDraftController@save');
            $router->delete('/delete/{id}', 'RecipeDraftController@delete');
            $router->post('/submit/{id}', 'RecipeDraftController@submitDraft');
            $router->post('/make/{id}', 'RecipeDraftController@makeDraft');
            $router->post('/save-image', 'RecipeDraftController@saveImage');
            $router->delete('/delete-image', 'RecipeDraftController@deleteImage');
        });
    });

    $router->group(['prefix' => 'backoffice', 'middleware' => BackofficeMiddleware::class], function () use ($router) {
        $router->group(['prefix' => 'recipe', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
            $router->post('/update-status', 'RecipeController@updateStatus');
        });
    });
});
