<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Auth;

class GmailController extends Controller
{
    private function getGmailService(): Gmail
    {
        $googleToken = GoogleToken::where('user_id', Auth::id())->firstOrFail();

        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessToken(json_decode($googleToken->access_token, true));

        return new Gmail($client);
    }

    public function index()
    {
        try {
            $service  = $this->getGmailService();
            $messages = $service->users_messages->listUsersMessages('me', [
                'maxResults' => 20,
                'q'          => 'is:unread',  // hanya email belum dibaca
            ]);

            $emails = [];
            foreach ($messages->getMessages() as $message) {
                $detail   = $service->users_messages->get('me', $message->getId(), ['format' => 'metadata']);
                $headers  = collect($detail->getPayload()->getHeaders());

                $emails[] = [
                    'id'      => $message->getId(),
                    'subject' => $headers->firstWhere('name', 'Subject')['value'] ?? '(no subject)',
                    'from'    => $headers->firstWhere('name', 'From')['value'] ?? '',
                    'date'    => $headers->firstWhere('name', 'Date')['value'] ?? '',
                ];
            }

            return view('emails.index', compact('emails'));

        } catch (\Exception $e) {
            return redirect('/google/redirect')->with('error', 'Hubungkan akun Google dulu!');
        }
    }
}