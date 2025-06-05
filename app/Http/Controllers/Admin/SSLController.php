<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LetsEncryptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SSLController extends Controller
{
    protected $letsEncryptService;

    public function __construct(LetsEncryptService $letsEncryptService)
    {
        $this->letsEncryptService = $letsEncryptService;
    }

    public function index()
    {
        $certificates = collect(Storage::disk('ssl')->files())
            ->filter(function ($file) {
                return Str::endsWith($file, '.crt');
            })
            ->map(function ($file) {
                $certPath = Storage::disk('ssl')->path($file);
                $certData = openssl_x509_parse(file_get_contents($certPath));
                
                return [
                    'name' => basename($file),
                    'domain' => $certData['subject']['CN'] ?? 'Unknown',
                    'issuer' => $certData['issuer']['O'] ?? 'Unknown',
                    'valid_from' => date('Y-m-d H:i:s', $certData['validFrom_time_t']),
                    'valid_to' => date('Y-m-d H:i:s', $certData['validTo_time_t']),
                ];
            })
            ->sortBy('domain')
            ->values();

        return view('admin.ssl.index', compact('certificates'));
    }

    public function create()
    {
        return view('admin.ssl.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|max:255',
            'certificate' => 'required|file|mimes:pem,crt',
            'private_key' => 'required|file|mimes:pem,key',
            'ca_bundle' => 'nullable|file|mimes:pem,crt',
        ]);

        $domain = $request->input('domain');
        $certificate = $request->file('certificate');
        $privateKey = $request->file('private_key');
        $caBundle = $request->file('ca_bundle');

        // Create SSL directory if it doesn't exist
        if (!Storage::disk('ssl')->exists('')) {
            Storage::disk('ssl')->makeDirectory('');
        }

        // Store certificate
        $certName = $domain . '.crt';
        Storage::disk('ssl')->putFileAs('', $certificate, $certName);

        // Store private key
        $keyName = $domain . '.key';
        Storage::disk('ssl')->putFileAs('', $privateKey, $keyName);

        // Store CA bundle if provided
        if ($caBundle) {
            $caName = $domain . '.ca-bundle';
            Storage::disk('ssl')->putFileAs('', $caBundle, $caName);
        }

        return redirect()->route('admin.ssl.index')
            ->with('success', 'SSL certificate installed successfully.');
    }

    public function show($name)
    {
        $certPath = Storage::disk('ssl')->path($name);
        if (!file_exists($certPath)) {
            abort(404);
        }

        $certData = openssl_x509_parse(file_get_contents($certPath));
        
        $certificate = [
            'name' => $name,
            'domain' => $certData['subject']['CN'] ?? 'Unknown',
            'issuer' => $certData['issuer']['O'] ?? 'Unknown',
            'valid_from' => date('Y-m-d H:i:s', $certData['validFrom_time_t']),
            'valid_to' => date('Y-m-d H:i:s', $certData['validTo_time_t']),
            'serial_number' => $certData['serialNumber'] ?? 'Unknown',
            'signature_type' => $certData['signatureTypeSN'] ?? 'Unknown',
            'subject' => $certData['subject'] ?? [],
            'issuer_details' => $certData['issuer'] ?? [],
        ];

        return view('admin.ssl.show', compact('certificate'));
    }

    public function destroy($name)
    {
        $certPath = Storage::disk('ssl')->path($name);
        if (!file_exists($certPath)) {
            abort(404);
        }

        // Delete certificate
        Storage::disk('ssl')->delete($name);

        // Delete associated private key
        $keyName = Str::beforeLast($name, '.crt') . '.key';
        if (Storage::disk('ssl')->exists($keyName)) {
            Storage::disk('ssl')->delete($keyName);
        }

        // Delete associated CA bundle
        $caName = Str::beforeLast($name, '.crt') . '.ca-bundle';
        if (Storage::disk('ssl')->exists($caName)) {
            Storage::disk('ssl')->delete($caName);
        }

        return redirect()->route('admin.ssl.index')
            ->with('success', 'SSL certificate removed successfully.');
    }

    public function requestLetsEncrypt(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'email' => 'required|email',
        ]);
        $result = $this->letsEncryptService->issueCertificate($request->domain, $request->email);
        if ($result['success']) {
            return redirect()->route('ssl.index')->with('success', $result['message']);
        }
        return redirect()->route('ssl.index')->with('error', $result['message']);
    }

    public function renewAll()
    {
        $result = $this->letsEncryptService->renewAll();
        if ($result['success']) {
            return redirect()->route('ssl.index')->with('success', $result['message']);
        }
        return redirect()->route('ssl.index')->with('error', $result['message']);
    }
} 