<?php
namespace App\Http\Controllers;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Common\PayPalModel;
use PayPal\Rest\ApiContext;
//require __DIR__  . '/PayPal-PHP-SDK/autoload.php';
//require __DIR__  . '/PayPal-PHP-SDK/autoload.php';

class PaypalPaymentController extends Controller
{
        public function __construct()
    {
        $paypal_conf = Config::get('paypal');
        
        $this->_api_context =  new \PayPal\Rest\ApiContext(new OAuthTokenCredential(
                $paypal_conf['client_id'],
                $paypal_conf['secret'])
        );
        $this->_api_context->setConfig($paypal_conf['settings']);
    }



//    // by deepseek
//     public function payWithpaypal(Request $request)
// {
//     try {
//         // Validate session and order existence
//         if (!session()->has('order_id')) {
//             throw new \Exception('Order ID not found in session');
//         }

//         // Load order with necessary relationships
//         $order = Order::with(['details', 'customer'])->find(session('order_id'));
        
//         if (!$order) {
//             throw new \Exception('Order not found');
//         }

//         // Prepare customer name safely
//         $customerName = optional($order->customer)->f_name ?? 'Customer';
//         $tr_ref = Str::random(6) . '-' . rand(1, 1000);
//         $formattedAmount = number_format($order->order_amount, 2, '.', '');

//         // Validate currency code
//         $currencyCode = Helpers::currency_code();
//         if (empty($currencyCode)) {
//             throw new \Exception('Currency code not configured');
//         }

//         // Set up PayPal payment items
//         $payer = new Payer();
//         $payer->setPaymentMethod('paypal');

//         $item = (new Item())
//             ->setName(substr($customerName, 0, 127)) // PayPal has 127 char limit
//             ->setCurrency($currencyCode)
//             ->setQuantity(1)
//             ->setPrice($formattedAmount);

//         $itemList = (new ItemList())->setItems([$item]);

//         $amount = (new Amount())
//             ->setCurrency($currencyCode)
//             ->setTotal($formattedAmount);

//         $transaction = (new Transaction())
//             ->setAmount($amount)
//             ->setItemList($itemList)
//             ->setDescription(substr($tr_ref, 0, 127)); // PayPal has 127 char limit

//         $redirectUrls = (new RedirectUrls())
//             ->setReturnUrl(URL::route('paypal-status'))
//             ->setCancelUrl(URL::route('payment-fail'));

//         $payment = (new Payment())
//             ->setIntent('Sale')
//             ->setPayer($payer)
//             ->setRedirectUrls($redirectUrls)
//             ->setTransactions([$transaction->toArray()]); // Explicitly convert to array

//         // Create payment
//         $payment->create($this->_api_context);

//         // Find approval URL
//         $approvalUrl = null;
//         foreach ($payment->getLinks() as $link) {
//             if ($link->getRel() === 'approval_url') {
//                 $approvalUrl = $link->getHref();
//                 break;
//             }
//         }

//         if (!$approvalUrl) {
//             throw new \Exception('No approval URL found in PayPal response');
//         }

//         // Update order
//         $order->update([
//             'transaction_reference' => $payment->getId(),
//             'payment_method' => 'paypal',
//             'order_status' => 'pending_payment',
//             'updated_at' => now()
//         ]);

//         Session::put('paypal_payment_id', $payment->getId());
//         Session::put('transaction_reference', $tr_ref);

//         return Redirect::away($approvalUrl);

//     } catch (\Exception $ex) {
//         \Log::error('PayPal Payment Error: ' . $ex->getMessage());
        
//         // More specific error handling
//         if (str_contains($ex->getMessage(), 'currency')) {
//             Toastr::error(trans('messages.your_currency_is_not_supported', 
//                 ['method' => trans('messages.paypal')]));
//         } else {
//             Toastr::error('Payment failed: ' . $ex->getMessage());
//         }

//         return back()->withInput();
//     }
// }


//   only for testing
public function payWithpaypal(Request $request)
{
    try {
        // Simple hardcoded test payment
        $payment = (new Payment())
            ->setIntent('sale')
            ->setPayer((new Payer())->setPaymentMethod('paypal'))
            ->setTransactions([
                (new Transaction())
                    ->setAmount((new Amount())
                        ->setCurrency('USD')  // Hardcoded for testing
                        ->setTotal('15.00')   // Fixed amount for testing
                    )
                    ->setDescription('Test payment')
            ])
            ->setRedirectUrls((new RedirectUrls())
                ->setReturnUrl(URL::route('paypal-status'))
                ->setCancelUrl(URL::route('payment-fail'))
            );

        // Debug: Log the payment request before sending
        \Log::debug('TEST PayPal Request: ', $payment->toArray());

        // Create payment
        $payment->create($this->_api_context);

        // Debug: Log the payment response
        \Log::debug('TEST PayPal Response: ', $payment->toArray());

        // Find approval URL
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() === 'approval_url') {
                // Temporary - store minimal session data
                Session::put('paypal_payment_id', $payment->getId());
                return Redirect::away($link->getHref());
            }
        }

