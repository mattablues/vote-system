<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Category;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Validator;

class CategoryController extends AbstractController
{
    public function index(): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $page = (int) $rawPage;

        $categories = Category::paginate(10, $page);

        return $this->view('admin.category.index', ['categories' => $categories]);
    }

    public function create(): Response
    {
        return $this->view('admin.category.create');
    }

    public function store(): Response
    {
        $this->before();

        $data = $this->request->post; // Hämta formulärdata

        // Validera data inklusive avatar
        $validator = new Validator($data, [
            'category' => 'required|min:5|max:200|unique:App\Models\Category,category',
            'description' => 'required|min:10|max:1000'
        ]);

        if (!$validator->validate()) {
            // Om validering misslyckas, lagra gamla indata och returnera vy med felmeddelanden
            $this->request->session()->set('old', $data);

            return $this->view('admin.category.create', [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $this->request->filterFields($data);
        $this->request->session()->remove('old');

        $category = new Category();
        $category->fill(['category' => $data['category'], 'description' => $data['description']]);
        $category->save();

        $this->request->session()->setFlashMessage(
            "Ny kategori har skapats."
        );

        return new RedirectResponse(route('admin.category.index'));
    }

    public function edit(string $id): Response
    {
        $category = Category::find($id);

        if (!$category) {
            throw new \InvalidArgumentException('Category not found');
        }

        return $this->view('admin.category.edit', ['category' => $category]);
    }

    public function update(string $id): Response
    {
        $this->before();

        $data = $this->request->post; // Hämta formulärdata
        $category = Category::find($id);

        if (!$category) {
            $this->request->session()->setFlashMessage('Kategori hittades inte.', 'error');
            return new RedirectResponse(route('admin.category.index'));
        }

        // Validera data inklusive avatar
        $validator = new Validator($data, [
            'category' => 'required|min:5|max:200|unique:App\Models\Category,category,id=' . $category->id,
            'description' => 'required|min:10|max:1000'
        ]);

        if (!$validator->validate()) {
            // Om validering misslyckas, lagra gamla indata och returnera vy med felmeddelanden
            $this->request->session()->set('old', $data);

            return $this->view('admin.category.create', [
                'errors' => $validator->errors(),
                'category' => $category
            ]);
        }

        $category->fill(['category' => $data['category'], 'description' => $data['description']]);

        $category->save();

        $this->request->session()->remove('old');

        $this->request->session()->setFlashMessage(
            "Kategori med ID $id har uppdaterats."
        );

        return new RedirectResponse(route('admin.category.index'));
    }

    public function delete(string $id): Response
    {
        $category = Category::find($id);

        if (!$category) {
            throw new \InvalidArgumentException('Category not found');
        }

        $category->forceDelete();

        $this->request->session()->setFlashMessage(
            "Kategori med ID $id har raderats."
        );

        return new RedirectResponse(route('admin.category.index'));
    }
}