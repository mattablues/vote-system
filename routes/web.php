<?php

declare(strict_types=1);

/** @var \Radix\Routing\Router $router */

$router->get('/', [
    \App\Controllers\HomeController::class, 'index'
])->name('home.index');

$router->get('/about', [
    \App\Controllers\AboutController::class, 'index'
])->name('about.index');

$router->get('/contact', [
    \App\Controllers\ContactController::class, 'index'
])->name('contact.index');

$router->post('/contact', [
    \App\Controllers\ContactController::class, 'create'
])->name('contact.create');

$router->get('/cookie', [
    \App\Controllers\CookieController::class, 'index'
])->name('cookie.index');

$router->get('/categories', [
    \App\Controllers\Votes\CategoryController::class, 'index'
])->name('votes.category.index');

$router->get('/category/{id:[\d]+}/show', [
    \App\Controllers\Votes\CategoryController::class, 'show'
])->name('votes.category.show');

$router->get('/subjects', [
    \App\Controllers\Votes\SubjectController::class, 'index'
])->name('votes.subject.index');

$router->get('/subject/create', [
    \App\Controllers\Votes\SubjectController::class, 'create'
])->name('votes.subject.create')->middleware(['voter.auth']);

$router->post('/subject/store', [
    \App\Controllers\Votes\SubjectController::class, 'store'
])->name('votes.subject.store');

$router->get('/subject/{id:[\d]+}/vote', [
    \App\Controllers\Votes\VoteController::class, 'index'
])->name('votes.vote.index');

$router->post('/subject/{id:[\d]+}/vote', [
    \App\Controllers\Votes\VoteController::class, 'create'
])->name('votes.vote.create')->middleware(['voter.auth']);

$router->group(['middleware' => ['location', 'voter.quest']], function () use ($router) {
    $router->get('/voter', [
        \App\Controllers\Votes\VoterController::class, 'index'
    ])->name('voter.index');

    $router->post('/voter', [
        \App\Controllers\Votes\VoterController::class, 'create'
    ])->name('voter.create');
});

$router->get('/voter/unregister', [
    \App\Controllers\Votes\VoterController::class, 'unregister'
])->name('voter.unregister');

$router->post('/voter/unregister', [
    \App\Controllers\Votes\VoterController::class, 'store'
])->name('voter.store');

$router->get('/voter/login', [
    \App\Controllers\Votes\VoterAuthController::class, 'login'
])->name('voter.auth.login')->middleware(['voter.quest']);

$router->post('/voter/login', [
    \App\Controllers\Votes\VoterAuthController::class, 'create'
])->name('voter.auth.create')->middleware(['voter.quest']);

$router->post('/voter/logout', [
    \App\Controllers\Votes\VoterAuthController::class, 'logout'
])->name('voter.auth.logout')->middleware(['voter.auth']);

$router->get('/voter/delete/{token:[\da-f]+}', [
    \App\Controllers\Votes\VoterController::class, 'delete'
])->name('voter.delete');

$router->post('/voter/delete/{token:[\da-f]+}', [
    \App\Controllers\Votes\VoterController::class, 'remove'
])->name('voter.remove');

$router->get('/voter/activate/{token:[\da-f]+}', [
    \App\Controllers\Votes\VoterController::class, 'activate'
])->name('voter.activate');

$router->get('/voter/password-forgot', [
    \App\Controllers\Votes\PasswordForgotController::class, 'index'
])->name('voter.password-forgot.index');

$router->post('/voter/password-forgot', [
    \App\Controllers\Votes\PasswordForgotController::class, 'create'
])->name('voter.password-forgot.create');

$router->get('/voter/password-reset/{token:[\da-f]+}', [
    \App\Controllers\Votes\PasswordResetController::class, 'index'
])->name('voter.password-reset.index');

$router->post('/voter/password-reset/{token:[\da-f]+}', [
    \App\Controllers\Votes\PasswordResetController::class, 'create'
])->name('voter.password-reset.create');

$router->group(['middleware' => ['private', 'guest']], function () use ($router) {
    $router->get('/register', [
        \App\Controllers\Auth\RegisterController::class, 'index'
    ])->name('auth.register.index');

    $router->post('/register', [
        \App\Controllers\Auth\RegisterController::class, 'create'
    ])->name('auth.register.create');
});

$router->group(['middleware' => ['guest']], function () use ($router) {
    $router->get('/register/activate/{token:[\da-f]+}', [
        \App\Controllers\Auth\RegisterController::class, 'activate'
    ])->name('auth.register.activate');

    $router->get('/login', [
        \App\Controllers\Auth\LoginController::class, 'index'
    ])->name('auth.login.index');

    $router->post('/login', [
        \App\Controllers\Auth\LoginController::class, 'create'
    ])->name('auth.login.create');

    $router->get('/password-forgot', [
        \App\Controllers\Auth\PasswordForgotController::class, 'index'
    ])->name('auth.password-forgot.index');

    $router->post('/password-forgot', [
        \App\Controllers\Auth\PasswordForgotController::class, 'create'
    ])->name('auth.password-forgot.create');

    $router->get('/password-reset/{token:[\da-f]+}', [
        \App\Controllers\Auth\PasswordResetController::class, 'index'
    ])->name('auth.password-reset.index');

    $router->post('/password-reset/{token:[\da-f]+}', [
        \App\Controllers\Auth\PasswordResetController::class, 'create'
    ])->name('auth.password-reset.create');

    $router->get('/logout-message', [
        \App\Controllers\Auth\LogoutController::class, 'logoutMessage'
    ])->name('auth.logout.message');

    $router->get('/logout-close-message', [
        \App\Controllers\Auth\LogoutController::class, 'closeLogoutMessage'
    ])->name('auth.logout.close-message');

    $router->get('/logout-delete-message', [
        \App\Controllers\Auth\LogoutController::class, 'deletedLogoutMessage'
    ])->name('auth.logout.delete-message');

    $router->get('/logout-blocked-message', [
        \App\Controllers\Auth\LogoutController::class, 'blockedLogoutMessage'
    ])->name('auth.logout.blocked-message');
});

