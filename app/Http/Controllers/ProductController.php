<?php

/**
 * File name: ProductController.php
 * Last modified: 2020.04.30 at 08:21:08
 * Author: Crosshair Technology Lab - TriCloud Technologies
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers;

use App\Criteria\Products\ProductsOfUserCriteria;
use App\DataTables\ProductDataTable;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\MarketRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UploadRepository;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Prettus\Validator\Exceptions\ValidatorException;
use Session;
use App\Models\Product;
use App\Models\Market;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Stmt\Catch_;
use ZipArchive;
use League\Flysystem\MountManager;
use Zip;
use Aws\S3\S3Client;
use App\Events\importUpdateStatus;
use Dompdf\Helpers;
use Exception;
use Intervention\Image\File as ImageFile;

class ProductController extends Controller
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
    /**
     * @var MarketRepository
     */
    private $marketRepository;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;



    public function __construct(
        ProductRepository $productRepo,
        CustomFieldRepository $customFieldRepo,
        UploadRepository $uploadRepo,
        MarketRepository $marketRepo,
        CategoryRepository $categoryRepo
    ) {
        parent::__construct();
        $this->productRepository = $productRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
        $this->marketRepository = $marketRepo;
        $this->categoryRepository = $categoryRepo;
    }

    /**
     * Display a listing of the Product.
     *
     * @param ProductDataTable $productDataTable
     * @return Response
     */
    public function index(ProductDataTable $productDataTable)
    {
        return $productDataTable->render('products.index');
    }

    /**
     * Show the form for creating a new Product.
     *
     * @return Response
     */
    public function create()
    {

        $category = $this->categoryRepository->pluck('name', 'id');
        if (auth()->user()->hasRole('admin')) {
            $market = $this->marketRepository->pluck('name', 'id');
        } else {
            $market = $this->marketRepository->myActiveMarkets()->pluck('name', 'id');
        }
        $hasCustomField = in_array($this->productRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->productRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('products.create')->with("customFields", isset($html) ? $html : false)->with("market", $market)->with("category", $category);
    }

    /**
     * Store a newly created Product in storage.
     *
     * @param CreateProductRequest $request
     *
     * @return Response
     */
    public function store(CreateProductRequest $request)
    {
        // dd($request->all());
        $input = $request->all();
        // dd("Inp: ",$input);
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->productRepository->model());
        try {
            $product = $this->productRepository->create($input);

            $product->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

            if (isset($input['image']) && $input['image']) {
                // dd($input['image']);
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();

                $mediaItem->copy($product, 'image');
                // $this->storeImageToS3($mediaItem);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.product')]));

        return redirect(route('products.index'));
    }

    /**
     * Display the specified Product.
     *
     * @param int $id
     *
     * @return Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function show($id)
    {
        $this->productRepository->pushCriteria(new ProductsOfUserCriteria(auth()->id()));
        $product = $this->productRepository->findWithoutFail($id);

        if (empty($product)) {
            Flash::error('Product not found');

            return redirect(route('products.index'));
        }

        return view('products.show')->with('product', $product);
    }

    /**
     * Show the form for editing the specified Product.
     *
     * @param int $id
     *
     * @return Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function edit($id)
    {
        $this->productRepository->pushCriteria(new ProductsOfUserCriteria(auth()->id()));
        $product = $this->productRepository->findWithoutFail($id);
        if (empty($product)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.product')]));
            return redirect(route('products.index'));
        }
        $category = $this->categoryRepository->pluck('name', 'id');
        if (auth()->user()->hasRole('admin')) {
            $market = $this->marketRepository->pluck('name', 'id');
        } else {
            $market = $this->marketRepository->myMarkets()->pluck('name', 'id');
        }
        $customFieldsValues = $product->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->productRepository->model());
        $hasCustomField = in_array($this->productRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('products.edit')->with('product', $product)->with("customFields", isset($html) ? $html : false)->with("market", $market)->with("category", $category);
    }

    /**
     * Update the specified Product in storage.
     *
     * @param int $id
     * @param UpdateProductRequest $request
     *
     * @return Response
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function update($id, UpdateProductRequest $request)
    {
        $this->productRepository->pushCriteria(new ProductsOfUserCriteria(auth()->id()));
        $product = $this->productRepository->findWithoutFail($id);

        if (empty($product)) {
            Flash::error('Product not found');
            return redirect(route('products.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->productRepository->model());
        try {
            $product = $this->productRepository->update($input, $id);

            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($product, 'image');
                // $this->storeImageToS3($mediaItem);
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $product->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.product')]));

        return redirect(route('products.index'));
    }

    /**
     * Remove the specified Product from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        if (!env('APP_DEMO', false)) {
            $this->productRepository->pushCriteria(new ProductsOfUserCriteria(auth()->id()));
            $product = $this->productRepository->findWithoutFail($id);

            if (empty($product)) {
                Flash::error('Product not found');

                return redirect(route('products.index'));
            }

            $this->productRepository->delete($id);

            Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.product')]));
        } else {
            Flash::warning('This is only demo app you can\'t change this section ');
        }
        return redirect(route('products.index'));
    }

    /**
     * Remove Media of Product
     * @param Request $request
     */
    public function removeMedia(Request $request)
    {
        $input = $request->all();
        $product = $this->productRepository->findWithoutFail($input['id']);
        try {
            if ($product->hasMedia($input['collection'])) {
                $product->getFirstMedia($input['collection'])->delete();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function storeImageToS3($img)
    {
        try {
            // dd($img->dirname);
            if (strpos($img->dirname, "conversions") !== false) {
                $imageBreakage = explode("\\", $img->dirname);
                $imgFile = Image::make($img);
                Storage::disk('s3Public')->put('public\\' . $imageBreakage[sizeof($imageBreakage) - 2] . '\conversions\\' . $img->basename, $imgFile->encode(), 'public');
            } else {
                $imageBreakage = explode("\\", $img->dirname);
                $imgFile = Image::make($img);
                Storage::disk('s3Public')->put('public\\' . $imageBreakage[sizeof($imageBreakage) - 1] . '\\' . $img->basename, $imgFile->encode(), 'public');
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }
    }

    public function getImageFromS3($img)
    {
        try {

            if (strpos($img->dirname, "conversions") !== false) {
                $imageBreakage = explode("\\", $img->dirname);

                if (Storage::disk('s3Public')->exists('public\\' . $imageBreakage[sizeof($imageBreakage) - 2] . '\conversions\\' . $img->basename)) {
                    return Storage::disk('s3Public')->url('public\\' . $imageBreakage[sizeof($imageBreakage) - 2] . '\conversions\\' . $img->basename);
                } else {
                    return Storage::disk('s3Public')->url('public\no_image.png');
                }
            } else {
                $imageBreakage = explode("\\", $img->dirname);

                if (Storage::disk('s3Public')->exists('public\\' . $imageBreakage[sizeof($imageBreakage) - 1] . '\\' . $img->basename)) {
                    return Storage::disk('s3Public')->url('public\\' . $imageBreakage[sizeof($imageBreakage) - 1] . '\\' . $img->basename);
                } else {
                    return Storage::disk('s3Public')->url('public\no_image.png');
                }
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }
    }

    public function send_message($id, $message, $progress)
    {
        $d = array('message' => $message, 'progress' => $progress);

        echo "id: $id" . PHP_EOL;
        echo "data: " . json_encode($d) . PHP_EOL;
        echo PHP_EOL;

        ob_flush();
        flush();
    }

    /**
     * Redirect to CSV Screen
     * @param Request $request
     */
    public function uploadCSV(Request $request)
    {
        $user = auth()->user();
        $user_id = $user->id;
        $perc = 0;

        $logFileTimestamp = Carbon::now()->format('YmdHs');
        Storage::put('userFiles\\' . $user_id . '\\log_' . $logFileTimestamp . '.txt', '');
        importUpdateStatus::dispatch($perc);

        if ($request->input('submit') != null && $request->method() == 'POST' && sizeof($request->file('files')) == 2) {

            $files = $request->file('files');
            $csvFile = new File;
            $csvFileName = '';
            $filename_csv = '';
            $extension_csv = '';
            $tempPath_csv = '';
            $fileSize_csv = '';
            $mimeType_csv = '';
            $imageFile = new File;
            $imageFileName = '';

            // dd($files);
            // Log::info(print_r($files, true));

            foreach ($files as $file) {
                $pieces = explode('.', $file->getClientOriginalName());

                // if ($pieces[1] == 'csv') {
                if (end($pieces) == 'csv') {
                    Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'CSV file found: processing.. .');
                    $csvFile = $file;
                    $csvFile->storeAs('userFiles\\' . $user_id, $file->getClientOriginalName());
                    // $csvFileName = $file->getClientOriginalName();
                    $filename_csv = $file->getClientOriginalName();
                    $extension_csv = $file->getClientOriginalExtension();
                    $tempPath_csv = $file->getRealPath();
                    $fileSize_csv = $file->getSize();
                    $mimeType_csv = $file->getMimeType();
                } else if (end($pieces) == 'zip') {
                    Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'ZIP file found: processing.. .');
                    $imageFile = $file;

                    // EXTRACT CONTENTS OF ZIP FILE IN THE STORAGE
                    $imageFile->storeAs('userFiles\\' . $user_id, $imageFile->getClientOriginalName());
                    $imageFileName = $imageFile->getClientOriginalName();

                    if (Storage::disk('local')->exists('\userFiles\\' . $user_id . '\\' . $imageFile->getClientOriginalName())) {
                        Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'ZIP file from local storage');

                        $zip  = Zip::open(storage_path('app') . '\userFiles\\' . $user_id . '\\' . $imageFile->getClientOriginalName());
                        Storage::put('que.txt', $zip);

                        $zip->extract(storage_path('app') . '\userFiles\\' . $user_id);
                        $zip->close();
                    } else {
                        Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'ZIP file not found');
                    }
                } else {
                    Storage::put('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'Invalid file format found: ' . $pieces[1]);
                }
            }

            Storage::put('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'File processing complete');

            // CSV FILE DETAILS
            /*if (Storage::disk('s3Public')->exists('userFiles\\'.$user_id.'\\'.$csvFile->getClientOriginalName())) {
                Storage::append('userFiles\\'.$user_id.'\log_'.$logFileTimestamp.'.txt', 'CSV file from S3 storage');
                
                $filename_csv = $csvFile->getClientOriginalName();
                $extension_csv = $csvFile->getClientOriginalExtension();
                $tempPath_csv = $csvFile->getRealPath();
                $fileSize_csv = $csvFile->getSize();
                $mimeType_csv = $csvFile->getMimeType();
            } else*/
            // Log::info(print_r('CSV FILE INFO: ' . $csvFile, true));
            if (Storage::disk('local')->exists('userFiles\\' . $user_id . '\\' . $csvFileName)) {
                Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'CSV file from local storage');
                // $filename_csv = $csvFileName;
                // $extension_csv = $csvFile->getClientOriginalExtension();
                // $tempPath_csv = $csvFile->getRealPath();
                // $fileSize_csv = $csvFile->getSize();
                // $mimeType_csv = $csvFile->getMimeType();
            } else {
                Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'CSV file from input file');
            }

            Storage::put('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'Reading CSV file complete');


            // VALID FILE EXTENSIONS
            $valid_extensions = array("csv");

            // 2MB IN BYTES
            $maxFileSize = 2097152;

            // CHECK FILE EXTENSION
            if (in_array(strtolower($extension_csv), $valid_extensions)) {

                // CHECK FILE SIZE
                if ($fileSize_csv <= $maxFileSize) {

                    // IMPORT CSV TO DATABASE
                    /*if (Storage::disk('s3Public')->exists('userFiles\\'.$user_id.'\\'.$csvFile->getClientOriginalName())) {
                        $customerArr = $this->csvToArray($tempPath_csv);
                        Storage::append('userFiles\\'.$user_id.'\log_'.$logFileTimestamp.'.txt', 'S3 CSV file array');
                    } else {*/
                    $customerArr = $this->csvToArray($tempPath_csv);
                    Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'Local CSV file array');
                    // }

                    if (is_array($customerArr)) {

                        // dd($customerArr);
                        Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', "\tTOTAL RECORDS = " . sizeof($customerArr));

                        for ($i = 0; $i < sizeof($customerArr); $i++) {

                            // Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', (string)$customerArr[$i]);

                            // dd($customerArr[$i]);
                            Log::info(print_r($customerArr[$i], true));
                            Log::info(isset($customerArr[$i]['name']));
                            Log::info(isset($customerArr[$i]['product_code']));
                            Log::info(isset($customerArr[$i]['product_barcode']));
                            Log::info(isset($customerArr[$i]['category_id']));
                            Log::info(isset($customerArr[$i]['market_id']));
                            Log::info(isset($customerArr[$i]['description']));
                            Log::info(isset($customerArr[$i]['price']));
                            Log::info(isset($customerArr[$i]['discount_price']));
                            Log::info(isset($customerArr[$i]['deliverable']));
                            Log::info(isset($customerArr[$i]['featured']));
                            Log::info(isset($customerArr[$i]['capacity']));
                            Log::info(isset($customerArr[$i]['unit']));
                            Log::info(isset($customerArr[$i]['package_items_count']));

                            Log::info("2ND PHASE");
                            Log::info(isset($customerArr[$i]['name']) && isset($customerArr[$i]['product_code']) && isset($customerArr[$i]['product_barcode']) && isset($customerArr[$i]['category_id']) && isset($customerArr[$i]['market_id']) && isset($customerArr[$i]['description']) && isset($customerArr[$i]['price']) && isset($customerArr[$i]['discount_price']) && isset($customerArr[$i]['deliverable']) && isset($customerArr[$i]['featured']) && isset($customerArr[$i]['capacity']) && isset($customerArr[$i]['unit']) && isset($customerArr[$i]['package_items_count']));

                            if ( isset($customerArr[$i]['name']) && isset($customerArr[$i]['product_code']) && isset($customerArr[$i]['product_barcode']) && isset($customerArr[$i]['category_id']) && isset($customerArr[$i]['market_id']) && isset($customerArr[$i]['description']) && isset($customerArr[$i]['price']) && isset($customerArr[$i]['discount_price']) && isset($customerArr[$i]['deliverable']) && isset($customerArr[$i]['featured']) && isset($customerArr[$i]['capacity']) && isset($customerArr[$i]['unit']) && isset($customerArr[$i]['package_items_count']) ) {
                                // && isset( $customerArr[$i]['image'] )

                                $productPresent = DB::table('products')->where([
                                    ['name', $customerArr[$i]['name']],
                                    ['market_id', $customerArr[$i]['market_id']],
                                ])->get();

                                // IF PRODUCT ALREADY PRESENT, UPDATE RECORD
                                if (sizeof($productPresent)) {

                                    Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'Product already present and updated for row ' . ($i + 1));

                                    DB::table('products')
                                        ->where('id', $productPresent[0]->id)
                                        ->update([
                                            'product_code' => $customerArr[$i]['product_code'],
                                            'product_barcode' => $customerArr[$i]['product_barcode'],
                                            'category_id' => $customerArr[$i]['category_id'],
                                            'market_id' => $customerArr[$i]['market_id'],
                                            'description' => $customerArr[$i]['description'],
                                            'price' => $customerArr[$i]['price'],
                                            'discount_price' => $customerArr[$i]['discount_price'],
                                            'deliverable' => $customerArr[$i]['deliverable'],
                                            'featured' => $customerArr[$i]['featured'],
                                            'capacity' => $customerArr[$i]['capacity'],
                                            'unit' => $customerArr[$i]['unit'],
                                            'package_items_count' => $customerArr[$i]['package_items_count']
                                        ]);
                                            //updating specialoffer if discounted price(helper function)
                                            updateSpecialOffer($customerArr[$i]['market_id']);

                                } else { // INSERT PRODUCT IN DB

                                    Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'Inserting record for row ' . ($i + 1));

                                    // UPLOAD UUID IN DB
                                    $uuid = Uuid::uuid4();
                                    DB::table('uploads')->insert([
                                        [
                                            'uuid' => $uuid,
                                            'created_at' => new \DateTime()
                                        ],
                                    ]);

                                    $uploadData = DB::table('uploads')
                                        ->orderBy('id', 'desc')
                                        ->first();
                                    $model_id = $uploadData->id;
                                    $uuid = $uploadData->uuid;

                                    $custom_properties = '{"uuid":"' . $uuid . '","user_id":' . $user_id . ',"generated_conversions":{"thumb":true,"icon":true}}';

                                    $order_column = DB::table('media')
                                        ->orderBy('order_column', 'desc')
                                        ->first();
                                    $order_column = $order_column->order_column + 1;

                                    // dd("PI",pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                                    // Storage::append('userFiles\\'.$user_id.'\log_'.$logFileTimestamp.'.txt', '  Path Info: '. storage_path('app').'\userFiles\\'. $user_id. '\\' . pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME) );

                                    // CHECK IF IMAGE IS IN CURRENT DIRECTORY OR NOT
                                    if (is_dir(storage_path('app') . '\userFiles\\' . $user_id . '\\' . pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME))) {
                                        $path = storage_path('app') . '\userFiles\\' . $user_id . '\\' . pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME) . '\\' . $customerArr[$i]['image'];
                                    } else if (is_dir(storage_path('app') . '\userFiles\\' . $user_id . '\\' . pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME))) {
                                        $path = storage_path('app') . '\userFiles\\' . $user_id . '\\' . $customerArr[$i]['image'];
                                    } else {
                                        $path = storage_path('app') . '\userFiles\\' . $user_id . '\\' . $customerArr[$i]['image'];
                                    }

                                    Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', '  Zip file extraction path: ' . $path);
                                    // dd("Path",$path);

                                    if (File::exists($path) && !empty($path) && !empty($customerArr[$i]['image'])) {
                                        // path does not exist

                                        $filename = basename($path);
                                        $file_name = $filename;

                                        $name = pathinfo($filename, PATHINFO_FILENAME);
                                        $name2 = $name;
                                        $name3 = $name;

                                        $imgg = Image::make($path);
                                        $size = $imgg->filesize();

                                        // INSERT UPLOAD MODEL TYPE IN MEDIA TABLE
                                        DB::table('media')->insert([
                                            [
                                                'model_type' => 'App\Models\Upload',
                                                'model_id' => (int)$model_id,
                                                'collection_name' => 'image',
                                                'name' => $name,
                                                'file_name' => $file_name,
                                                'mime_type' => 'image/jpeg',
                                                'disk' => 'public',
                                                'size' => (int)$size,
                                                'manipulations' => '[]',
                                                'custom_properties' => $custom_properties,
                                                'responsive_images' => '[]',
                                                'order_column' => $order_column,
                                                'created_at' => new \DateTime()
                                            ],
                                        ]);

                                        $mediaData = DB::table('media')
                                            ->orderBy('id', 'desc')
                                            ->first();
                                        $folder_id = $mediaData->id;

                                        // INSERT PRODUCT IN TABLE
                                        DB::table('products')->insert([
                                            'name' => $customerArr[$i]['name'],
                                            'product_code' => $customerArr[$i]['product_code'],
                                            'product_barcode' => $customerArr[$i]['product_barcode'],
                                            'category_id' => $customerArr[$i]['category_id'],
                                            'market_id' => $customerArr[$i]['market_id'],
                                            'description' => $customerArr[$i]['description'],
                                            'price' => $customerArr[$i]['price'],
                                            'discount_price' => $customerArr[$i]['discount_price'],
                                            'deliverable' => $customerArr[$i]['deliverable'],
                                            'featured' => $customerArr[$i]['featured'],
                                            'capacity' => $customerArr[$i]['capacity'],
                                            'unit' => $customerArr[$i]['unit'],
                                            'package_items_count' => $customerArr[$i]['package_items_count']
                                        ]);

                                        $productData = DB::table('products')
                                            ->orderBy('id', 'desc')
                                            ->first();
                                        $product_id = $productData->id;

                                        //updating specialoffer if discounted price(helper function)
                                        // try{
                                        updateSpecialOffer($customerArr[$i]['market_id']);

                                        // }
                                        // catch (\Exception $e) {
                                        //     Storage::put('queee.txt', $e->getMessage());

                                        //     // return $e->getMessage();
                                        // }
                                        // $market_id = $customerArr[$i]['market_id'];
                                        // $product = DB::table('products')->where('market_id', $market_id)->where('discount_price', '!=', 0)->get();
                                        // if (count($product) < 1) {
                                        //     // return $this->sendError('No product exist with discounted price.', 401);
                                        //     //  return [0];
                                        //     Market::where('id', $market_id)->update(array('special_offer' => 0));
                                        // } else {
                                        //     Market::where('id', $market_id)->update(array('special_offer' => 1));
                                        // }

                                        // INSERT PRODUCT MODEL TYPE IN MEDIA TABLE
                                        DB::table('media')->insert([
                                            [
                                                'model_type' => 'App\Models\Product',
                                                'model_id' => (int)$product_id,
                                                'collection_name' => 'image',
                                                'name' => $name,
                                                'file_name' => $file_name,
                                                'mime_type' => 'image/jpeg',
                                                'disk' => 'public',
                                                'size' => (int)$size,
                                                'manipulations' => '[]',
                                                'custom_properties' => $custom_properties,
                                                'responsive_images' => '[]',
                                                'order_column' => $order_column,
                                                'created_at' => new \DateTime()
                                            ],
                                        ]);

                                        $mediaData = DB::table('media')
                                            ->orderBy('id', 'desc')
                                            ->first();
                                        $folder_id = $mediaData->id;

                                        $filePath = storage_path() . '\app\public\\' .  $folder_id;
                                        File::isDirectory($filePath) or File::makeDirectory($filePath, 0775, true, true);

                                        $imgg = Image::make($path);
                                        $imgg->save(storage_path() . '\app\public\\' . $folder_id . '\\' . $file_name);
                                        // $this->storeImageToS3($imgg);

                                        $filePath = storage_path() . '\app\public\\' .  $folder_id . '\conversions';
                                        File::isDirectory($filePath) or File::makeDirectory($filePath, 0775, true, true);

                                        // GENERATE THUMB IMAGE
                                        $name2 .= '-thumb.jpg';
                                        $imgg2 = Image::make($path);
                                        $imgg2->resize(200, 200, function ($constraint) {
                                            $constraint->aspectRatio();
                                            $constraint->upsize();
                                        })->save(storage_path() . '\app\public\\' .  $folder_id . '\conversions\\' . $name2);
                                        // $this->storeImageToS3($imgg2);

                                        // GENERATE ICON IMAGE
                                        $name3 .= '-icon.jpg';
                                        $imgg3 = Image::make($path);
                                        $imgg3->resize(100, 100, function ($constraint) {
                                            $constraint->aspectRatio();
                                            $constraint->upsize();
                                        })->save(storage_path() . '\app\public\\' .  $folder_id . '\conversions\\' . $name3);
                                        // $this->storeImageToS3($imgg3);
                                    } else {
                                        // PATH DOES NOT EXIST EITHER IN LARAVEL OR S3
                                        Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', '  Image file path error in both local and S3 for row ' . ($i + 1));
                                    }
                                }
                            } else {
                                Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'Incomplete data entered in CSV file for row ' . ($i + 1));
                            }

                            $perc = ($i / sizeof($customerArr)) * 100;

                            $res = setcookie("importBarPercentage", ceil($perc), "/products");
                            // event(new importUpdateStatus(ceil($perc)));
                            importUpdateStatus::dispatch($perc);
                            // importUpdateStatus::dispatch();
                            // importUpdateStatus::dispatch();
                            // $evn::dispatch();
                            // $this->send_message($i, 'on iteration ' . $i . ' of '.sizeof($customerArr) , $i*sizeof($customerArr));
                            // Storage::put('userFiles\cookieFile.txt', '- Ret: '.$res);
                            // // Storage::put('userFiles\cookieFile.txt', '- '.$perc);
                            // // Storage::put('userFiles\cookieFile.txt', ' -');
                        }

                        // File::deleteDirectory(storage_path('app').'\public\userFiles\\'. $user_id);
                        // Storage::disk('s3Public')->delete('s3_folder_path/'. $imageName);
                        // File::deleteDirectory(Storage::disk('s3Public')."\public\userFiles\\". $user_id);
                        Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', "Import Successful");
                        Session::flash('message', 'Import Successful.');
                    } else {
                        Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'Improper/No records in CSV file');
                    }
                } else {
                    Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'CSV file is too large');
                    Session::flash('message', 'File too large. File must be less than 2MB.');
                }
            } else {
                Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'Invalid/Corrupt CSV file');
                Session::flash('message', 'Invalid File Extension.');
            }
        } else {
            Storage::append('userFiles\\' . $user_id . '\log_' . $logFileTimestamp . '.txt', 'Incomplete files uploaded');
            Session::flash('message', 'Invalid File Extension. Kindly make a \'.csv\' of product list and \'.zip\' file of all the images');
            // return false;
        }

        setcookie("importBarPercentage", "100", "/products");
        // $this->send_message('CLOSE', 'Process complete', "/");
        // event(new importUpdateStatus('100'));
        importUpdateStatus::dispatch('100');
        // importUpdateStatus::dispatch();
        // $evn::dispatch();
        // Storage::download('userFiles\\'.$user_id.'\log_'.$logFileTimestamp.'.txt');
        // Redirect to index
        // return redirect()->action('ProductController@index');


        return redirect(route('products.index'));
        // return redirect()->route('products.index');
    }

    function csvToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        return $data;
    }
}
