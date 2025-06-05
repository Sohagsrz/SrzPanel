<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class LetsEncryptService
{
    protected $isWindows;

    public function __construct()
    {
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Issue a new SSL certificate for a domain using Let's Encrypt.
     */
    public function issueCertificate($domain, $email)
    {
        if ($this->isWindows) {
            return $this->issueWindows($domain, $email);
        }
        return $this->issueLinux($domain, $email);
    }

    protected function issueLinux($domain, $email)
    {
        $cmd = "certbot certonly --webroot -w /var/www/html -d {$domain} --agree-tos --email {$email} --non-interactive --quiet";
        $result = Process::run($cmd);
        if ($result->successful()) {
            return [
                'success' => true,
                'message' => 'Certificate issued successfully.'
            ];
        }
        Log::error('LetsEncrypt Linux error: ' . $result->errorOutput());
        return [
            'success' => false,
            'message' => $result->errorOutput()
        ];
    }

    protected function issueWindows($domain, $email)
    {
        $cmd = "wacs.exe --target manual --host {$domain} --emailaddress {$email} --accepttos --installation iis --store centralssl --centralsslstore C:\\CentralSSL --notaskscheduler --usedefaulttaskuser --verbose";
        $result = Process::run("powershell -Command \"cd C:\\win-acme; {$cmd}\"");
        if ($result->successful()) {
            return [
                'success' => true,
                'message' => 'Certificate issued successfully.'
            ];
        }
        Log::error('LetsEncrypt Windows error: ' . $result->errorOutput());
        return [
            'success' => false,
            'message' => $result->errorOutput()
        ];
    }

    /**
     * Renew all certificates.
     */
    public function renewAll()
    {
        if ($this->isWindows) {
            $cmd = "wacs.exe --renew --baseuri https://acme-v02.api.letsencrypt.org/";
            $result = Process::run("powershell -Command \"cd C:\\win-acme; {$cmd}\"");
        } else {
            $cmd = "certbot renew --quiet";
            $result = Process::run($cmd);
        }
        if ($result->successful()) {
            return [
                'success' => true,
                'message' => 'Certificates renewed.'
            ];
        }
        Log::error('LetsEncrypt renew error: ' . $result->errorOutput());
        return [
            'success' => false,
            'message' => $result->errorOutput()
        ];
    }
} 