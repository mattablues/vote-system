<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Status;
use App\Models\User;
use App\Services\UploadService;
use Radix\Controller\AbstractController;
use Radix\Enums\Role;
use Radix\Http\Exception\NotAuthorizedException;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Session\Session;
use Radix\Support\Validator;
use RuntimeException;

class UserController extends AbstractController
{
    public function index(): Response
    {
        return $this->view('user.index');
    }

    public function show(string $id): Response
    {
        // Hämta inloggad användare för att avgöra behörighet
        $authId = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($authId) && !is_string($authId)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $authUser = User::find($authId);

        // $id här är route-parametern: vilken användare vi vill visa
        if (!$authUser || !$authUser->hasAtLeast('moderator')) {
            $user = User::with('status')->where('id', '=', $id)->first();
        } else {
            $user = User::with('status')->withSoftDeletes()->where('id', '=', $id)->first();
        }

        $roles = Role::cases();

        return $this->view('user.show', ['user' => $user, 'roles' => $roles]);
    }

    public function edit(): Response
    {
        $id = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($id) && !is_string($id)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($id);

        return $this->view('user.edit', ['user' => $user]);
    }

    public function update(): Response
    {
        $this->before();

        $data = $this->request->post; // Hämta formulärdata

        $rawAvatar = $this->request->files['avatar'] ?? null;
        /** @var array{error:int,name?:string,tmp_name?:string,size?:int,type?:string}|null $avatar */
        $avatar = is_array($rawAvatar) && array_key_exists('error', $rawAvatar) ? $rawAvatar : null;

        $userId = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($userId) && !is_string($userId)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($userId);

        // Validera data inklusive avatar
        $validator = new Validator($data + ['avatar' => $avatar], [
            'first_name' => 'required|min:2|max:15',
            'last_name' => 'required|min:2|max:15',
            'email' => 'required|email|unique:App\Models\User,email,id=' . $userId,
            'avatar' => 'nullable|file_size:2|file_type:image/jpeg,image/png',
            'password' => 'nullable|min:8|max:15',
            'password_confirmation' => 'nullable|required_with:password|confirmed:password',
        ]);

        if (!$validator->validate()) {
            $this->request->session()->set('old', $data);

            return $this->view('user.edit', [
                'user' => $user,
                'errors' => $validator->errors(),
            ]);
        }

        // Hantera avatar-uppladdning om en fil har laddats upp
        if ($avatar !== null && $avatar['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadDirectory = ROOT_PATH . "/public/images/user/$userId/";

                $uploadService = new UploadService();

                if ($user === null) {
                    throw new NotAuthorizedException('User not found.');
                }

                if ($user->avatar !== '/images/graphics/avatar.png') {
                    $oldAvatarPath = ROOT_PATH . $user->avatar;
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }

                $data['avatar'] = $uploadService->uploadAvatar($avatar, $uploadDirectory);
            } catch (RuntimeException $e) {
                return $this->view('user.edit', [
                    'errors' => ['avatar' => $e->getMessage()],
                ]);
            }
        }

        // Kontrollera avatar innan fälten filtreras
        if ($avatar !== null && $avatar['error'] === UPLOAD_ERR_NO_FILE) {
            unset($data['avatar']);
        }

        // Filtrera irrelevanta fält
        $data = $this->request->filterFields($data);

        // Rensa session för gamla indata
        $this->request->session()->remove('old');

        if ($user === null) {
            throw new NotAuthorizedException('User not found.');
        }

        // Uppdatera användardata i databasen
        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);

        // Uppdatera avatar om det finns en ny filväg
        if (!empty($data['avatar']) && is_string($data['avatar'])) {
            $user->avatar = $data['avatar'];
        }

        // Uppdatera lösenord om ett nytt lösenord angavs
        if (isset($data['password']) && is_string($data['password']) && $data['password'] !== '') {
            $password = $data['password']; // här vet PHPStan att det är string

            $user->password = $password;
        }

        // Spara ändringar i databasen
        $user->save();

        // Ange ett framgångsmeddelande
        $firstName = is_string($data['first_name'] ?? null) ? $data['first_name'] : '';
        $lastName  = is_string($data['last_name'] ?? null) ? $data['last_name'] : '';

        /** @var string $firstName */
        /** @var string $lastName */
        $this->request->session()->setFlashMessage(
            "Konto för $firstName $lastName har uppdaterats."
        );

        // Omdirigera till användarens startsida
        return new RedirectResponse(route('user.index'));
    }

    public function close(): Response
    {
        $this->before();

        $id = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($id) && !is_string($id)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($id);

        if ($user && $user->isAdmin()) {
            throw new NotAuthorizedException('You are not authorized to close this account.');
        }

        if ($user === null) {
            throw new NotAuthorizedException('User not found.');
        }

        $user->loadMissing('status');

        /** @var \App\Models\Status|null $status */
        $status = $user->getRelation('status');

        if (!$status instanceof Status) {
            throw new RuntimeException('Status relation is not loaded or invalid.');
        }

        $status->fill(['status' => 'closed', 'active' => 'offline']);
        $status->save();

        $user->delete();

        $this->request->session()->destroy(); // Förstör sessionen

        return new RedirectResponse(route('auth.logout.close-message'));
    }

    public function delete(): Response
    {
        $this->before();

        $id = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($id) && !is_string($id)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($id);

        if ($user && $user->isAdmin()) {
            throw new NotAuthorizedException('You are not authorized to delete this user.');
        }

        if ($user === null) {
            throw new NotAuthorizedException('User not found.');
        }

        $userDirectory = ROOT_PATH . '/public/images/user/' . $user->id;

        // Kontrollera om katalogen existerar innan du försöker ta bort den
        if (is_dir($userDirectory)) {
            // Iterera och ta bort alla filer i katalogen
            $files = array_diff(scandir($userDirectory), ['.', '..']);
            foreach ($files as $file) {
                unlink($userDirectory . '/' . $file);
            }

            // Ta bort själva katalogen
            rmdir($userDirectory);
        }

        $user->forceDelete();

        $this->request->session()->destroy(); // Förstör sessionen        $this->auth->logout();

        return new RedirectResponse(route('auth.logout.delete-message'));
    }
}
