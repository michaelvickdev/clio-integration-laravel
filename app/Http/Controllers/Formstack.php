<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use App\ClioApiTokens;
use Illuminate\Support\Facades\Http;

class Formstack extends Controller
{
    public $tokens = null;
    public $url_contact = '';
    public $url_matters = '';

    public function __construct()
    {
        $this->tokens = ClioApiTokens::find(1);
        $url_contact = env('CLIO_API_URL') . 'contacts.json';
        $url_matters = env('CLIO_API_URL') . 'matters.json';
    }

    public function handleForm(Request $request)
    {
        $input = json_decode($request->getContent());
        if ($input->HandshakeKey != env('FORMSTACK_KEY')) {
            return Response::json(['error' => 'Invalid Form Key'],401);
        }

        $contact = $this->getContactByEmail($input->email->value);
        dump($contact);

        if ($contact['meta']['records'] == 0) {
            $data = [
                'data' =>
                    [
                        "first_name" => $input->name->value->first,
                        "middle_name" => $input->name->value->middle,
                        "last_name" => $input->name->value->last,
                        "email_addresses" => [
                            [
                                "name" => "Other",
                                "address" => $input->email->value,
                                "default_email" => true
                            ]
                        ],
                        "phone_numbers" => [
                            [
                                "name" => "Other",
                                "number" => $input->phone->value,
                                "default_number" => true
                            ]
                        ],
                        "type" => "Person",
                    ]
            ];
            dump($data);
            $createdContact = $this->createContact($data);
            dump($createdContact);
        }

        $associatedContact = $this->getContactByEmail($input->associated_email->value);
        if ($associatedContact['meta']['records'] == 0) {
            $data = [
                'data' =>
                    [
                        "first_name" => $input->associated_name->value->first,
                        "middle_name" => $input->associated_name->value->middle,
                        "last_name" => $input->associated_name->value->last,
                        "email_addresses" => [
                            [
                                "name" => "Other",
                                "address" => $input->associated_email->value,
                                "default_email" => true
                            ]
                        ],
                        "type" => "Person",
                    ]
            ];
            $createdAssociatedContact = $this->createContact($data);
        }
        dump($createdAssociatedContact);
        dd($input);
    }


    /**
     * Create Contact in Clio, return created contact
     *
     * @param array
     * @return array
     */
    public function createContact($data)
    {
        return Http::withToken($this->tokens->access_token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['json' => $data])
            ->post($this->url_contact)->json();
    }


    /**
     * Get Contact by email from Clio API
     *
     * @param string
     * @return array
     */
    public function getContactByEmail($email)
    {
        dump($email);
        dump($this->url_contact);
        return Http::withToken($this->tokens->access_token)
            ->withOptions(['query' => ['query' => $email]])
            ->get($this->url_contact)->json();
    }

    /**
     * Get Matters by Contact ID from Clio API
     *
     * @param string|integer
     * @return array
     */
    public function getMattersByContactID($contact_id)
    {
        return Http::withToken($this->tokens->access_token)
            ->withOptions(['query' => ['client_id' => $contact_id]])
            ->get($this->url_matters)->json();
    }
}
