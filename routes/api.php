<?php

use App\Http\Middleware\AcceptJson;
use App\Http\Middleware\CheckLoginSession;
use App\Http\Middleware\RefreshToken;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1', 'middleware' => AcceptJson::class], function () use ($router) {
    $router->group(['namespace' => 'App\Http\Controllers'], function () use ($router) {
        $router->group(['prefix' => 'auth'], function () use ($router) {
            $router->post('/create', 'AuthController@create');
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
    });

    // Needs login session
    $router->group(['middleware' => CheckLoginSession::class], function () use ($router) {
        $router->group(['prefix' => 'auth', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
            $router->post('/logout', 'AuthController@logout');
        });

        $router->group(['middleware' => RefreshToken::class], function () use ($router) {
            $router->group(['prefix' => 'auth', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
                $router->get('/me', 'AuthController@me');
            });
        });

        $router->group(['prefix' => 'recipe', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
            $router->put('/create', 'RecipeController@create');
            $router->patch('/update', 'RecipeController@update');
        });

        $router->group(['prefix' => 'draft', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
            $router->post('/save', 'RecipeDraftController@save');
            $router->delete('/delete/{id}', 'RecipeDraftController@delete');
        });
    });
});
