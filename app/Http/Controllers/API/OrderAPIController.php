<?php

/**
 * File name: OrderAPIController.php
 * Last modified: 2020.05.31 at 19:34:40
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers\API;


use App\Criteria\Orders\OrdersOfStatusesCriteria;
use App\Criteria\Orders\OrdersOfUserCriteria;
use App\Events\OrderChangedEvent;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\NewOrder;
use App\Notifications\StatusChangedOrder;
use App\Repositories\CartRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProductOrderRepository;
use App\Repositories\UserRepository;
use Braintree\Gateway;
use Laracasts\Flash\Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use Stripe\Token;
use OneSignal;
use Twilio\Rest\Client;
use DB;
use App\Models\User;


/**
 * Class OrderController
 * @package App\Http\Controllers\API
 */
class OrderAPIController extends Controller
{
    /** @var  OrderRepository */
    private $orderRepository;
    /** @var  ProductOrderRepository */
    private $productOrderRepository;
    /** @var  CartRepository */
    private $cartRepository;
    /** @var  UserRepository */
    private $userRepository;
    /** @var  PaymentRepository */
    private $paymentRepository;
    /** @var  NotificationRepository */
    private $notificationRepository;

    /**
     * OrderAPIController constructor.
     * @param OrderRepository $orderRepo
     * @param ProductOrderRepository $productOrderRepository
     * @param CartRepository $cartRepo
     * @param PaymentRepository $paymentRepo
     * @param NotificationRepository $notificationRepo
     * @param UserRepository $userRepository
     */
    public function __construct(OrderRepository $orderRepo, ProductOrderRepository $productOrderRepository, CartRepository $cartRepo, PaymentRepository $paymentRepo, NotificationRepository $notificationRepo, UserRepository $userRepository)
    {
        $this->orderRepository = $orderRepo;
        $this->productOrderRepository = $productOrderRepository;
        $this->cartRepository = $cartRepo;
        $this->userRepository = $userRepository;
        $this->paymentRepository = $paymentRepo;
        $this->notificationRepository = $notificationRepo;
    }

    /**
     * Display a listing of the Order.
     * GET|HEAD /orders
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // return $request;
        try {
            $this->orderRepository->pushCriteria(new RequestCriteria($request));
            $this->orderRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->orderRepository->pushCriteria(new OrdersOfStatusesCriteria($request));
            $this->orderRepository->pushCriteria(new OrdersOfUserCriteria(auth()->id()));
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        $orders = $this->orderRepository->all();

        return $this->sendResponse($orders->toArray(), 'Orders retrieved successfully');
    }

    /**
     * Display the specified Order.
     * GET|HEAD /orders/{id}
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        /** @var Order $order */
        if (!empty($this->orderRepository)) {
            try {
                $this->orderRepository->pushCriteria(new RequestCriteria($request));
                $this->orderRepository->pushCriteria(new LimitOffsetCriteria($request));
            } catch (RepositoryException $e) {
                Flash::error($e->getMessage());
            }
            $order = $this->orderRepository->findWithoutFail($id);
        }

        if (empty($order)) {
            return $this->sendError('Order not found');
        }

