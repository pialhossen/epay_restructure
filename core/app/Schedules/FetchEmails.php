<?php
use App\Models\GeneralSetting;
use Webklex\PHPIMAP\Client;

class FetchEmails
{
    public function fetchEmails()
    {
        $settings = GeneralSetting::find(1);
        $imap_config = json_decode($settings->imap_config, true);

        // Override IMAP config from database
        config([
            'imap.accounts.dynamic.host' => $imap_config['host'],
            'imap.accounts.dynamic.port' => $imap_config['imap_port'],
            'imap.accounts.dynamic.encryption' => $imap_config['imap_encryption'],
            'imap.accounts.dynamic.validate_cert' => $imap_config['imap_validate_cert'] == '1',
            'imap.accounts.dynamic.username' => $imap_config['imap_username'],
            'imap.accounts.dynamic.password' => $imap_config['imap_password'],
            'imap.accounts.dynamic.protocol' => $imap_config['imap_protocol'],
        ]);

        // Make sure username/password exist
        if (!$imap_config['imap_username'] || !$imap_config['imap_password']) {
            $msg = 'Missing IMAP username or password.';
            Log::error($msg);
            return response()->json(['status' => 'error', 'message' => $msg], 500);
        }

        // IMPORTANT: connect to 'dynamic'
        $client = Client::account('dynamic');

        // connect, retry once if certificate validation fails
        try {
            $client->connect();
        } catch (\Throwable $e) {
            if ($imap_config['imap_validate_cert'] == '1') {
                config(['imap.accounts.dynamic.validate_cert' => false]);
                $client = Client::account('dynamic');
                $client->connect();
            } else {
                Log::error('IMAP connect failed: ' . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => 'IMAP connect failed'], 500);
            }
        }

        $inbox = $client->getFolder('INBOX');
        $fromFilter = $imap_config['imap_filter_from'];

        $messages = $inbox->query()->unseen()->get();

        if ($fromFilter) {
            $messages = $messages->filter(function ($m) use ($fromFilter) {
                $f = $m->getFrom();
                if (is_iterable($f)) {
                    foreach ($f as $addr) {
                        if (isset($addr->mail) && stripos($addr->mail, $fromFilter) !== false) {
                            return true;
                        }
                    }
                    return false;
                }
                return stripos((string) $f, $fromFilter) !== false;
            });
        }

        $summaries = [];
        foreach ($messages as $m) {

            // FROM
            $from = '(unknown)';
            $f = $m->getFrom();
            if (is_iterable($f)) {
                foreach ($f as $addr) {
                    if (isset($addr->mail)) {
                        $from = $addr->mail;
                        break;
                    }
                }
            } else {
                $from = (string) $f ?: '(unknown)';
            }
            if (preg_match('/([\w.\-+]+@[\w.\-]+\.\w+)/', $from, $match)) {
                $from = $match[1];
            }

            // SUBJECT
            $subject = (string) $m->getSubject() ?: '(no subject)';

            // BODY
            $text = trim((string) $m->getTextBody()) ?: trim(strip_tags((string) $m->getHTMLBody()));
            $text = preg_replace('/[\r\n\t\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $text);
            $text = preg_replace('/\s+/', ' ', trim($text));

            // DATE
            $dateStr = null;
            try {
                $d = $m->getDate();
                if ($d instanceof \DateTime) {
                    $dateStr = $d->format('c');
                } elseif ($d) {
                    $dateStr = (string) $d;
                }
            } catch (\Throwable $e) {
            }

            $summaries[] = [
                'id' => method_exists($m, 'getMessageId') ? $m->getMessageId() : null,
                'from' => $from,
                'subject' => $subject,
                'date' => $dateStr,
                'body_preview' => mb_substr($text, 0, 1000),
            ];
        }

        $client->disconnect();

        return response()->json(['status' => 'ok', 'data' => $summaries]);
    }

    function getAndSaveUnreadEmails()
    {
        $data = $this->fetchEmails();
        // Start From here
    }
    public function __invoke()
    {
        $this->getAndSaveUnreadEmails();
    }
}