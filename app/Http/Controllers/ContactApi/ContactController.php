<?php

namespace App\Http\Controllers\ContactApi;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Contact\AddContactRequest;
use App\Http\Requests\Contact\EditContactRequest;
use App\Http\Requests\Contact\ListContactRequest;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Responses\Contact\ContactCrudResponse;
use App\Helpers\Status;

class ContactController extends Controller
{
    /**
     * Adds a new contact to the user's contact list.
     *
     * @param AddContactRequest $request The request containing the contact data.
     * @param ContactCrudResponse $contactResponse The response object to use.
     *
     * @return JsonResponse The response containing the newly created contact data.
     */
    public function addContact(AddContactRequest $request, ContactCrudResponse $contactResponse)
    {
        // Start the transaction
        DB::beginTransaction();

        try {
            // Prepare contact data
            $contactData = $this->getContactData($request);
            $contactData['user_id'] = Auth::user()->id;

            // Create the contact
            $contact = Contact::create($contactData);


            // Save associated data
            $this->saveContactAssociations($contact, $request);

            // Commit the transaction
            DB::commit();

            return $contactResponse->success($this->formatContactResponse($contact));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create contact', [
                'exception_message' => $e->getMessage(),
            ]);
            return $contactResponse->generalError('Failed to create contact. Please try again later.', 500);
        }
    }

    /**
     * Updates an existing contact in the user's contact list.
     *
     * @param int $contactId The contact ID to update.
     * @param EditContactRequest $request The request containing the updated contact data.
     * @param ContactCrudResponse $contactResponse The response object to use.
     *
     * @return JsonResponse The response containing the updated contact data.
     */
    public function updateContact($contactId, EditContactRequest $request, ContactCrudResponse $contactResponse)
    {
        DB::beginTransaction();

        try {
            // Find the contact by user_id and contactId
            $contact = Contact::active()->where('user_id', Auth::user()->id)->find($contactId);

            if (!$contact) {
                return $contactResponse->generalError('Contact not found', 404);
            }

            // Prepare contact data
            $contactData = $this->getContactData($request);

            // Update the contact
            $contact->update($contactData);

            // Update associated data (phones and emails)
            $this->saveContactAssociations($contact, $request);

            // Commit the transaction
            DB::commit();

            return $contactResponse->success($this->formatContactResponse($contact));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update contact', [
                'contactId' => $contactId,
                'exception_message' => $e->getMessage(),
            ]);
            return $contactResponse->generalError('Failed to update contact. Please try again later.', 500);
        }
    }

    /**
     * Soft deletes a contact in the user's contact list.
     *
     * @param int $contactId The contact ID to delete.
     * @param ContactCrudResponse $contactResponse The response object to use.
     *
     * @return JsonResponse The response with a success message.
     */
    public function deleteContact($contactId, ContactCrudResponse $contactResponse)
    {
        DB::beginTransaction();

        try {
            $contact = Contact::active()->where('user_id', Auth::user()->id)->find($contactId);

            if (!$contact) {
                return $contactResponse->generalError('Invalid id for contact', 404);
            }

            // // Delete associated phone numbers
            // $contact->phones()->delete();

            // // Delete associated emails
            // $contact->emails()->delete();

            // Delete the contact
            $contact->status = Status::DELETED;
            $contact->save();

            // Commit the transaction
            DB::commit();

            return $contactResponse->success(['message' => 'Contact trashed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete contact', [
                'contactId' => $contactId,
                'exception_message' => $e->getMessage(),
            ]);
            return $contactResponse->generalError('Failed to delete contact. Please try again later.', 500);
        }
    }

    /**
     * Retrieves and displays a contact's details from the user's contact list.
     *
     * @param int $contactId The contact ID to view.
     * @param ContactCrudResponse $contactResponse The response object to use.
     *
     * @return JsonResponse The response containing the contact data or an error message.
     */

    public function viewContact($contactId, ContactCrudResponse $contactResponse)
    {
        try {
            $contact = Contact::active()->where('user_id', Auth::user()->id)->find($contactId);

            if (!$contact) {
                return $contactResponse->generalError('Invalid id for contact', 404);
            }

            return $contactResponse->success($this->formatContactResponse($contact));
        } catch (\Exception $e) {

            Log::error('Failed to retrieve contact', [
                'contactId' => $contactId,
                'exception_message' => $e->getMessage(),
            ]);
            return $contactResponse->generalError('Failed to retrieve contact. Please try again later.', 500);
        }
    }

    /**
     * Retrieves a list of contacts from the user's contact list, with optional filtering and sorting.
     *
     * @param ListContactRequest $request The request containing the filter and sort criteria.
     * @param ContactCrudResponse $contactResponse The response object to use.
     *
     * @return JsonResponse The response containing the list of contacts or an error message.
     */
    public function listContact(ListContactRequest $request, ContactCrudResponse $contactResponse)
    {
        // Define defaults
        $defaultLimit = 10;
        $defaultSortKey = 'created_at';
        $defaultSortOrder = 'asc';

        // Get request parameters
        $limit = (int) $request->input('limit', $defaultLimit);
        $page = (int) $request->input('page', 1);
        $sortKey = $request->input('sort_key', $defaultSortKey);
        if (empty($sortKey)) {
            $sortKey = $defaultSortKey;
        }
        $sortOrder = strtolower($request->input('sort_order', $defaultSortOrder)) === 'desc' ? 'desc' : 'asc';
        $search = $request->input('search', '');

        // Build the query
        $query = Contact::active()
            ->where('user_id', Auth::user()->id)
            ->applySearchFilters($search) // Custom query scope for search filters
            ->with([
                'phones' => fn($q) => $q->where('is_primary', 1),
                'emails' => fn($q) => $q->where('is_primary', 1),
            ]);

        // Handle sorting based on sort_key
        $query = $query->applySort($sortKey, $sortOrder);

        // Paginate the result
        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        // Transform data
        $items = $paginated->getCollection()->map(fn($contact) => [
            'id' => $contact->id,
            'last_name' => $contact->last_name,
            'email' => optional($contact->emails->first())->email,
            'birthday' => optional($contact->birthday)->format('d-m-Y'),
            'phone' => optional($contact->phones->first())->phone,
        ])->values();

        // Prepare response
        $response = [
            'pager' => [
                'page' => $paginated->currentPage(),
                'limit' => $paginated->perPage(),
                'page_count' => $paginated->lastPage(),
                'item_count' => $paginated->total(),
                'sort_key' => $sortKey,
                'sort_order' => $sortOrder,
            ],
            'items' => $items,
        ];

        return $contactResponse->success($response);
    }

    /**
     * Returns an array of contact data from the request, filtered to only include the fields that are relevant
     * to creating or updating a contact.
     *
     * @param Request $request The request containing the contact data.
     *
     * @return array The contact data.
     */
    private function getContactData($request)
    {
        return $request->only([
            'first_name',
            'last_name',
            'nick_name',
            'web_url',
            'address',
            'birthday',
            'notes',
            'country',
            'zip_code'
        ]);
    }

    /**
     * Save the phone numbers and email addresses associated with the contact.
     *
     * @param Contact $contact The contact to save the associations for.
     * @param Request $request The request containing the contact data.
     *
     * @return void
     */
    private function saveContactAssociations($contact, $request)
    {
        // Delete old phone numbers and emails
        $contact->phones()->delete();
        $contact->emails()->delete();

        // Insert new phone numbers
        foreach ($request->phone_numbers ?? [] as $phoneData) {
            $contact->phones()->create([
                'phone'       => $phoneData['phone'] ?? '',
                'tag'         => $phoneData['tag'] ?? '',
                'is_primary'  => $phoneData['is_primary'] ?? false,
            ]);
        }

        // Insert new email addresses
        foreach ($request->emails ?? [] as $emailData) {
            $contact->emails()->create([
                'email'       => $emailData['email'] ?? '',
                'tag'         => $emailData['tag'] ?? '',
                'is_primary'  => $emailData['is_primary'] ?? false,
            ]);
        }
    }

    /**
     * Formats a contact response into an array suitable for returning as JSON.
     *
     * @param Contact $contact The contact to format.
     *
     * @return array The formatted contact response.
     */
    private function formatContactResponse(Contact $contact): array
    {
        $contact->load('phones', 'emails');

        $data = $contact->toArray();
        $data['phone_numbers'] = $data['phones'] ?? [];
        unset($data['phones']);

        return $data;
    }
}
