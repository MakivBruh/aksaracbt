<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamSessionController extends Controller
{
    public function index()
    {
        $sessions = ExamSession::latest('starts_at')->paginate(20);

        return view('admin.sessions.index', compact('sessions'));
    }

    public function create()
    {
        return view('admin.sessions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);

        ExamSession::create($data);

        return redirect()->route('admin.sessions.index')
            ->with('success', 'Token tryout berhasil dibuat.');
    }

    public function edit(ExamSession $session)
    {
        return view('admin.sessions.edit', compact('session'));
    }

    public function update(Request $request, ExamSession $session): RedirectResponse
    {
        $data = $this->validated($request, $session);
        $data['is_active'] = $request->boolean('is_active');

        $session->update($data);

        return redirect()->route('admin.sessions.index')
            ->with('success', 'Token tryout berhasil diperbarui.');
    }

    private function validated(Request $request, ?ExamSession $session = null): array
    {
        $request->merge([
            'token' => strtoupper((string) $request->input('token')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'token' => [
                'required',
                'string',
                'max:64',
                'alpha_dash:ascii',
                Rule::unique('exam_sessions', 'token')->ignore($session),
            ],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
        ]);

        return $data;
    }
}