        throw new \Exception('No approval URL found in PayPal response');

    } catch (\Exception $ex) {
        \Log::error('TEST PayPal Error: ' . $ex->getMessage());
        Toastr::error('Test payment failed: ' . $ex->getMessage());
        return back();
    }
}
    // by defult
    // public function payWithpaypal(Request $request)
    // {
       
    //     $order = Order::with(['details'])->where(['id' => session('order_id')])->first();
 
    //     $tr_ref = Str::random(6) . '-' . rand(1, 1000);

    //     $payer = new Payer();
    //     $payer->setPaymentMethod('paypal');

    //     $items_array = [];
    //     $item = new Item();
    //     $number = sprintf("%0.2f", $order['order_amount']);
    //     $item->setName($order->customer['f_name'])
    //         ->setCurrency(Helpers::currency_code())
    //         ->setQuantity(1)
    //         ->setPrice($number);
         
    //     array_push($items_array, $item);

    //     $item_list = new ItemList();
    //     $item_list->setItems($items_array);

    //     $amount = new Amount();
     
    //     $amount->setCurrency(Helpers::currency_code())
    //         ->setTotal($number);
    //     \session()->put('transaction_reference', $tr_ref);
    //     $transaction = new Transaction();
    //     $transaction->setAmount($amount)
    //         ->setItemList($item_list)
    //         ->setDescription($tr_ref);
       
    //     $redirect_urls = new RedirectUrls();
    //     $redirect_urls->setReturnUrl(URL::route('paypal-status'))
    //     ->setCancelUrl(URL::route('payment-fail'));

    //     $payment = new Payment();
    //     $payment->setIntent('Sale')
    //         ->setPayer($payer)
    //         ->setRedirectUrls($redirect_urls)
    //         ->setTransactions(array($transaction));
        
    //     try {
            
    //         $payment->create($this->_api_context);
           
    //          /**
    //      * Get redirect url
    //      * The API response provides the url that you must redirect
    //      * the buyer to. Retrieve the url from the $payment->getLinks() method
    //      *
    //      */
    
    //     foreach ($payment->getLinks() as $key => $link) {
            
    //         if ($link->getRel() == 'approval_url') {
                
    //             $redirectUrl = $link->getHref();
                
    //             break;
    //         }
    
    //     }
     
    //         DB::table('orders')
    //             ->where('id', $order->id)
    //             ->update([
    //                 'transaction_reference' => $payment->getId(),
    //                 'payment_method' => 'paypal',
    //                 'order_status' => 'success',
    //                 'failed' => now(),
    //                 'updated_at' => now()
    //             ]);
       
    //         Session::put('paypal_payment_id', $payment->getId());
             
    //         if (isset($redirectUrl)) {
             
    //             return Redirect::away($redirectUrl);
    //         }else{
    //             dd("bye");
    //         }

    //     } catch (\Exception $ex) {
    //        dd($ex->getData());
    //            //   Toastr::error(trans($ex->getData(),['method'=>trans('messages.paypal')]));

    //         Toastr::error(trans('messages.your_currency_is_not_supported',['method'=>trans('messages.paypal')]));
    //         return back();
    //     }

    //     Session::put('error', trans('messages.config_your_account',['method'=>trans('messages.paypal')]));
    //     return back();
    // }
    
    public function getPaymentStatus(Request $request)
    {
        $payment_id = Session::get('paypal_payment_id');
        if (empty($request['PayerID']) || empty($request['token'])) {
            Session::put('error', trans('messages.payment_failed'));
            return Redirect::back();
        }

        $payment = Payment::get($payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($request['PayerID']);

        /**Execute the payment **/
        $result = $payment->execute($execution, $this->_api_context);
        $order = Order::where('transaction_reference', $payment_id)->first();
        if ($result->getState() == 'approved') {

            $order->transaction_reference = $payment_id;
            $order->payment_method = 'paypal';
            $order->payment_status = 'paid';
            $order->order_status = 'confirmed';
            $order->confirmed = now();
            $order->save();
            /*try {
                Helpers::send_order_notification($order);
            } catch (\Exception $e) {
            } */


            return redirect('&status=success');
            /*if ($order->callback != null) {
                return redirect($order->callback . '&status=success');
            }else{
                return \redirect()->route('payment-success');
            }*/
        }

        $order->order_status = 'failed';
        $order->failed = now();
        $order->save();
        return redirect('&status=fail');
        /*if ($order->callback != null) {
            return redirect($order->callback . '&status=fail');
        }else{
            return \redirect()->route('payment-fail');
        }*/
    }
}