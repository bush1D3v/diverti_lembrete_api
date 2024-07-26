<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Actions\Reminder\ViewReminderAction;
use App\Application\Actions\Reminder\ListRemindersAction;
use App\Application\Actions\Reminder\CheckRemindersAction;
use App\Application\Actions\Reminder\CountRemindersAction;
use App\Application\Actions\Reminder\DeleteReminderAction;
use App\Application\Actions\Reminder\InsertReminderAction;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->group('/reminders', function (Group $group) {
        $group->options('', function (Request $request, Response $response) {
            return $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Location');
        });
        $group->post('', InsertReminderAction::class);
        $group->get('', ListRemindersAction::class);
        $group->get('/count', CountRemindersAction::class);
        $group->options('/{id}', function (Request $request, Response $response) {
            return $response->withHeader('Access-Control-Allow-Methods', 'GET, DELETE, OPTIONS');
        });
        $group->get('/{id}', ViewReminderAction::class);
        $group->delete('/{id}', DeleteReminderAction::class);
        $group->options('/{id}/check', function (Request $request, Response $response) {
            return $response->withHeader('Access-Control-Allow-Methods', 'PATCH, OPTIONS');
        });
        $group->patch('/{id}/check', CheckRemindersAction::class);
    });
};
