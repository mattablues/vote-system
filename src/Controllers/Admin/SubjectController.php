<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Category;
use App\Models\Subject;
use InvalidArgumentException;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Validator;

class SubjectController extends AbstractController
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

        $subjects = Subject::with('category')
            ->orderBy('published')
            ->orderBy('id', 'desc')
            ->paginate(10, (int) $page);

        return $this->view('admin.subject.index', ['subjects' => $subjects]);
    }

    public function create(): Response
    {
        $categories = Category::all();

        return $this->view('admin.subject.create', ['categories' => $categories]);
    }

    public function store(): Response
    {
        $this->before();
        $data = $this->request->post;

        $categories = Category::all();

        $validator = new Validator($data, [
            'category_id' => 'required|numeric',
            'subject'     => 'required|min:5|max:300|unique:App\Models\Subject,subject',
        ]);

        if (!$validator->validate()) {
            $this->request->session()->set('old', $data);
            return $this->view('admin.subject.create', [
                'categories' => $categories,
                'errors'     => $validator->errors(),
            ]);
        }

        // Filtrera tillåtna fält om din request har stöd för det
        $data = $this->request->filterFields($data);
        $this->request->session()->remove('old');

        $subject = new Subject();

        $catVal = $data['category_id'] ?? 0;
        $categoryId = is_numeric($catVal) ? (int) $catVal : 0;

        $subVal = $data['subject'] ?? '';
        $subjectText = is_string($subVal) ? $subVal : '';

        $subject->fill([
            'category_id' => $categoryId,
            'subject' => $subjectText,
            'published' => 0,
        ]);

        $subject->save();

        $this->request->session()->remove('old');
        $this->request->session()->setFlashMessage('Ämnet har skapades, granska det innan det publiceras.');

        return new RedirectResponse(route('admin.subject.index'));
    }

    public function edit(string $id): Response
    {
        $subject = Subject::find($id);
        $categories = Category::all();

        if (!$subject) {
            throw new InvalidArgumentException('Subject not found');
        }

        return $this->view('admin.subject.edit', ['subject' => $subject, 'categories' => $categories]);
    }

    public function update(string $id): Response
    {
        $this->before();

        $data = $this->request->post; // Hämta formulärdata
        $subject = Subject::find($id);
        $categories = Category::all();

        // Kontrollera om ämnet existerar
        if (!$subject) {
            $this->request->session()->setFlashMessage('Ämnet hittades inte.', 'error');
            return new RedirectResponse(route('admin.subject.index'));
        }

        // Validera data inklusive avatar
        $validator = new Validator($data, [
            'category_id' => 'required|numeric',
            // Lägg till null-check på ID för säkerhets skull, men vi har redan kollat !$subject
            'subject' => 'required|min:5|max:300|unique:App\Models\Subject,subject,id=' . $subject->id,
        ]);

        if (!$validator->validate()) {
            // Om validering misslyckas, lagra gamla indata och returnera vy med felmeddelanden
            $this->request->session()->set('old', $data);

            return $this->view('admin.subject.edit', [
                'errors' => $validator->errors(),
                'subject' => $subject,
                'categories' => $categories,
            ]);
        }

        $this->request->session()->remove('old');

        // Säkra typerna innan fill
        $catVal = $data['category_id'] ?? 0;
        $categoryId = is_numeric($catVal) ? (int) $catVal : 0;

        $subVal = $data['subject'] ?? '';
        $subjectText = is_string($subVal) ? $subVal : '';

        $subject->fill(['category_id' => $categoryId, 'subject' => $subjectText]);
        $subject->save();

        $this->request->session()->setFlashMessage(
            "Ämne med ID {$id} har uppdaterats."
        );

        return new RedirectResponse(route('admin.subject.index'));
    }

    public function publish(string $id): Response
    {
        $this->before();

        $subject = Subject::find($id);

        if (!$subject) {
            $this->request->session()->setFlashMessage("Ämnet kunde inte hittas.");

            return new RedirectResponse(route('admin.subject.index'));
        }

        $subject->fill(['published' => 1]);
        $subject->save();

        $this->request->session()->setFlashMessage("Ämne med ID {$subject->id} har publicerats och kan nu röstas på");

        return new RedirectResponse(route('admin.subject.index'));
    }

    public function delete(string $id): Response
    {
        $subject = Subject::find($id);

        if (!$subject) {
            throw new InvalidArgumentException('Subject not found');
        }

        $subject->forceDelete();

        $this->request->session()->setFlashMessage(
            "Ämne med ID {$id} har raderats."
        );

        return new RedirectResponse(route('admin.subject.index'));
    }
}
