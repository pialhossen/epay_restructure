<?php

namespace App\Schedules;

use App\Models\ForwardEmail;
use App\Models\GeneralSetting;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Log;

class FetchEmails
{
    public function fetchEmails()
    {
        $settings = GeneralSetting::find(1);
        $imap_config = json_decode($settings->imap_config, true);

        config([
            'imap.accounts.dynamic.host' => $imap_config['imap_host'],
            'imap.accounts.dynamic.port' => $imap_config['imap_port'],
            'imap.accounts.dynamic.encryption' => $imap_config['imap_encryption'],
            'imap.accounts.dynamic.validate_cert' => $imap_config['imap_validate_cert'] == '1',
            'imap.accounts.dynamic.username' => $imap_config['imap_username'],
            'imap.accounts.dynamic.password' => $imap_config['imap_password'],
            'imap.accounts.dynamic.protocol' => $imap_config['imap_protocol'],
        ]);

        $client = Client::account('dynamic');

        try {
            $client->connect();
        } catch (\Throwable $e) {
            if (true) {
                config(['imap.accounts.' . config('imap.default') . '.validate_cert' => false]);
                $client = Client::account('default');
                $client->connect();
            }
        }

        $inbox = $client->getFolder('INBOX');
        // $fromFilter = 'workwithpiyal@gmail.com';
        $fromFilter = $imap_config['imap_filter_from'];

        $messages = $inbox->query()->unseen()->get();

        if ($fromFilter) {
            $messages = $messages->filter(function ($m) use ($fromFilter) {
                $f = $m->getFrom();
                if (is_iterable($f)) {
                    foreach ($f as $addr) {
                        if(isset($addr->mail)){
                            foreach($fromFilter as $email){
                                if (stripos($addr->mail, $email) !== false) {
                                    return true;
                                }
                            } 
                        }
                    }
                    return false;
                }
                foreach($fromFilter as $email){
                    if (stripos($f, $email) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        $summaries = [];
        foreach ($messages as $m) {
            $f = $m->getFrom();
            try {
                $m->setFlag('Seen');
            } catch (\Throwable $e) {
                logger("Failed to mark email as seen: " . $e->getMessage());
            }
            $fromStr = '(unknown)';
            if (is_iterable($f)) {
                foreach ($f as $addr) {
                    if (isset($addr->mail)) {
                        $fromStr = $addr->mail;
                        break;
                    }
                }
            } else {
                $fromStr = (string) $f ?: '(unknown)';
            }
            if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $fromStr, $matches)) {
                $fromStr = $matches[1];
            }

            $subj = $m->getSubject();
            $subject = (string) $subj ?: '(no subject)';

            $text = trim((string) $m->getTextBody()) ?: trim(strip_tags((string) $m->getHTMLBody()));
            $text = preg_replace('/[\r\n\t\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $text);
            $text = preg_replace('/\s+/', ' ', trim($text));
            $dateStr = null;
            if (method_exists($m, 'getDate')) {
                try {
                    $d = $m->getDate();
                    if ($d instanceof \DateTime) {
                        $dateStr = $d->format('c');
                    } elseif ($d) {
                        $dateStr = (string) $d;
                    }
                } catch (\Throwable $e) {
                    $dateStr = null;
                }
            }
            $summaries[] = [
                'id' => method_exists($m, 'getMessageId') ? $m->getMessageId() : null,
                'from' => $fromStr,
                'subject' => $subject,
                'date' => $dateStr,
                'body_preview' => mb_substr($text, 0, 1000),
            ];
        }

        $client->disconnect();
        return ['status' => 'ok', 'data' => $summaries];
    }


    public function saveEmail($data)
    {
        $email = new ForwardEmail();
        $email->from = $data['from'] ?? '';
        $email->subject = $data['subject'] ?? '';
        $email->date = $data['date'] ?? '';
        $email->body = $data['body_preview'] ?? '';
        $email->save();
    }


    public function getAndSaveUnreadEmails()
    {
        $result = $this->fetchEmails();

        if ($result['status'] !== 'ok')
            return;

        $emails = $result['data'];

        foreach ($emails as $email) {
            $this->saveEmail($email);
        }
    }


    public function __invoke()
    {
        $this->getAndSaveUnreadEmails();
    }
}