$router->group(['middleware' => ['auth']], function () use ($router) {
    $router->get('/dashboard', [
        \App\Controllers\Dashboard::class, 'index'
    ])->name('dashboard.index');

    $router->get('/user', [
        \App\Controllers\UserController::class, 'index'
    ])->name('user.index');

    $router->get('/user/{id:[\d]+}/show', [
        \App\Controllers\UserController::class, 'show'
    ])->name('user.show');

    $router->get('/user/edit', [
        \App\Controllers\UserController::class, 'edit'
    ])->name('user.edit');

    $router->post('/user/edit', [
        \App\Controllers\UserController::class, 'update'
    ])->name('user.update');

    $router->post('/user/delete', [
        \App\Controllers\UserController::class, 'delete'
    ])->name('user.delete');

    $router->post('/user/close', [
        \App\Controllers\UserController::class, 'close'
    ])->name('user.close');

    $router->post('/logout', [
        \App\Controllers\Auth\LogoutController::class, 'index'
    ])->name('auth.logout.index');
});

$router->group(['path' => '/admin', 'middleware' => ['auth', 'role.exact.admin']], function () use ($router) {
    $router->get('/users/create-user', [
        \App\Controllers\Admin\UserController::class, 'create'
    ])->name('admin.user.create');

    $router->post('/users/create-user', [
        \App\Controllers\Admin\UserController::class, 'store'
    ])->name('admin.user.store');

    $router->post('/users/{id:[\d]+}/role', [
        \App\Controllers\Admin\UserController::class, 'role'
    ])->name('admin.user.role');
});

$router->group(['path' => '/admin', 'middleware' => ['auth', 'role.min.editor']], function () use ($router) {
    $router->get('/categories', [
        \App\Controllers\Admin\CategoryController::class, 'index'
    ])->name('admin.category.index');

    $router->get('/categories/{id:[\d]+}/edit', [
        \App\Controllers\Admin\CategoryController::class, 'edit'
    ])->name('admin.category.edit');

    $router->post('/categories/{id:[\d]+}/update', [
        \App\Controllers\Admin\CategoryController::class, 'update'
    ])->name('admin.category.update');

    $router->get('/subjects', [
        \App\Controllers\Admin\SubjectController::class, 'index'
    ])->name('admin.subject.index');

    $router->get('/subject/create', [
    \App\Controllers\Admin\SubjectController::class, 'create'
    ])->name('admin.subject.create');

    $router->post('/subject/store', [
        \App\Controllers\Admin\SubjectController::class, 'store'
    ])->name('admin.subject.store');

    $router->post('/subjects/{id:[\d]+}/post', [
        \App\Controllers\Admin\SubjectController::class, 'publish'
    ])->name('admin.subject.publish');

    $router->get('/subjects/{id:[\d]+}/edit', [
        \App\Controllers\Admin\SubjectController::class, 'edit'
    ])->name('admin.subject.edit');

    $router->post('/subjects/{id:[\d]+}/update', [
        \App\Controllers\Admin\SubjectController::class, 'update'
    ])->name('admin.subject.update');
});

$router->group(['path' => '/admin', 'middleware' => ['auth', 'role.min.moderator']], function () use ($router) {
    $router->get('/users', [
        \App\Controllers\Admin\UserController::class, 'index'
    ])->name('admin.user.index');

    $router->post('/users/{id:[\d]+}/send-activation', [
        \App\Controllers\Admin\UserController::class, 'sendActivation'
    ])->name('admin.user.send-activation');

    $router->post('/users/{id:[\d]+}/block', [
        \App\Controllers\Admin\UserController::class, 'block'
    ])->name('admin.user.block');

    $router->get('/users/closed', [
        \App\Controllers\Admin\UserController::class, 'closed'
    ])->name('admin.user.closed');

    $router->post('/users/{id:[\d]+}/restore', [
        \App\Controllers\Admin\UserController::class, 'restore'
    ])->name('admin.user.restore');

    $router->get('/voters', [
        \App\Controllers\Admin\VoterController::class, 'index'
    ])->name('admin.voter.index');

    $router->post('/voters/{id:[\d]+}/send-activation', [
        \App\Controllers\Admin\VoterController::class, 'sendActivation'
    ])->name('admin.voter.send-activation');

    $router->post('/voters/{id:[\d]+}/block', [
        \App\Controllers\Admin\VoterController::class, 'block'
    ])->name('admin.voter.block');

    $router->get('/categories/create', [
        \App\Controllers\Admin\CategoryController::class, 'create'
    ])->name('admin.category.create');

    $router->post('/categories/create', [
        \App\Controllers\Admin\CategoryController::class, 'store'
    ])->name('admin.category.store');

    $router->post('/categories/{id:[\d]+}/delete', [
        \App\Controllers\Admin\CategoryController::class, 'delete'
    ])->name('admin.category.delete');

    $router->post('/subjects/{id:[\d]+}/delete', [
        \App\Controllers\Admin\SubjectController::class, 'delete'
    ])->name('admin.subject.delete');

    $router->get('/health', [
        \App\Controllers\Admin\HealthWebController::class, 'index'
    ])->name('admin.health.index');
});