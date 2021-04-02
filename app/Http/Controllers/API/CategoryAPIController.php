<?php

namespace App\Http\Controllers\API;


use App\Criteria\Categories\CategoriesOfFieldsCriteria;
use App\Criteria\Categories\HiddenCriteria;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Exceptions\RepositoryException;
use Flash;
use DB;

/**
 * Class CategoryController
 * @package App\Http\Controllers\API
 */

class CategoryAPIController extends Controller
{
    /** @var  CategoryRepository */
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepo)
    {
        $this->categoryRepository = $categoryRepo;
    }

    /**
     * Display a listing of the Category.
     * GET|HEAD /categories
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->categoryRepository->pushCriteria(new RequestCriteria($request));
            $this->categoryRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->categoryRepository->pushCriteria(new CategoriesOfFieldsCriteria($request));
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        $categories = $this->categoryRepository->all();

        return $this->sendResponse($categories->toArray(), 'Categories retrieved successfully');
    }

    public function categoriesAndProducts(Request $request)
    {
        $marketId = $request->marketId;
        try {
            $this->categoryRepository->pushCriteria(new RequestCriteria($request));
            $this->categoryRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->categoryRepository->pushCriteria(new CategoriesOfFieldsCriteria($request));
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        //correct
        // $products = Product::where('market_id', $marketId)->with('category')->groupBy('category_id')->get();
        //  return $products;

        $products = Product::where('market_id', $marketId)->where('deliverable', '!=', 0)->with('category')->groupBy('category_id')->get();
        $categories = $products->pluck('category');
        if (count($categories) < 1) {
            return $this->sendError('Categories not found');
        }
        //   return $categories;
        // $cats =Category::where('id', $products->category_id)->get();
        // return $cats;

        // $arr = [];
        // foreach ($products as  $product) {
        //     if ($product->categoryId) {
        //         $arr[] = $this->categoryRepository->where('id', $product->category_id)->get();
        //     }
        //     // $cats[] =Category::where('id', $product->category_id)->get();

        // }
        // return (string) $cats;;
        //             $uniques = array();
        //             foreach ($categories as $key => $cat) {
        //                  $uniques[]=$cat[$key]->name;
        //             // $uniques[$cat->product_sku] = $obj;
        //             }
        // return $ar;
        // $values = array_unique($arr, SORT_REGULAR);
        // $result = array_merge($values);
        // return $values;
        // $tt=json_decode(json_encode($array));
        // return $tt;
        // $object = json_decode(json_encode($values), FALSE);
        // $object = (object)$values;
        // return response()->json($values);
        // $categories = $this->categoryRepository->all();
        // return $object;
        // return collect($arr);
        else {
            return $this->sendResponse($categories, 'Categories retrieved successfully');
        }
    }

    /**
     * Display the specified Category.
     * GET|HEAD /categories/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var Category $category */
        if (!empty($this->categoryRepository)) {
            $category = $this->categoryRepository->findWithoutFail($id);
        }

        if (empty($category)) {
            return $this->sendError('Category not found');
        }

        return $this->sendResponse($category->toArray(), 'Category retrieved successfully');
    }
}
