<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment; // Ensure you're using your Appointment model
use Stripe\StripeClient;

class StripeController extends Controller
{
    // Phase 1: Stripe Payment Initialization
    public function stripe(Request $request)
    {
        $stripe = new StripeClient(config('stripe.stripe_sk'));

        // Create Stripe checkout session
        $response = $stripe->checkout->sessions->create([
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'lkr', // LKR for Sri Lanka
                        'product_data' => [
                            'name' => 'Please Pay',
                            'description' => "Pet Boarding Center: {$request->boarding_center}",
                        ],
                        'unit_amount' => $request->price * 100, // Amount in cents
                    ],
                    'quantity' => $request->quantity,
                ],
            ],
            'mode' => 'payment',
            'success_url' => route('success') . '?session_id={CHECKOUT_SESSION_ID}&id=' . $request->id,
            'cancel_url' => route('cancel'),
            'metadata' => [
                'pet_name' => $request->pet_name,
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'boarding_center' => $request->boarding_center,
                'profile_pic' => $request->profile_pic_url,
                'owner_first_name' => $request->owner_first_name,
                'owner_last_name' => $request->owner_last_name,
                
            ],
        ]);

    if (isset($response->id) && $response->id != '') {
        session()->put('product_name', $request->product_name);
        session()->put('quantity', $request->quantity);
        session()->put('price', $request->price);

        return redirect($response->url);
    } else {
        return redirect()->route('cancel');
    }
}

        // Redirect to Stripe checkout page
        

    // Phase 2: Stripe Payment Success Handling
    public function success(Request $request)
    {
        // Initialize Stripe client
        $stripe = new StripeClient(config('stripe.stripe_sk'));
        $session = $stripe->checkout->sessions->retrieve($request->session_id);
        $metadata = $session->metadata;


        // Get the appointment by ID (from the request)
        $appointment = Appointment::findOrFail($request->id);

        // Update the appointment's payment status
        $appointment->update([
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        return view('success',compact('metadata'));
    }

    public function cancel()
    {
        return redirect()->route('pet-owner.dashboard')->with('message', 'Payment was cancelled.');
    }
}