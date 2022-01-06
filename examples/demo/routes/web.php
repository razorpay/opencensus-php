<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use OpenCensus\Trace\Tracer;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/visits', function () {
    // Creates a detached span
    $span = Tracer::startSpan(['name' => 'expensive-redis-operation']);
// Opens a scope that attaches the span to the current context
    $scope = Tracer::withSpan($span);
    try {
        $visits = Redis::incr('visits');
        $span = Tracer::startSpan(['name' => 'db:get:user']);
        $scope = Tracer::withSpan($span);
        $users = DB::table('users')->get();
        $span = Tracer::startSpan(['name' => 'GET:Guzzle:Repository']);
        $scope = Tracer::withSpan($span);
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://api.github.com/repos/guzzle/guzzle');
        $response->getStatusCode();
    } finally {
        // Closes the scope (ends the span)
        $scope->close();
    }

    return "Total Redis Hit: ".$visits;
});

Route::get('/list', function () {

    $users = DB::table('users')->get();

    return "Total User Entries: ".count($users);
});

Route::get('/add', function () {

    $span = Tracer::startSpan(['name' => 'db:add:user']);
// Opens a scope that attaches the span to the current context
    $scope = Tracer::withSpan($span);
    try {
        DB::table('users')->insert([
            'name' => 'user1'.sha1(time()),
            'email' => 'user1'.sha1(time()).'1@email.com',
            'password' => bcrypt('password'),
        ]);
    } finally {
        // Closes the scope (ends the span)
        $scope->close();
    }
    $span = Tracer::startSpan(['name' => 'db:get:users']);
    $scope = Tracer::withSpan($span);
    try {
        $users = DB::table('users')->get();
    }finally {
        // Closes the scope (ends the span)
        $scope->close();
    }
    return "Total User Entries: ".count($users);
});
