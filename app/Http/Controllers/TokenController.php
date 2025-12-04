<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\TokenPackage;
use App\Models\Payment;
use App\Services\CinetPayService;
use Inertia\Inertia;

class TokenController extends Controller
{
    protected CinetPayService $cinetPayService;

    public function __construct(CinetPayService $cinetPayService)
    {
        $this->cinetPayService = $cinetPayService;
    }

    public function showBuyPage()
    {
        $packages = TokenPackage::where('is_active', true)
            ->orderBy('tokens')
            ->get();
        
        return Inertia::render('Tokens/Buy', [
            'packages' => $packages,
            'balance' => Auth::user()->tokens_balance ?? 0,
        ]);
    }

    public function index()
    {
        $packages = TokenPackage::where('is_active', true)
            ->orderBy('tokens')
            ->get();
        
        return response()->json([
            'packages' => $packages,
            'balance' => Auth::user()->tokens_balance ?? 0,
        ]);
    }

    public function buy(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:token_packages,id',
        ]);

        $package = TokenPackage::findOrFail($request->package_id);
        $user = Auth::user();

        $transactionId = 'TOK_' . Str::random(10);
        
        $paymentData = [
            'transaction_id' => $transactionId,
            'amount' => $package->price_cents,
            'currency' => $package->currency,
            'description' => "Achat de tokens - {$package->name}",
            'customer_id' => (string) $user->id,
            'customer_name' => $user->name,
            'customer_surname' => $user->cml_fullname ?? '',
            'customer_email' => $user->email,
            'customer_phone_number' => $user->phone ?? '',
            'customer_address' => '',
            'customer_city' => '',
            'customer_country' => 'CI',
            'customer_state' => '',
            'customer_zip_code' => '',
        ];

        try {
            $result = $this->cinetPayService->initiatePayment($paymentData);

            if (!$result['success']) {
                return response()->json(['error' => $result['error']], 500);
            }

            // Create pending payment record
            Payment::create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'cinetpay_transaction_id' => $result['data']['transaction_id'] ?? null,
                'amount' => $package->price_cents,
                'currency' => $package->currency,
                'status' => 'pending',
                'payment_method' => 'cinetpay',
                'description' => "Achat de {$package->tokens} tokens",
                'cinetpay_response' => $result['data'],
                'metadata' => ['package_id' => $package->id, 'tokens' => $package->tokens],
            ]);

            return response()->json([
                'payment_url' => $result['payment_url'],
                'transaction_id' => $transactionId,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
