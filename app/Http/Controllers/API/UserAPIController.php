<?php

/**
 * File name: UserAPIController.php
 * Last modified: 2020.05.04 at 09:04:09
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Market;
use App\Models\Pre_User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Repositories\CustomFieldRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Laracasts\Flash\Flash;
use App\Http\Requests\UpdateOrderRequest;
use App\Notifications\StatusChangedOrder;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\OrderStatusRepository;
use App\Repositories\PaymentRepository;
use App\Notifications\AssignedOrder;
use App\Events\OrderChangedEvent;
use Illuminate\Support\Facades\Response;
use Auth;


class UserAPIController extends Controller
{
    private $userRepository;
    private $uploadRepository;
    private $roleRepository;
    private $customFieldRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepository $userRepository, UploadRepository $uploadRepository, RoleRepository $roleRepository, CustomFieldRepository $customFieldRepo)
    {
        $this->userRepository = $userRepository;
        $this->uploadRepository = $uploadRepository;
        $this->roleRepository = $roleRepository;
        $this->customFieldRepository = $customFieldRepo;
    }

    function login(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required',
            ]);
            if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
                // Authentication passed...
                $user = auth()->user();
                // dd(!$user->hasRole('client'));
                if ($user->hasRole('client')) {
                    $user->device_token = $request->input('device_token', '');
                    $user->save();
                    return $this->sendResponse($user, 'User retrieved successfully');
                } else {
                    return $this->sendError('not allowed', 401);
                }
            } else {
                return  $this->sendError('login failed', 401);
            }
        } catch (\Exception $e) {
            return  $this->sendError('server error', 401);
        }
    }

    function getOrdersOfUser(Request $request)
    {
        $userId = $request->userId;
        if ($userId) {
            $orders = DB::table('orders')->where('user_id', $userId)->get();
            return $orders;
        }

        ////////
        // $userId = $request->userId;
        // if ($userId) {
        //     $users = DB::table('user_markets')
        //         //->select('user_markets.market_id')
        //         ->where('user_markets.user_id', $userId)
        //         //->select('user_markets.market_id')

        //         ->join('products', 'products.market_id', '=', 'user_markets.market_id')
        //         ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
        //         ->join('orders', 'orders.id', '=', 'product_orders.order_id')
        //         ->join('payments', 'payments.id', '=', 'orders.payment_id')
        //         ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
        //         //->join('delivery_addresses', 'delivery_addresses.user_id', '=', 'orders.user_id')
        //         ->join('users', 'users.id', '=', 'orders.user_id')
        //         ->join('delivery_addresses', 'delivery_addresses.id', '=', 'orders.delivery_address_id')
        //         ->select(
        //             //product_orders
        //             'product_orders.order_id as order_id',
        //             'product_orders.created_at',
        //             'product_orders.quantity',
        //             //products
        //             'products.name',
        //             'products.discount_price',
        //             'products.price as product_price',
        //             //orders
        //             'orders.delivery_fee',
        //             'orders.tax',
        //             //order_statuses
        //             'order_statuses.status as order_status',
        //             'orders.tax',
        //             //payments
        //             'payments.method',
        //             'payments.price as total_price',
        //             'payments.description',
        //             //delivery_addresses
        //            'delivery_addresses.address',
        //            // 'delivery_addresses.latitude'
        //            //users
        //            'users.name as user_name',
        //            'users.email as user_email',


        //         )
        //         //->groupBy('product_orders.order_id')
        //         // ->join('payments','orders.id','=','product_orders.order_id')

        //         ->get();
        //     //$gg= $users->select(['lists.title AS title', 'lists_galleries.name AS name', 'lists.id AS id'])->get();
        //     //$products = DB::table('products')
        //     //  ->where('market_id', $users.market_id)
        //     //return $this->sendResponse($users->toArray(), 'Orders retrieved successfully');
        //     return $users;
        // }


    }

    /***********get all orders if the logged in user is admin of all markets shifted to 'UserOne' *******/ ////
    function getAllOrdersAdmin(Request $request)
    {
        $model_roles = DB::table('model_has_roles')->where('role_id', 2)->get();
        foreach ($model_roles as $role) {
            $users[] = DB::table('users')->where('id', $role->model_id)->get();
        }
        foreach ($users as $user) {
            $gett[] = $user;
            // Notification::send($user, new NewOrder($order));
        }
        return $gett;

        // $userId = $request->userId;
        // if ($userId) {
        //     $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');
        //    if ($role_id == '2') {
        //         $orders = DB::table('user_markets')
        //             ->join('products', 'products.market_id', '=', 'user_markets.market_id')
        //             ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
        //             ->join('orders', 'orders.id', '=', 'product_orders.order_id')
        //             ->join('payments', 'payments.id', '=', 'orders.payment_id')
        //             ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
        //             ->join('users', 'users.id', '=', 'orders.user_id')
        //             ->join('delivery_addresses', 'delivery_addresses.id', '=', 'orders.delivery_address_id')
        //             ->select(
        //                 'product_orders.order_id as order_id',
        //                 'product_orders.created_at',
        //                 'product_orders.quantity',
        //                 'products.name',
        //                 'products.discount_price',
        //                 'products.price as product_price',
        //                 'orders.delivery_fee',
        //                 'orders.tax',
        //                 'order_statuses.status as order_status',
        //                 'orders.tax',
        //                 'payments.method',
        //                 'payments.price as total_price',
        //                 'payments.description',
        //                 'delivery_addresses.address',
        //                 'users.name as user_name',
        //                 'users.email as user_email',
        //             ) 
        //             ->get();
        //         return $orders;
        //     }
        // }
    }


    //copy of 'UserOne' api
    function getAllAdminOrders(Request $request)
    {
        $service_fee = DB::table('service_fees')->first()->service_fees;
        $fee_convert = $service_fee / 100;

        $userId = $request->userId;
        if ($userId) {
            $users = DB::table('user_markets')
                ->where('user_markets.user_id', $userId)
                ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                ->join('markets', 'products.market_id', '=', 'markets.id')
                ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                ->join('orders', 'orders.id', '=', 'product_orders.order_id')->groupBy('product_orders.order_id')
                ->join('payments', 'payments.id', '=', 'orders.payment_id')
                ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                ->join('users', 'users.id', '=', 'orders.user_id')
                ->join('delivery_addresses', 'delivery_addresses.id', '=', 'orders.delivery_address_id')
                ->join('custom_field_values', 'custom_field_values.customizable_id', '=', 'orders.user_id')
                ->where('custom_field_values.custom_field_id', '=', '4')

                ->select(
                    'product_orders.order_id as order_id',
                    'product_orders.created_at',
                    'product_orders.quantity',
                    'products.name',
                    'products.discount_price',
                    'products.price as product_price',
                    'orders.delivery_fee',
                    'orders.tax',
                    'order_statuses.status as order_status',
                    'orders.tax',
                    'payments.method',
                    'payments.price as total_price',
                    DB::raw("payments.price+('$fee_convert') as final_price"),
                    'payments.description',
                    'delivery_addresses.address',
                    'users.name as user_name',
                    'users.email as user_email',
                    'custom_field_values.value as phone'

                )
                ->get();

            return $users;
        }
    }

    function getMarketProducts(Request $request)
    {
        $userId = $request->userId;
        if ($userId) {
            $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');
            if ($role_id == '3') {
                // $markets=DB::table('user_markets')->where('user_id', $userId)
                // ->join('products', 'products.market_id', '=', 'user_markets.market_id')->groupBy('products.market_id')
                // ->get();
                // return $markets;
                //     return $this->sendResponse($markets, 'Data retreived successfully.');
                // return $this->hasManyThrough(
                //     'App\Market', 'App\Product',
                //     'market_id', 'market_id', 'id'
                // );
                $markets = DB::table('user_markets')->where('user_id', $userId)->get();

                foreach ($markets as $market) {
                    //$a[]= $market->market_id;
                    $market_name[] = DB::table('markets')->where('id', $market->market_id)->value('name');
                    $b[] = DB::table('products')->where('market_id', $market->market_id)->get();
                }
                // $specialproductmsg = $a. $b;
                // return [$market_name];
                $data = array(
                    'Markets' => $b,
                );
                return $data;
            } else {
                return $this->sendError('Not allowed', 401);
            }
        } else {
            return $this->sendError('Server error', 401);
        }
    }

    function getMarkets(Request $request)
    {
        $userId = $request->userId;
        if ($userId) {
            $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');
            if ($role_id == '3') {
                $markets = DB::table('user_markets')->where('user_id', $userId)
                    ->join('markets', 'markets.id', '=', 'user_markets.market_id')

                    ->select(
                        'markets.name',
                        'markets.id'
                    )
                    ->get();
                $data = array(
                    'data' => $markets,
                );
                return $data;
            } else {
                return $this->sendError('Not allowed', 401);
            }
        } else {
            return $this->sendError('Server error', 401);
        }
    }

    function getMarketProductsOne(Request $request)
    {
        // return $request;
        $marketId = $request->marketId;
        if ($marketId) {
            $skip = $request->skip;
            $take = $request->take;
            $products = DB::table('products')->where('market_id', $marketId)
                ->join('markets', 'products.market_id', '=', 'markets.id')
                ->select(
                    'products.*',
                    'markets.name as market_name'
                )
                ->OFFSET($skip)->take($take)
                ->get();
            $data = array(
                'data' => $products,
            );
            return $data;
            // ->OFFSET($skip)->take($take);
        } else {
            return $this->sendError('Server error', 401);
        }
    }

    function setEditProduct(Request $request)
    {
        // if (Product::where('id', $request->productId)->exists()) {
        //     $product = Product::find($request->id);
        //      return $product;
        //     $product->name = is_null($request->name) ? $product->name : $product->name;
        //     $product->price = is_null($request->price) ? $product->price : $product->price;
        //     $product->discount_price = is_null($request->discountedPrice) ? $product->discountedPrice : $product->discountedPrice;
        //     $product->description = is_null($request->description) ? $product->description : $product->description;
        //     $product->capacity = is_null($request->capacity) ? $product->capacity : $product->capacity;
        //     $product->package_items_count = is_null($request->package_items_count) ? $product->package_items_count : $product->package_items_count;
        //     $product->unit = is_null($request->unit) ? $product->unit : $product->unit;
        //     $product->featured = is_null($request->featured) ? $product->featured : $product->featured;
        //     $product->deliverable = is_null($request->deliverable) ? $product->deliverable : $product->deliverable;
        //     $product->product_code = is_null($request->product_code) ? $product->product_code : $product->product_code;
        //     $product->product_barcode = is_null($request->product_barcode) ? $product->product_barcode : $product->product_barcode;
        // $product->name = is_null($request->name) ? $product->name : $product->name;

        ///////
        if (!$request->filled) {

            Product::where('id', $request->productId)
                ->update([
                    'name' => $request->name,
                    'price' => $request->price,
                    'discount_price' => $request->discountedPrice,
                    'description' => $request->description,
                    'capacity' => $request->capacity,
                    'package_items_count' => $request->package_items_count,
                    'unit' => $request->unit,
                    'featured' => $request->featured,
                    'deliverable' => $request->deliverable,
                    'category_id' => $request->category_id,
                    'market_id' => $request->market_id,
                    'product_code' => $request->product_code,
                    'product_barcode' => $request->product_barcode
                    //  $request->description,

                ]);
            return $this->sendResponse('congrats', 'Data updated successfully.');
        } else {
            return $this->sendError('Please fill all the required fields.', 401);
        }
    }

    function getCategories()
    {
        $categories = DB::table('categories')->select(
            'id',
            'name'
            // 'description'
        )
            ->get();
        $data = array(
            'data' => $categories,
        );
        return $data;
        // return $categories;
    }

    function preRegisterUser(Request $request)
    {
        // return $request;
        if (!$request->filled) {
            $obj = new Pre_User;
            $obj->name = $request->name;
            $obj->email = $request->email;
            $obj->phone_no = $request->phone_no;
            $obj->shop_name = $request->shop_name;
            $obj->shop_address = $request->shop_address;
            // Get the value from the form
            $input['email'] = Input::get('email');

            // Must not already exist in the `email` column of `users` table
            $rules = array('email' => 'unique:pre_users,email');

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                return $this->sendError('Account already registered with this email.', 401);
            } else {
                // Register the new user or whatever.
                $obj->save();
                return $this->sendResponse('congrats', 'Data inserted successfully.');
            }
        } else {
            return $this->sendError('Please fill all the required fields.', 401);
        }
    }

    function deleteProduct(Request $request)
    {
        $productId = $request->productId;
        if ($productId) {
            $product = Product::findOrFail($productId);
            if ($product) {
                $product->delete();
                return $this->sendResponse('Successful', 'Product deleted successfully.');
            } else {
                return $this->sendError('Deletion failed.', 401);
            }
        } else {
            return $this->sendError('Id missing', 401);
        }
    }

    function deleteAllProducts(Request $request)
    {
        $marketId = $request->marketId;
        if ($marketId) {
            $market = Market::findOrFail($marketId);
            $ids = explode(",", $marketId);
        // return $market->id;

            if ($market) {
                Product::whereIn('market_id', $ids)->delete();
                updateSpecialOffer($marketId);
                return $this->sendResponse('Successful', 'Products deleted successfully.');
            } else {
                return $this->sendError('Deletion failed.', 401);
            }
        } else {
            return $this->sendError('Id missing', 401);
        }
    }

    function ifDiscountedPrice(Request $request)
    {
        $marketId = $request->marketId;
        if ($marketId) {
            $market = Market::where('id', '=', $marketId)->first();
            // return $market;
            if ($market === null) {
                // user doesn't exist
                return $this->sendError('Market not exist.', 401);
            }
            else
            {
                $product = DB::table('products')->where('market_id' , $marketId)->where('discount_price' , '!=' , 0)->get();
                // return $product;
                if(count($product) < 1)
                {
                    // return $this->sendError('No product exist with discounted price.', 401);
                 return [0];

                }
                else
                {
                 return [1];
                }
            }
        } 
        else 
        {
            return $this->sendError('Id missing', 401);
        }
    }

    //being used in app//
    function getOrdersOfUserOne(Request $request)
    {
        $service_fee = DB::table('service_fees')->first()->service_fees;
        $fee_convert = $service_fee / 100;

        $userId = $request->userId;
        if ($userId) {

            $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');
            if ($role_id == '3') {
                $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');

                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    ->where('user_markets.user_id', $userId)
                    //->select('user_markets.market_id')

                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('markets', 'products.market_id', '=', 'markets.id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->join('users', 'users.id', '=', 'orders.user_id')
                    ->join('delivery_addresses', 'delivery_addresses.id', '=', 'orders.delivery_address_id')
                    ->join('custom_field_values', 'custom_field_values.customizable_id', '=', 'orders.user_id')
                    ->where('custom_field_values.custom_field_id', '=', '4')

                    // ->join('delivery_addresses', 'delivery_addresses.user_id', '=', 'user_markets.user_id')
                    ->select(
                        //product_orders
                        'product_orders.order_id as order_id',
                        'product_orders.created_at',
                        'product_orders.quantity',
                        //products
                        'products.name',
                        'products.discount_price',
                        'products.price as product_price',
                        //orders
                        'orders.delivery_fee',
                        'orders.tax',
                        'orders.service_fee',
                        //order_statuses 
                        'order_statuses.status as order_status',
                        'orders.tax',
                        //payments
                        'payments.method',
                        'payments.price as total_price',
                        'payments.price as final_price',
                        // DB::raw("payments.price+('$service_fee') as final_price"),
                        'payments.description',
                        //delivery_addresses
                        // 'delivery_addresses.address as delivery_address'
                        'delivery_addresses.address as delivery_address',
                        // 'delivery_addresses.latitude'
                        //users
                        'users.name as user_name',
                        'users.email as user_email',
                        //markets
                        'markets.name as market_name',
                        //custom_field_values
                        'custom_field_values.value as phone'

                        //     )
                        //     //->groupBy('product_orders.order_id')
                        //     // ->join('payments','orders.id','=','product_orders.order_id')

                    )->get();
                //$gg= $users->select(['lists.title AS title', 'lists_galleries.name AS name', 'lists.id AS id'])->get();
                //$products = DB::table('products')
                //  ->where('market_id', $users.market_id)
                //return $this->sendResponse($users->toArray(), 'Orders retrieved successfully');
                return $users;
            } else if ($role_id == '2') {
                // return $userId;
                $orders = DB::table('user_markets')
                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('markets', 'products.market_id', '=', 'markets.id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->join('users', 'users.id', '=', 'orders.user_id')
                    ->join('delivery_addresses', 'delivery_addresses.id', '=', 'orders.delivery_address_id')
                    ->join('custom_field_values', 'custom_field_values.customizable_id', '=', 'orders.user_id')
                    ->where('custom_field_values.custom_field_id', '=', '4')

                    ->select(
                        'product_orders.order_id as order_id',
                        'product_orders.created_at',
                        'product_orders.quantity',
                        'products.name',
                        'products.discount_price',
                        'products.price as product_price',
                        'orders.delivery_fee',
                        'orders.service_fee',
                        'orders.tax',
                        'order_statuses.status as order_status',
                        'orders.tax',
                        'payments.method',
                        'payments.price as total_price',
                        'payments.price as final_price',
                        //  DB::raw("payments.price+('$service_fee') as final_price"),
                        'payments.description',
                        'delivery_addresses.address as delivery_address',
                        'users.name as user_name',
                        'users.email as user_email',
                        'markets.name as market_name',
                        'custom_field_values.value as phone'

                    )->get();
                return $orders;
            } else {
                return '[0]';
            }
        }
    }

    function getTotalVendorOrders(Request $request)
    {

        $userId = $request->userId;
        if ($userId) {

            $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');
            if ($role_id == '3') {
                $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');

                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    ->where('user_markets.user_id', $userId)
                    //->select('user_markets.market_id')

                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('markets', 'products.market_id', '=', 'markets.id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->join('users', 'users.id', '=', 'orders.user_id')
                    ->join('delivery_addresses', 'delivery_addresses.id', '=', 'orders.delivery_address_id')
                    ->join('custom_field_values', 'custom_field_values.customizable_id', '=', 'orders.user_id')
                    ->where('custom_field_values.custom_field_id', '=', '4')
                    ->groupBy('product_orders.order_id')

                    // ->join('delivery_addresses', 'delivery_addresses.user_id', '=', 'user_markets.user_id')
                    ->get();
                //$gg= $users->select(['lists.title AS title', 'lists_galleries.name AS name', 'lists.id AS id'])->get();
                //$products = DB::table('products')
                //  ->where('market_id', $users.market_id)
                //return $this->sendResponse($users->toArray(), 'Orders retrieved successfully');
                $data = array(
                    'count' => count($users),
                );
                return $data;
            } else if ($role_id == '2') {
                // return $userId;
                $orders = DB::table('user_markets')
                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('markets', 'products.market_id', '=', 'markets.id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->join('users', 'users.id', '=', 'orders.user_id')
                    ->join('delivery_addresses', 'delivery_addresses.id', '=', 'orders.delivery_address_id')
                    ->join('custom_field_values', 'custom_field_values.customizable_id', '=', 'orders.user_id')
                    ->where('custom_field_values.custom_field_id', '=', '4')
                    ->groupBy('product_orders.order_id')
                    ->get();
                $data = array(
                    'count' => count($orders),
                );
                return $data;
            } else {
                return $this->sendError('No user exist.', 401);
            }
        }
    }



    function getTotalVendorEarning(Request $request)
    {
        $userId = $request->userId;

        if ($userId) {
            $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');

            if ($role_id == '3') {
                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    ->where('user_markets.user_id', $userId)
                    //->select('user_markets.market_id')

                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->groupBy('product_orders.order_id')
                    ->where('orders.active', '!=', 0)
                    ->get();
                // return $users;
                $sum = 0;
                foreach ($users as $user) {
                    $sum =  $sum + $user->price;
                }

                $service_sum = 0;

                foreach ($users as $user) {
                    $service_sum =  $service_sum + $user->service_fee;
                }
                $service_fee = DB::table('service_fees')->first()->service_fees;
                $sum = $sum + $service_sum;
                $data = array(
                    'count' => number_format($sum, 2),
                );
                return $data;
                //  ->sum('payments.price');
                //->distinct()
                // ->selectRaw('SUM(payments.price) As Total_price')

                //->get(['payments.price', DB::raw('SUM(payments.price) AS sum_a')]);
            } else if ($role_id == '2') {
                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    // ->where('user_markets.user_id', $userId)
                    //->select('user_markets.market_id')

                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->groupBy('product_orders.order_id')
                    ->get();
                // return $users;
                $sum = 0;
                foreach ($users as $user) {
                    $sum =  $sum + $user->price;
                }

                $service_sum = 0;

                foreach ($users as $user) {
                    $service_sum =  $service_sum + $user->service_fee;
                }
                $service_fee = DB::table('service_fees')->first()->service_fees;
                $sum = $sum + $service_sum;
                $data = array(
                    'count' => number_format($sum, 2),
                );
                return $data;
            } else {
                return $this->sendError('No response.', 401);
            }
        } else {
            return $this->sendError('No user exist.', 401);
        }
        //service fees 0.03


        // $fee_convert = $service_fee / 100;
        //  $sum = $sum + ($sum * $fee_convert);
        // $final_sum=$service_sum+$sum;
        // return [$sum];
    }

    function getTotalVendorCash(Request $request)
    {
        $userId = $request->userId;
        if ($userId) {
            $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');

            if ($role_id == '3') {
                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    ->where('user_markets.user_id', $userId)
                    //->select('user_markets.market_id')
                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->groupBy('product_orders.order_id')
                    ->where('orders.active', '!=', 0)
                    // ->where('payments.method', '=', 'Cash on Delivery')
                    // ->select(DB::raw('SUM(payments.price) As Total_price'))
                    ->get();

                $sum = 0;
                // return [$users];
                foreach ($users as $user) {
                    $sum =  $sum + $user->price;
                }
                $service_sum = 0;

                foreach ($users as $user) {
                    $service_sum =  $service_sum + $user->service_fee;
                }
                $service_fee = DB::table('service_fees')->first()->service_fees;
                $sum = $sum + $service_sum;
                $data = array(
                    'count' => number_format($sum, 2),
                );
                return $data;
                // return [number_format($sum, 2)];
            } else if ($role_id == '2') {
                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    // ->where('user_markets.user_id', $userId)
                    //->select('user_markets.market_id')
                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->groupBy('product_orders.order_id')
                    ->where('payments.method', '=', 'Cash on Delivery')
                    // ->select(DB::raw('SUM(payments.price) As Total_price'))
                    ->get();

                $sum = 0;

                foreach ($users as $user) {
                    $sum =  $sum + $user->price;
                }
                $service_sum = 0;

                foreach ($users as $user) {
                    $service_sum =  $service_sum + $user->service_fee;
                }
                $service_fee = DB::table('service_fees')->first()->service_fees;
                $sum = $sum + $service_sum;
                $data = array(
                    'count' => number_format($sum, 2),
                );
                return $data;
                // return [number_format($sum, 2)];
            } else {
                return $this->sendError('No response', 401);
            }
        }
        //service fees 0.03


        // $fee_convert = $service_fee / 100;
        //  $sum = $sum + ($sum * $fee_convert);
        // return [$sum];
    }

    function getTotalVendorBank(Request $request)
    {
        $userId = $request->userId;

        if ($userId) {
            $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');

            if ($role_id == '3') {
                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    ->where('user_markets.user_id', $userId)
                    //->select('user_markets.market_id')

                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->groupBy('product_orders.order_id')
                    ->where('payments.method', '!=', 'Cash on Delivery')
                    ->where('orders.active', '!=', 0)
                    // ->select(DB::raw('SUM(payments.price) As Total_price'))
                    ->get();
                $sum = 0;

                foreach ($users as $user) {
                    $sum =  $sum + $user->price;
                }
                $service_sum = 0;

                foreach ($users as $user) {
                    $service_sum =  $service_sum + $user->service_fee;
                }
                $service_fee = DB::table('service_fees')->first()->service_fees;
                if ($sum == 0) {
                    $sum = 0;
                } else {
                    $sum = $sum + $service_fee;
                }
                $data = array(
                    'count' => number_format($sum, 2),
                );
                return $data;
            } else if ($role_id == '2') {
                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    ->where('user_markets.user_id', $userId)
                    //->select('user_markets.market_id')

                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->groupBy('product_orders.order_id')
                    ->where('payments.method', '!=', 'Cash on Delivery')
                    // ->select(DB::raw('SUM(payments.price) As Total_price'))
                    ->get();
                $sum = 0;

                foreach ($users as $user) {
                    $sum =  $sum + $user->price;
                }
                $service_sum = 0;

                foreach ($users as $user) {
                    $service_sum =  $service_sum + $user->service_fee;
                }
                $service_fee = DB::table('service_fees')->first()->service_fees;
                if ($sum == 0) {
                    $sum = 0;
                } else {
                    $sum = $sum + $service_fee;
                }
                $data = array(
                    'count' => number_format($sum, 2),
                );
                return $data;
            } else {
                return $this->sendError('No response.', 401);
            }
        }

        //service fees 0.03

        // $fee_convert = $service_fee / 100;
        // $sum = $sum + ($sum * $fee_convert);

        // return [$sum];
    }


    function getVendorActiveOrders(Request $request)
    {
        $userId = $request->userId;
        if ($userId) {
            $role_id = DB::table('model_has_roles')->where('model_id', $userId)->value('role_id');

            if ($role_id == '3') {
                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    ->where('user_markets.user_id', $userId)
                    //->select('user_markets.market_id')

                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->groupBy('product_orders.order_id')
                    ->where('orders.active', '!=', 0)
                    ->where('order_statuses.status', '!=', 'Delivered')
                    //->select(DB::raw('SUM(payments.price) As Total_price'))
                    ->get();
                $count = count($users);
                $data = array(
                    'count' => $count,
                );
                return $data;
                // return $users;
            } else if ($role_id == '2') {
                $users = DB::table('user_markets')
                    //->select('user_markets.market_id')
                    // ->where('user_markets.user_id', $userId)

                    //->select('user_markets.market_id')
                    ->join('products', 'products.market_id', '=', 'user_markets.market_id')
                    ->join('product_orders', 'product_orders.product_id', '=', 'products.id')
                    ->join('orders', 'orders.id', '=', 'product_orders.order_id')
                    ->join('payments', 'payments.id', '=', 'orders.payment_id')
                    ->join('order_statuses', 'order_statuses.id', '=', 'orders.order_status_id')
                    ->groupBy('product_orders.order_id')
                    ->where('orders.active', '!=', 0)
                    ->where('order_statuses.status', '!=', 'Delivered')
                    //->select(DB::raw('SUM(payments.price) As Total_price'))
                    ->get();
                $count = count($users);
                $data = array(
                    'count' => $count,
                );
                return $data;
            } else {
                return $this->sendError('No response.', 401);
            }
        } else {
            return $this->sendError('No response.', 401);
        }
    }

    function changeOrderStatuss(Request $request)
    {
        $orderId = $request->orderId;
        $orderStatus = $request->orderStatus;

        if ($orderId && $orderStatus) {
            $orderStatusId = DB::table('order_statuses')->where('status', $orderStatus)->value('id');
            if ($orderStatusId) {
                $affected = DB::table('orders')
                    ->where('id', $orderId)
                    ->update(['order_status_id' => $orderStatusId]);
                //notification
                try {
                    $order = Order::where('id', $orderId)->first();
                    $user_obj = User::where('id', $order->user_id)->get();

                    Notification::send($user_obj, new StatusChangedOrder($order));
                } catch (ValidatorException $e) {
                    Flash::error($e->getMessage());
                }
                return [$affected];
            }
        }
    }

    function changeOrderStatusTest(Request $request)
    {
        $orderId = $request->orderId;
        $orderStatus = $request->orderStatus;

        if ($orderId && $orderStatus) {
            $orderStatusId = DB::table('order_statuses')->where('status', $orderStatus)->value('id');
            if ($orderStatusId) {
                $affected = DB::table('orders')
                    ->where('id', $orderId)
                    ->update(['order_status_id' => $orderStatusId]);
            }
            //notification
            // $input = $req->all();
            try {
                //$order= DB::table('orders')->where('id', $orderId)->get();
                //$order= DB::table('orders')->where('id', $orderId)->first();

                $order = Order::where('id', $orderId)->first();
                $user_obj = User::where('id', $order->user_id)->get();
                //$order = $this->orderRepository->update($input, $orderId);
                // return $user_obj;

                // $oldOrder = $this->orderRepository->findWithoutFail($orderId);
                Notification::send($user_obj, new StatusChangedOrder($order));

                // if (setting('enable_notifications', false)) {
                //     if (isset($input['order_status_id']) && $input['order_status_id'] != $oldOrder->order_status_id) {
                //         Notification::send([$order->user], new StatusChangedOrder($order));
                //     }

                //     if (isset($input['driver_id']) && ($input['driver_id'] != $oldOrder['driver_id'])) {
                //         $driver = $this->userRepository->findWithoutFail($input['driver_id']);
                //         if (!empty($driver)) {
                //             Notification::send([$driver], new AssignedOrder($order));
                //         }
                //     }
                // }

                // $this->paymentRepository->update([
                //     "status" => $input['status'],
                // ], $order['payment_id']);
                // //dd($input['status']);

                // event(new OrderChangedEvent($oldStatus, $order));

                // foreach (getCustomFieldsValues($customFields, $request) as $value) {
                //     $order->customFieldsValues()
                //         ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
                // }
            } catch (ValidatorException $e) {
                Flash::error($e->getMessage());
            }
        }
    }


    function getOrderStatuses(Request $request)
    {
        $statuses = DB::table('order_statuses')->get();
        return $statuses;
    }

    function login1(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required',
            ]);
            if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
                // Authentication passed...
                $user = auth()->user();
                // dd(!$user->hasRole('client'));
                if (!$user->hasRole('client')) {
                    $user->device_token = $request->input('device_token', '');
                    $user->save();
                    return $this->sendResponse($user, 'User retrieved successfully');
                } else {
                    return $this->sendError('Login Failed', 401);
                }
            } else {
                return $this->sendError('Incorrect email or password', 401);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return
     */
    function register(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required|unique:users|email',
                'password' => 'required',
            ]);
            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->device_token = $request->input('device_token', '');
            $user->password = Hash::make($request->input('password'));
            $user->api_token = str_random(60);
            $user->save();

            $defaultRoles = $this->roleRepository->findByField('default', '1');
            $defaultRoles = $defaultRoles->pluck('name')->toArray();
            $user->assignRole($defaultRoles);


            if (copy(public_path('images/avatar_default.png'), public_path('images/avatar_default_temp.png'))) {
                $user->addMedia(public_path('images/avatar_default_temp.png'))
                    ->withCustomProperties(['uuid' => bcrypt(str_random())])
                    ->toMediaCollection('avatar');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }


        return $this->sendResponse($user, 'User retrieved successfully');
    }

    function logout(Request $request)
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();
        if (!$user) {
            return $this->sendError('User not found', 401);
        }
        try {
            auth()->logout();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage(), 401);
        }
        return $this->sendResponse($user['name'], 'User logout successfully');
    }

    function user(Request $request)
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();

        if (!$user) {
            return $this->sendError('User not found', 401);
        }

        return $this->sendResponse($user, 'User retrieved successfully');
    }

    function settings(Request $request)
    {
        $settings = setting()->all();
        $settings = array_intersect_key(
            $settings,
            [
                'default_tax' => '',
                'default_currency' => '',
                'default_currency_decimal_digits' => '',
                'app_name' => '',
                'currency_right' => '',
                'enable_paypal' => '',
                'enable_stripe' => '',
                'enable_razorpay' => '',
                'main_color' => '',
                'main_dark_color' => '',
                'second_color' => '',
                'second_dark_color' => '',
                'accent_color' => '',
                'accent_dark_color' => '',
                'scaffold_dark_color' => '',
                'scaffold_color' => '',
                'google_maps_key' => '',
                'mobile_language' => '',
                'app_version' => '',
                'enable_version' => '',
                'distance_unit' => '',
            ]
        );

        if (!$settings) {
            return $this->sendError('Settings not found', 401);
        }

        return $this->sendResponse($settings, 'Settings retrieved successfully');
    }

    /**
     * Update the specified User in storage.
     *
     * @param int $id
     * @param Request $request
     *
     */
    public function update($id, Request $request)
    {
        $user = $this->userRepository->findWithoutFail($id);

        if (empty($user)) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], 'User not found');
        }
        $input = $request->except(['password', 'api_token']);
        try {
            if ($request->has('device_token')) {
                $user = $this->userRepository->update($request->only('device_token'), $id);
            } else {
                $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());
                $user = $this->userRepository->update($input, $id);

                foreach (getCustomFieldsValues($customFields, $request) as $value) {
                    $user->customFieldsValues()
                        ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
                }
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage(), 401);
        }

        return $this->sendResponse($user, __('lang.updated_successfully', ['operator' => __('lang.user')]));
    }

    function sendResetLinkEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        $response = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($response == Password::RESET_LINK_SENT) {
            return $this->sendResponse(true, 'Reset link was sent successfully');
        } else {
            return $this->sendError('Reset link not sent', 401);
        }
    }

    function updateOneSignalPlayerId(Request $request)
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();

        if (!$user) {
            return $this->sendError('User not found', 401);
        }

        if ($request->method() == 'POST') {
            if ($request->one_signal_pId) {

                // IF USER IS AUTHENTIC AND ONESIGNAL PID NEEDS TO BE CHANGED (VALUE IS NEW)                
                $res = DB::table('users')
                    ->where('id', $user->id)
                    ->update(['onesignal_pId' => $request->one_signal_pId]);

                // IF USER ONESIGNAL PID UPDATED
                if ($res) {
                    return response()->json(array([
                        'message' => 'OneSignal player id provided for the user has been updated successfully',
                        'code' => 200,
                    ]), 200);
                } else {
                    return response()->json(array([
                        'message' => 'OneSignal player id provided for the user could not be updated',
                        'code' => 300,
                    ]), 300);
                }
            } else {
                return response()->json(array([
                    'message' => 'POST Payload incorrect: \'user_id\' & \'one_signal_pId\' are  missing!',
                    'code' => 300,
                ]), 300);
            }
        } else {
            return response()->json(array([
                'message' => 'Only POST method is allowed!',
                'code' => 300,
            ]), 300);
        }
    }
}
