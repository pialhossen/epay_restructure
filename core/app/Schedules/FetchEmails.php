<?php

namespace App\Schedules;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\ForwardEmail;
use App\Models\GeneralSetting;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Log;

class FetchEmails
{
    function stripQuotedReply($text)
    {
        // Remove common reply indicators
        $patterns = [
            "/On\s.*wrote:/i",
            "/-----Original Message-----/i",
            "/From:\s.*\n/i",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)) {
                $pos = $match[0][1];
                return trim(substr($text, 0, $pos));
            }
        }

        return trim($text);
    }
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
        $fromFilter = $imap_config['imap_filter_from'];

        $sinceDate = Carbon::now('Asia/Dhaka')
                ->subDays(1)
                ->format('d-M-Y');
        
        logger("sinceDate: ".$sinceDate);
        
        $messages = $inbox->query()
        ->unseen()
        ->since($sinceDate)
        ->limit(5)
        ->get();

        logger("Unread Email Found: " . count($messages));
        
        if ($fromFilter) {
            foreach ($messages as $m) {
                try {
                    logger("Making Email As Read. Subject: " . $m->getSubject());
                    $m->setFlag('Seen');
                } catch (\Throwable $e) {
                    logger("Failed to mark email as seen: " . $e->getMessage());
                }
            }
            $messages = $messages->filter(function ($m) use ($fromFilter) {
                $f = $m->getFrom();
                if (is_iterable($f)) {
                    foreach ($f as $addr) {
                        if (isset($addr->mail)) {
                            foreach ($fromFilter as $email) {
                                if (stripos($addr->mail, $email) !== false) {
                                    return true;
                                }
                            }
                        }
                    }
                    return false;
                }
                foreach ($fromFilter as $email) {
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

            // ------------------------
            // Updated body extraction
            // ------------------------
            $body = $m->getTextBody();
            if (empty(trim($body))) {
                $body = $m->getHTMLBody();
            }
            $text = trim(strip_tags($body));
            $text = preg_replace('/[\r\n\t\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = $this->stripQuotedReply($text);
            // ------------------------

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
            $is_hidden = false;
            $words = array_filter(array_map('trim', $imap_config['word_array'] ?? []));
            if (
                Str::contains($subject, $words, ignoreCase: true) ||
                Str::contains($text, $words, ignoreCase: true)
            ) {
                $is_hidden = true;
            }

            $summaries[] = [
                'id' => method_exists($m, 'getMessageId') ? $m->getMessageId() : null,
                'from' => $fromStr,
                'subject' => $subject,
                'date' => $dateStr,
                'is_hidden' => $is_hidden,
                'body' => $text,
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
        $email->is_hidden = $data['is_hidden'] ?? '';
        $email->body = $data['body'] ?? '';
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
        logger('Fetch Emails Schedules Started');
        $this->getAndSaveUnreadEmails();
        logger('Fetch Emails Schedules Finished');
    }
}
