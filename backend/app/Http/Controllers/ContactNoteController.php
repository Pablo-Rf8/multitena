<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use App\Services\CrmScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactNoteController extends Controller
{
    public function store(Request $request, int $contact, CrmScope $scope, Audit $audit): RedirectResponse
    {
        $contact = $scope->contacts($request->user())->findOrFail($contact);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $note = $contact->notes()->create(['body' => trim($data['body']), 'author_id' => $request->user()->id]);
        $audit->record('note.created', $note, $contact->company_id, ['contact_id' => $contact->id]);

        return redirect()->route('contacts.show', $contact)->with('status', 'Nota agregada.');
    }
}