        return $this->sendResponse($order->toArray(), 'Order retrieved successfully');
    }

    public function sendSMSNotification($market)
    {
        if (count($market) > 0) {
            Storage::put('TWILIOoutPut.txt', ' Mob: ' . $market);
            $mobile = $market[0]->mobile;
            // dd($mobile);
            Storage::put('TWILIOoutPut.txt', ' Mob: ' . $market);
            // Storage::put('TWILIOoutPut1.txt', ' Mob: ' . $mobile);
            $sid    = "ACc6cf68bcc144d62635a9516a78ae4108";
            $token  = "5f2090a54c56c7a5859346f1d61e4b42";
            $twilio_number = "+14422694536";
            $message = "Hi there! you have a new order! - BaqalaApp";
            $client = new Client($sid, $token);
            $client->messages->create(
                $mobile,
                ['from' => $twilio_number, 'body' => $message]
            );
        }
    }

    /**
     * Store a newly created Order in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $payment = $request->only('payment');
        if (isset($payment['payment']) && $payment['payment']['method']) {
            if ($payment['payment']['method'] == "Credit Card (Stripe Gateway)") {
                return $this->stripPayment($request);
            } else {
                return $this->cashPayment($request);
            }
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function stripPayment(Request $request)
    {
        $input = $request->all();
        $amount = 0;
        try {
            $user = $this->userRepository->findWithoutFail($input['user_id']);
            if (empty($user)) {
                return $this->sendError('User not found');
            }
            $stripeToken = Token::create(array(
                "card" => array(
                    "number" => $input['stripe_number'],
                    "exp_month" => $input['stripe_exp_month'],
                    "exp_year" => $input['stripe_exp_year'],
                    "cvc" => $input['stripe_cvc'],
                    "name" => $user->name,
                )
            ));
            if ($stripeToken->created > 0) {
                if (empty($input['delivery_address_id'])) {
                    $order = $this->orderRepository->create(
                        $request->only('user_id', 'order_status_id', 'tax', 'hint')
                    );
                } else {
                    $order = $this->orderRepository->create(
                        $request->only('user_id', 'order_status_id', 'tax', 'delivery_address_id', 'delivery_fee', 'hint')
                    );
                }
                foreach ($input['products'] as $productOrder) {
                    $productOrder['order_id'] = $order->id;
                    $amount += $productOrder['price'] * $productOrder['quantity'];
                    $this->productOrderRepository->create($productOrder);
                }
                $amount += $order->delivery_fee;
                $amountWithTax = $amount + ($amount * $order->tax / 100);
                $charge = $user->charge((int)($amountWithTax * 100), ['source' => $stripeToken]);
                $payment = $this->paymentRepository->create([
                    "user_id" => $input['user_id'],
                    "description" => trans("lang.payment_order_done"),
                    "price" => $amountWithTax,
                    "status" => $charge->status, // $charge->status
                    "method" => $input['payment']['method'],
                ]);
                $this->orderRepository->update(['payment_id' => $payment->id], $order->id);

                $this->cartRepository->deleteWhere(['user_id' => $order->user_id]);

                Notification::send($order->productOrders[0]->product->market->users, new NewOrder($order));

                /* CODE BY ALI HAIDER SIMPLE */

                /* try {
                    // app('OneSignal::class')->sendNotificationToUser("You have a new order at your store!", $order->productOrders[0]->product->market->users, $url = null, $data = null, $buttons = null, $schedule = null, $headings = null, $subtitle = null);
                    OneSignal::async()->sendNotificationToUser("You have a new order at your store!", $order->productOrders[0]->product->market->users, $url = null, $data = null, $buttons = null, $schedule = null, $headings = null, $subtitle = null);
                } catch (ValidatorException $e) {
                    // Storage::put('file123.txt', $e);
                    return $this->sendError($e->getMessage());
                } */

                /* CODE BY ALI HAIDER SIMPLE */
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function cashPayment(Request $request)
    {
        $input = $request->all();
        // Storage::put('abc.txt', $input);
        Log::info($input);
        // Storage::put('userFiles\\'.$user_id.'\\log_'.$logFileTimestamp.'.txt', '');
        $amount = 0;
        try {
            $order = $this->orderRepository->create(
                $request->only('user_id', 'order_status_id', 'tax', 'delivery_address_id', 'delivery_fee','service_fee','hint')
            );
            Log::info($input['products']);
            foreach ($input['products'] as $productOrder) {
                $productOrder['order_id'] = $order->id;
                $amount += $productOrder['price'] * $productOrder['quantity'];
                $this->productOrderRepository->create($productOrder);
            }
            $amount += $order->delivery_fee;
            $amountWithTax = $amount + ($amount * $order->tax / 100);
            // $amountWithTax = $amount + ($amount * $input['service_fee'] / 100);
            $payment = $this->paymentRepository->create([
                "user_id" => $input['user_id'],
                "description" => trans("lang.payment_order_waiting"),
                "price" => $amountWithTax,
                "status" => 'Waiting for Client',
                "method" => $input['payment']['method'],
            ]);

            $this->orderRepository->update(['payment_id' => $payment->id], $order->id);

            $this->cartRepository->deleteWhere(['user_id' => $order->user_id]);

            Notification::send($order->productOrders[0]->product->market->users, new NewOrder($order));
            // Storage::put('output.txt','ddd');

            // send notification to all admins
            $model_roles=DB::table('model_has_roles')->where('role_id',2)->get();

            foreach($model_roles as $role)
            {
                $users[]=User::where('id',$role->model_id)->get();
            }

            // Storage::put('output.txt', $users[], 'public');
            // Storage::put('output.txt',$users);

            foreach($users as $user)
            {
                Notification::send($user, new NewOrder($order));
            }
            //Storage::put('outPut.txt', "users");

            //       return $users; 

            /* CODE BY ALI HAIDER SIMPLE */

            // try {
            // OneSignal::async()->sendNotificationToUser("You have a new order at your store!", $order->productOrders[0]->product->market->users, $url = null, $data = null, $buttons = null, $schedule = null, $headings = null, $subtitle = null);
            // OneSignal::sendNotificationToUser("You have a new order at your store!", 'e19ee75c-4308-484e-9796-c3fb09', $url = null, $data = null, $buttons = null, $schedule = null, $headings = null, $subtitle = null);
            $this->sendOneSignalNotificationToShopManager($order->productOrders[0]->product->market->users);
            // Storage::put('OrderoutPut.txt', ' Mob: '.$order);
            // Storage::put('OrderoutPut1.txt', ' Mob: '.$order->productOrders[0]->product->market->users);
            // Storage::put('OrderoutPut2.txt', ' Mob: '.$order->productOrders[0]->product->market->where('id',$order->productOrders[0]->product->market->id)->get());

            $this->sendSMSNotification($order->productOrders[0]->product->market->where('id', $order->productOrders[0]->product->market->id)->get());
            // $this->sendSMSNotification($order->productOrders[0]->product->market);

            // OneSignal::async()->sendNotificationToUser("You have a new order at your store!", $order->productOrders[0]->product->market->users[0]->onesignal_pId, $url = null, $data = null);

            /* CODE BY ALI HAIDER SIMPLE */
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

    /**
     * Update the specified Order in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $oldOrder = $this->orderRepository->findWithoutFail($id);
        if (empty($oldOrder)) {
            return $this->sendError('Order not found');
        }
        $oldStatus = $oldOrder->payment->status;
        $input = $request->all();

        try {
            $order = $this->orderRepository->update($input, $id);
            if (isset($input['order_status_id']) && $input['order_status_id'] == 5 && !empty($order)) {
                $this->paymentRepository->update(['status' => 'Paid'], $order['payment_id']);
            }
            //            if (isset($input['status'])) {
            //                $this->paymentRepository->update(['status' => $input['status']], $order['payment_id']);
            //            }
            event(new OrderChangedEvent($oldStatus, $order));

            if (setting('enable_notifications', false)) {
                if (isset($input['order_status_id']) && $input['order_status_id'] != $oldOrder->order_status_id) {
                    Notification::send([$order->user], new StatusChangedOrder($order));
                }
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

    /* CODE BY ALI HAIDER SIMPLE */

    private function sendOneSignalNotificationToShopManager($user)
    {
        $pId = $user[0]->onesignal_pId;
        // Storage::put('file45612.txt', $user);

        $content      = (object) array(
            'en' => 'You have a new order'
        );
        $headings      = (object) array(
            'en' => 'Baqala App'
        );

        $fields = array(
            'app_id' => 'eceb4836-43bd-4b60-9ee7-55b812f11792',
            'include_player_ids' => array($pId),
            'contents' => $content,
            'headings' => $headings,
            'url' => 'http://62.171.149.49:8083/login'
        );
        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic YzEwNTJjOWQtODRmZC00NjA1LWIwZDUtNThlNjc3OTBiMTAy'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_exec($ch);
        curl_close($ch);
        // Storage::put('file123.txt', $pId);
        // OneSignal::sendNotificationToUser("You have a new order at your store!",  "".$pId, $url = null, $data = null);
    }

    private function sendOneSignalNotificationToShopManager2($user)
    {
        Storage::put('file123.txt', $user[0]->onesignal_pId);
        // OneSignal::sendNotificationToUser("You have a new order!", $user[0]->onesignal_pId, $url = null, $data = null);
        OneSignal::async()->sendNotificationToUser(
            "You have a new order!",
            $user[0]->onesignal_pId,
            $url = null,
            $data = null,
            $buttons = null,
            $schedule = null
        );
    }

    /* CODE BY ALI HAIDER SIMPLE */
}
