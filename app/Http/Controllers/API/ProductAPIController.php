<?php

/**
 * File name: ProductAPIController.php
 * Last modified: 2020.05.04 at 09:04:19
 * Author: Crosshair Technology Lab - TriCloud Technologies
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers\API;


use App\Criteria\Products\NearCriteria;
use App\Criteria\Products\ProductsOfCategoriesCriteria;
use App\Criteria\Products\ProductsOfFieldsCriteria;
use App\Criteria\Products\TrendingWeekCriteria;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\CustomFieldRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UploadRepository;
use Flash;
use Illuminate\Http\Request;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Facades\DB;
use Carbon;

/**
 * Class ProductController
 * @package App\Http\Controllers\API
 */
class ProductAPIController extends Controller
{
    /** @var  ProductRepository */
    private $productRepository;
    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;
    /**
     * @var UploadRepository
     */
    private $uploadRepository;


    public function __construct(ProductRepository $productRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo)
    {
        parent::__construct();
        $this->productRepository = $productRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
    }

    /**
     * Display a listing of the Product.
     * GET|HEAD /products
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // return $request;
        // return $request;
        try {
            $this->productRepository->pushCriteria(new RequestCriteria($request));
            $this->productRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->productRepository->pushCriteria(new ProductsOfFieldsCriteria($request));
            if ($request->get('trending', null) == 'week') {
                $this->productRepository->pushCriteria(new TrendingWeekCriteria($request));
            } else {
                $this->productRepository->pushCriteria(new NearCriteria($request));
            }

            //            $this->productRepository->orderBy('closed');
            //            $this->productRepository->orderBy('area');
            // $products = $this->productRepository->all();
            $skip = $request->skip;
            $take = $request->take;
            $time = $request->time;
            // // $products = $this->productRepository->all();
            
             $products = $this->productRepository->where("deliverable", 1)
             ->where('markets.start_time','<=',$time)
            ->where('markets.end_time','>=',$time)
            ->with('market')->OFFSET($skip)->take($take)->get();
            if(count($products)<1)
            {
            return $this->sendError('no products');
                // return 
            }
        

            // $products = Product::where("deliverable", 1)->with('market')->OFFSET($skip)->take($take)->get();

            // $start_time=DB::table('markets')->where('id',$products[0]->market_id)->value('start_time');
            // $end_time=DB::table('markets')->where('id',$products[0]->market_id)->value('end_time');
            // $final=$products->where(,'<=',$dt) ->where('period_ends_at','>=',$dt)
            // return $end_time;
            // //market id to get products of open time in that market
            // return $products[0]->market_id;

        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($products->toArray(), 'Products retrieved successfully');
    }

//getting products wrt tme
    public function indexTest(Request $request)
    {
        //  return $request;
        try {
            $this->productRepository->pushCriteria(new RequestCriteria($request));
            $this->productRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->productRepository->pushCriteria(new ProductsOfFieldsCriteria($request));
            if ($request->get('trending', null) == 'week') {
                $this->productRepository->pushCriteria(new TrendingWeekCriteria($request));
            } else {
                $this->productRepository->pushCriteria(new NearCriteria($request));
            }

            //            $this->productRepository->orderBy('closed');
            //            $this->productRepository->orderBy('area');
            // $products = $this->productRepository->all();
            $skip = $request->skip;
            $take = $request->take;
            $time= $request->time;
            //  return $time;
            // $products = $this->productRepository->all();
           // $products = $this->productRepository->where("deliverable", 1)->with('market')->OFFSET($skip)->take($take)->get();
            // return $products;
            $products = $this->productRepository->where("deliverable", 1)
            ->whereTime('markets.start_time', '<=', $time.':00')
            ->whereTime('markets.end_time', '>=', $time.':00')
            
            // ->where('markets.start_time','<=',$time)
            // ->where('markets.end_time','>=',$time)->with('market')
            ->OFFSET($skip)->take($take)->get();
           // $start_time=DB::table('markets')->where('id',$products[0]->market_id)->value('start_time');
         //   $end_time=DB::table('markets')->where('id',$products[0]->market_id)->value('end_time');
            // $final=$products->where(,'<=',$dt) ->where('period_ends_at','>=',$dt);
            // return $products;
            //market id to get products of open time in that market
            // return $products[0]->market_id;

        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($products->toArray(), 'Products retrieved successfully');
    }

    //special offer products
    public function discountedProducts(Request $request)
    {
        // return $request;
        try {
            $this->productRepository->pushCriteria(new RequestCriteria($request));
            $this->productRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->productRepository->pushCriteria(new ProductsOfFieldsCriteria($request));
            // $this->productRepository->pushCriteria(new ProductsOfCategoriesCriteria($request));
            $skip = $request->skip;
            $take = $request->take;

            // $categoryID = 0;
            // if ($request->categoryID) {
            //     $categoryID = $request->categoryID;
            // }

            // if ($categoryID) {
                $products = $this->productRepository->where("deliverable", 1)->where('discount_price','!=',0)->OFFSET($skip)->take($take)->get();
            // } else {
            //     $products = $this->productRepository->where("deliverable", 1)->OFFSET($skip)->take($take)->get();
            // }

            // $products = $this->productRepository->all();
            // $products = $this->productRepository->where("deliverable", 1)->OFFSET($skip)->take($take)->get();
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($products->toArray(), 'Products retrieved successfully');
    }

    /**
     * Display a list`in`g of the Product.
     * GET|HEAD /products
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductSearchTotal(Request $request)
    {
        try {
            $this->productRepository->pushCriteria(new RequestCriteria($request));
            $this->productRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->productRepository->pushCriteria(new ProductsOfFieldsCriteria($request));
            if ($request->get('trending', null) == 'week') {
                $this->productRepository->pushCriteria(new TrendingWeekCriteria($request));
            } else {
                $this->productRepository->pushCriteria(new NearCriteria($request));
            }

            //            $this->productRepository->orderBy('closed');
            //            $this->productRepository->orderBy('area');
            // $products = $this->productRepository->all();
            // $skip = $request->skip;
            // $take = $request->take;

            $products = $this->productRepository->all();
            // $products = $this->productRepository->OFFSET($skip)->take($take)->count();

        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return [count($products)];
    }

    public function getTotalProducts()
    {
        $serviceFee = Product::count();
        return [($serviceFee)];
    }

    /**
     * Display a listing of the Product.
     * GET|HEAD /products/categories
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(Request $request)
    {

        try {
            $this->productRepository->pushCriteria(new RequestCriteria($request));
            $this->productRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->productRepository->pushCriteria(new ProductsOfFieldsCriteria($request));
            $this->productRepository->pushCriteria(new ProductsOfCategoriesCriteria($request));
            $skip = $request->skip;
            $take = $request->take;

            $categoryID = 0;
            if ($request->categoryID) {
                $categoryID = $request->categoryID;
            }

            if ($categoryID) {
                $products = $this->productRepository->where("deliverable", 1)->where('category_id',$categoryID)->OFFSET($skip)->take($take)->get();
            } else {
                $products = $this->productRepository->where("deliverable", 1)->OFFSET($skip)->take($take)->get();
            }

            // $products = $this->productRepository->all();
            // $products = $this->productRepository->where("deliverable", 1)->OFFSET($skip)->take($take)->get();
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($products->toArray(), 'Products retrieved successfully');
    }


    /**
     * Display a listing of the Product.
     * GET|HEAD /products/categories
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchMarket(Request $request)
    {

        try {
            $this->productRepository->pushCriteria(new RequestCriteria($request));
            $this->productRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->productRepository->pushCriteria(new ProductsOfFieldsCriteria($request));
            if ($request->get('trending', null) == 'week') {
                $this->productRepository->pushCriteria(new TrendingWeekCriteria($request));
            } else {
                $this->productRepository->pushCriteria(new NearCriteria($request));
            }

            $skip = $request->skip;
            $take = $request->take;
            $marketID = 0;
            if ($request->marketID) {
                $marketID = $request->marketID;
            }

            if ($marketID) {
                $products = $this->productRepository->where("deliverable", 1)->where('market_id', $marketID)->with('market')->OFFSET($skip)->take($take)->get();
            } else {
                $products = $this->productRepository->where("deliverable", 1)->OFFSET($skip)->take($take)->get();
            }
            // $products = $this->productRepository->all();
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($products->toArray(), 'Products retrieved successfully');
    }

    /**
     * Display a listing of the Product.
     * GET|HEAD /products/categories
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductCategorySearchTotal(Request $request)
    {
        try {
            $this->productRepository->pushCriteria(new RequestCriteria($request));
            $this->productRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->productRepository->pushCriteria(new ProductsOfFieldsCriteria($request));
            $this->productRepository->pushCriteria(new ProductsOfCategoriesCriteria($request));
            // $skip = $request->skip;
            // $take = $request->take;

            $products = $this->productRepository->all();
            // $products = $this->productRepository->OFFSET($skip)->take($take)->get();

        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return [count($products)];
    }

    /**
     * Display the specified Product.
     * GET|HEAD /products/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        /** @var Product $product */
        if (!empty($this->productRepository)) {
            try {
                $this->productRepository->pushCriteria(new RequestCriteria($request));
                $this->productRepository->pushCriteria(new LimitOffsetCriteria($request));
            } catch (RepositoryException $e) {
                return $this->sendError($e->getMessage());
            }
            $product = $this->productRepository->findWithoutFail($id);
        }

        if (empty($product)) {
            return $this->sendError('Product not found');
        }

        return $this->sendResponse($product->toArray(), 'Product retrieved successfully');
    }

    /**
     * Store a newly created Product in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->productRepository->model());
        try {
            $product = $this->productRepository->create($input);
            $product->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($product, 'image');
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($product->toArray(), __('lang.saved_successfully', ['operator' => __('lang.product')]));
    }

    /**
     * Update the specified Product in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $product = $this->productRepository->findWithoutFail($id);

        if (empty($product)) {
            return $this->sendError('Product not found');
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->productRepository->model());
        try {
            $product = $this->productRepository->update($input, $id);

            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($product, 'image');
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $product->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($product->toArray(), __('lang.updated_successfully', ['operator' => __('lang.product')]));
    }

    /**
     * Remove the specified Product from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $product = $this->productRepository->findWithoutFail($id);

        if (empty($product)) {
            return $this->sendError('Product not found');
        }

        $product = $this->productRepository->delete($id);

        return $this->sendResponse($product, __('lang.deleted_successfully', ['operator' => __('lang.product')]));
    }
}
