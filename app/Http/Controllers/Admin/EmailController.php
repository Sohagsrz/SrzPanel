<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Email;
use App\Models\Domain;
use Illuminate\Http\Request;
use App\Services\EmailService;
use App\Services\CacheService;
use Illuminate\Support\Facades\Hash;

class EmailController extends Controller
{
    protected $emailService;
    protected $cacheService;

    public function __construct(
        EmailService $emailService,
        CacheService $cacheService
    ) {
        $this->emailService = $emailService;
        $this->cacheService = $cacheService;
    }

    public function index()
    {
        $emails = $this->cacheService->remember(
            CacheService::KEY_EMAIL_LIST,
            fn() => Email::with('domain')->paginate(10),
            300 // Cache for 5 minutes
        );
        
        return view('admin.email.index', compact('emails'));
    }

    public function create()
    {
        $domains = $this->cacheService->remember(
            CacheService::KEY_DOMAIN_LIST,
            fn() => Domain::all(),
            3600 // Cache for 1 hour
        );
        
        return view('admin.email.create', compact('domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'quota' => 'required|integer|min:1',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';

        $email = $this->emailService->createEmailAccount($validated);
        
        // Clear email list cache
        $this->cacheService->forget(CacheService::KEY_EMAIL_LIST);

        return redirect()->route('email.show', $email)
            ->with('success', 'Email account created successfully.');
    }

    public function show(Email $email)
    {
        $email->load('domain');
        
        $stats = $this->cacheService->remember(
            CacheService::KEY_EMAIL_STATS . ".{$email->id}",
            fn() => $this->emailService->getStats($email),
            300 // Cache for 5 minutes
        );

        return view('admin.email.show', compact('email', 'stats'));
    }

    public function edit(Email $email)
    {
        $domains = $this->cacheService->remember(
            CacheService::KEY_DOMAIN_LIST,
            fn() => Domain::all(),
            3600 // Cache for 1 hour
        );
        
        return view('admin.email.edit', compact('email', 'domains'));
    }

    public function update(Request $request, Email $email)
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',
            'quota' => 'required|integer|min:1',
            'status' => 'required|in:active,suspended',
        ]);

        $this->emailService->updateEmailAccount($email, $validated);
        
        // Clear email list cache
        $this->cacheService->forget(CacheService::KEY_EMAIL_LIST);
        // Clear email stats cache
        $this->cacheService->forget(CacheService::KEY_EMAIL_STATS . ".{$email->id}");

        return redirect()->route('email.show', $email)
            ->with('success', 'Email account updated successfully.');
    }

    public function destroy(Email $email)
    {
        $this->emailService->deleteEmailAccount($email);
        
        // Clear email list cache
        $this->cacheService->forget(CacheService::KEY_EMAIL_LIST);
        // Clear email stats cache
        $this->cacheService->forget(CacheService::KEY_EMAIL_STATS . ".{$email->id}");

        return redirect()->route('email.index')
            ->with('success', 'Email account deleted successfully.');
    }

    public function updateForward(Request $request, Email $email)
    {
        $validated = $request->validate([
            'forward_to' => 'required|email',
        ]);

        $this->emailService->updateForward($email, $validated['forward_to']);
        
        // Clear email stats cache
        $this->cacheService->forget(CacheService::KEY_EMAIL_STATS . ".{$email->id}");

        return redirect()->route('email.show', $email)
            ->with('success', 'Email forwarding updated successfully.');
    }

    public function updateAutoresponder(Request $request, Email $email)
    {
        $validated = $request->validate([
            'autoresponder_enabled' => 'required|boolean',
            'autoresponder_subject' => 'required_if:autoresponder_enabled,1|string|max:255',
            'autoresponder_message' => 'required_if:autoresponder_enabled,1|string'
        ]);

        $this->emailService->updateAutoresponder($email, $validated);

        return redirect()->route('email.show', $email)
            ->with('success', 'Autoresponder updated successfully.');
    }

    public function changePassword(Request $request, Email $email)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->emailService->changePassword($email, $validated['password']);

        return redirect()->route('email.show', $email)
            ->with('success', 'Password changed successfully.');
    }

    public function getStats(Email $email)
    {
        $stats = $this->emailService->getStats($email);
        return response()->json($stats);
    }
} 