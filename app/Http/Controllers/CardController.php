<?php

namespace App\Http\Controllers;

use App\Models\CardDetails;
use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;
use JWTAuth;

class CardController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }

            $cards = CardDetails::with('user')->where('user_id', $user->id)->get();

            return $this->responseSuccess($cards);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function store(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $setting = Setting::where('key', 'stripe_system')->where('is_disabled',1)->first();
            $stripeSecretKey = @$setting->value_details['is_stripeMode'] ? @$setting->value_details['stripeLiveSecretKey'] : @$setting->value_details['stripeTestSecretKey'];
            $stripe = new \Stripe\StripeClient($stripeSecretKey);

            if (empty($user->customer_stripe_id)) {

                $customer = $stripe->customers->create([
                    'email'          => $user->email,
                    'name'           => @$user->first_name . ' ' . @$user->last_name,
                    'payment_method' => $request->payment_method,
                ]);

                $user->update(['customer_stripe_id' => $customer->id]);
            }
            $customer = $stripe->customers->retrieve($user->customer_stripe_id);

            $stripe->paymentMethods->attach(
                $request->payment_method,
                ['customer' => $customer->id]
            );

            $user->update(['payment_method'  => $request->payment_method]);

            $paymentMethodDefult = $stripe->customers->retrievePaymentMethod(
                $customer->id,
                $user->payment_method,
                []
            );

            if ($paymentMethodDefult && $user->payment_method) {

                $lastActiveCard = $user->cardActive;
                if ($lastActiveCard) {
                    $lastActiveCard->status = 1;
                    $lastActiveCard->save();
                }

                CardDetails::create([
                    'user_id'   => $user->id,
                    'brand'     => $paymentMethodDefult->card->brand,
                    'last4'     => $paymentMethodDefult->card->last4,
                    'exp_month' => $paymentMethodDefult->card->exp_month,
                    'exp_year'  => $paymentMethodDefult->card->exp_year,
                    'status'    => 2,
                    'stipe_payment_id' => $user->payment_method
                ]);
            }
            return $this->responseSuccess('Card created successfully.');
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $card = CardDetails::findorFail($id);
            if (empty($card)) {
                return $this->responseException('Not found card', 404);
            }

            $card->delete($id);

            return $this->responseSuccess("Delete card success");
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function activeCard(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $card = CardDetails::findorFail($request->card_id);
            if (empty($card)) {
                return $this->responseException('Not found card', 404);
            }

            $lastActiveCard = $user->cardActive;
            $lastActiveCard->status = CardDetails::SETACTIVE;
            $lastActiveCard->save();
            
            $card->status = CardDetails::ACTIVETED;
            $card->save();

            return $this->responseSuccess("defult card successfully");
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}