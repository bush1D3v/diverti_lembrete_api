<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Actions\Reminder\ViewReminderAction;
use App\Application\Actions\Reminder\ListRemindersAction;
use App\Application\Actions\Reminder\DeleteReminderAction;
use App\Application\Actions\Reminder\InsertReminderAction;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->group('/reminders', function (Group $group) {
        $group->options('', function (Request $request, Response $response) {
            return $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
        });
        $group->get('', ListRemindersAction::class);
        $group->get('/{id}', ViewReminderAction::class);
        $group->post('', InsertReminderAction::class);
        $group->delete('/{id}', DeleteReminderAction::class);
    });
};
