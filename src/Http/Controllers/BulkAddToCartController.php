<?php

namespace Webkul\BulkAddToCart\Http\Controllers;

use Excel;
use Cart;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

use Webkul\Admin\Imports\DataGridImport;
use Webkul\Product\Repositories\ProductFlatRepository;
use Webkul\Product\Repositories\ProductRepository as Product;

class BulkAddToCartController extends Controller
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    /**
     *
     * @var array
     */
    protected $product;

    /**
     * ProductFlatRepository object
     *
     * @var array
     */
    protected $productFlatRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Product\Repositories\ProductRepository       $product
     * @param  \Webkul\Product\Repositories\ProductFlatRepository   $productFlatRepository
     *
     * @return void
     */
    public function __construct(
        Product $product,
        ProductFlatRepository $productFlatRepository
    ) {
        $this->product = $product;
        $this->productFlatRepository = $productFlatRepository;

        $this->_config = request('_config');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view($this->_config['view']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $valid_extension = ['xlsx', 'csv', 'xls', 'ods'];

        if (! in_array(request()->file('file')->getClientOriginalExtension(), $valid_extension)) {
            session()->flash('error', trans('bulkaddtocart::app.products.upload-error'));

            return redirect()->back();
        } else {
            try {
                $excelData = (new DataGridImport)->toArray(request()->file('file'));
                $cart = [];

                foreach ($excelData as $data) {
                    foreach ($data as $column => $uploadData) {
                        unset($uploadData[""]);

                        if (count(
                            array_diff(
                                array_keys($uploadData), [
                                    'sku',
                                    'quantity',
                                    'links',
                                    'qty',
                                    'bundle_option',
                                    'bundle_option_qty',
                                    'booking_date',
                                    'booking_slot',
                                    'booking_from',
                                    'booking_to',
                                    'note',
                                    'event_id',
                                    'type',
                                ]
                            )
                        ) > 0) {
                            session()->flash('error', trans('bulkaddtocart::app.products.invalid-column'));

                            return redirect()->back();
                        }

                        $validator = Validator::make($uploadData, [
                            'sku' => 'required',
                            'quantity' => 'required|numeric|min:1',
                        ]);

                        $product = $this->product->findOneWhere([
                            'sku' => $uploadData['sku'],
                        ]);

                        if ($product) {
                            $productFlat = $this->productFlatRepository->findOneWhere([
                                'product_id' => $product->id
                            ]);
                        }

                        if ($product && $productFlat && $productFlat->status == 1) {
                            $canAdd = $product->haveSufficientQuantity($uploadData['quantity']);

                            if (! $canAdd) {
                                $sufficientQuantity[] = $column + 1;
                            } else if ($product->type == 'simple' && $uploadData['quantity'] > 0) {
                                if ($product->parent_id != null) {
                                    $parentProduct = $this->product->findOneWhere([
                                        'id' => $product->parent_id,
                                    ]);

                                    $parentProductFlat = $this->productFlatRepository->findOneWhere([
                                        'product_id' => $parentProduct->id,
                                    ]);

                                    if ($parentProductFlat->status) {
                                        foreach ($parentProduct->super_attributes as $super_attribute) {
                                            if ($super_attribute->type == 'select') {
                                                foreach ($product->attribute_values as $attribute_value) {
                                                    if ($super_attribute->id == $attribute_value->attribute_id) {
                                                        $attributes[$super_attribute->id] = (string)$attribute_value->integer_value;
                                                    }
                                                }
                                            }
                                        }

                                        $cart['product'] = (string)$parentProduct->id;
                                        $cart['product_id'] = (string)$parentProduct->id;
                                        $cart['quantity'] = (string)$uploadData['quantity'];
                                        $cart['is_configurable'] = 'true';
                                        $cart['selected_configurable_option'] = (string)$product->id;
                                        $cart['super_attribute'] = $attributes;
                                    }
                                } else {
                                    $cart['product'] = (string)$product->id;
                                    $cart['product_id'] = (string)$product->id;
                                    $cart['quantity'] = (string)$uploadData['quantity'];
                                    $cart['is_configurable'] = 'false';
                                }
                            }

                            switch ($product->type) {
                                case 'virtual':
                                    $cart['product'] = (string)$product->id;
                                    $cart['product_id'] = (string)$product->id;
                                    $cart['quantity'] = (string)$uploadData['quantity'];
                                    $cart['is_configurable'] = 'false';
                                break;

                                case 'grouped':
                                    $qty = $uploadData['qty'] ?? [];
                                    $qty = explode('|', $uploadData['qty']);

                                    $quantities = [];
                                    foreach ($qty as $index => $quantity) {
                                        $productQty = explode(',', $quantity);
                                        $quantities[$productQty[0]] = $productQty[1];
                                    }

                                    $cart['qty'] = $quantities;
                                    $cart['product'] = (string)$product->id;
                                    $cart['product_id'] = (string)$product->id;
                                    $cart['quantity'] = (string)$uploadData['quantity'];
                                    $cart['is_configurable'] = 'false';
                                break;

                                case 'downloadable':
                                    $links = $uploadData['links'] ?? [];
                                    $links = explode(',', $uploadData['links']);

                                    $cart['links'] = $links;
                                    $cart['product'] = (string)$product->id;
                                    $cart['product_id'] = (string)$product->id;
                                    $cart['quantity'] = (string)$uploadData['quantity'];
                                    $cart['is_configurable'] = 'false';
                                break;

                                case 'bundle':
                                    $uploadData['bundle_option'] = explode(',', $uploadData['bundle_option']);
                                    $uploadData['bundle_option_qty'] = explode(',', $uploadData['bundle_option_qty']);

                                    foreach ($uploadData['bundle_option'] as $index => $bundleOption) {
                                        $bundleOptions[$index + 1] = [
                                            $bundleOption
                                        ];

                                        $bundleOptionQty[$index + 1] = $uploadData['bundle_option_qty'][$index];
                                    }

                                    $cart['bundle_options'] = $bundleOptions;
                                    $cart['bundle_option_qty'] = $bundleOptionQty;
                                    $cart['product'] = (string)$product->id;
                                    $cart['product_id'] = (string)$product->id;
                                    $cart['quantity'] = (string)$uploadData['quantity'];
                                    $cart['is_configurable'] = 'false';
                                break;

                                case 'booking':
                                    $bookingProduct = app('\Webkul\BookingProduct\Repositories\BookingProductRepository')
                                                        ->findOneWhere([
                                                            'product_id' => $product->id
                                                        ]);

                                    if ($bookingProduct) {
                                        switch ($bookingProduct->type) {
                                            case 'default':
                                                $bookingTo = str_replace("|", "-", $uploadData['booking_to']);
                                                $bookingFrom = str_replace("|", "-", $uploadData['booking_from']);
                                                $bookingDate = str_replace("|", "-", $uploadData['booking_date']);

                                                $uploadData['booking_slot'] = explode('-', $uploadData['booking_slot']);

                                                $cart['booking'] = [
                                                    'date' => $bookingDate,
                                                    'slot' => strtotime($bookingFrom . " " . $uploadData['booking_slot'][0])
                                                                . "-" .
                                                              strtotime($bookingTo . " " . $uploadData['booking_slot'][1]),
                                                ];
                                            break;

                                            case 'appointment':
                                                $bookingDate = str_replace("|", "-", $uploadData['booking_date']);
                                                $uploadData['booking_slot'] = explode('-', $uploadData['booking_slot']);

                                                $cart['booking'] = [
                                                    'date' => $bookingDate,
                                                    'slot' => strtotime($bookingDate . " " . $uploadData['booking_slot'][0])
                                                                . "-" .
                                                              strtotime($bookingDate . " " . $uploadData['booking_slot'][1]),
                                                ];
                                            break;

                                            case 'event':
                                                $qty = explode(',', $uploadData['qty']);
                                                $eventIds = explode(',', $uploadData['event_id']);

                                                $eventTickets = app('\Webkul\BookingProduct\Repositories\BookingProductEventTicketRepository')
                                                                    ->findWhere([
                                                                        'booking_product_id' => $bookingProduct->id
                                                                    ])
                                                                    ->all();

                                                if ($eventTickets) {
                                                    foreach ($eventIds as $index => $eventId) {
                                                        if (isset($eventTickets[$eventId - 1])) {
                                                            $ticketId = $eventTickets[$eventId - 1]->id;
                                                            $eventIds[$index] = $ticketId;
                                                        }
                                                    }
                                                }

                                                $uploadData['qty'] = [];
                                                foreach ($qty as $index => $singleQty) {
                                                    $uploadData['qty'][$eventIds[$index]] = $singleQty;
                                                }

                                                $cart['booking'] = [
                                                    'qty' => $uploadData['qty']
                                                ];
                                            break;

                                            case 'rental':
                                                $bookingRental = app('\Webkul\BookingProduct\Repositories\BookingProductRentalSlotRepository')
                                                                ->findOneWhere([
                                                                    'booking_product_id' => $bookingProduct->id
                                                                ]);

                                                if ($bookingRental->renting_type != 'daily') {
                                                    $bookingDate = str_replace("|", "-", $uploadData['booking_date']);
                                                    $uploadData['booking_to'] = str_replace("|", ":", $uploadData['booking_to']);
                                                    $uploadData['booking_from'] = str_replace("|", ":", $uploadData['booking_from']);

                                                    $bookingTo = strtotime($bookingDate . " " . $uploadData['booking_to']);
                                                    $bookingFrom = strtotime($bookingDate . " " . $uploadData['booking_from']);

                                                    $cart['booking'] = [
                                                        'slot' => [
                                                            'to' => $bookingTo,
                                                            'from' => $bookingFrom,
                                                        ],
                                                        'date' => $bookingDate,
                                                    ];
                                                } else {
                                                    $bookingTo = str_replace("|", "-", $uploadData['booking_to']);
                                                    $bookingFrom = str_replace("|", "-", $uploadData['booking_from']);

                                                    $cart['booking'] = [
                                                        'date_to' => $bookingTo,
                                                        'date_from' => $bookingFrom,
                                                    ];
                                                }
                                            break;

                                            case 'table':
                                                $bookingDate = str_replace("|", "-", $uploadData['booking_date']);
                                                $uploadData['booking_slot'] = explode('-', $uploadData['booking_slot']);

                                                $cart['booking'] = [
                                                    'date' => $bookingDate,
                                                    'note' => $uploadData['note'],
                                                    'slot' => strtotime($bookingDate . " " . $uploadData['booking_slot'][0])
                                                                . "-" .
                                                              strtotime($bookingDate . " " . $uploadData['booking_slot'][1]),
                                                ];
                                            break;

                                            default:
                                            break;
                                        }

                                        $cart['product'] = (string)$product->id;
                                        $cart['product_id'] = (string)$product->id;
                                        $cart['quantity'] = (string)$uploadData['quantity'];
                                        $cart['is_configurable'] = 'false';
                                    }
                                break;

                                default:
                                break;
                            }

                            if (sizeof($cart) > 0) {
                                $result = Cart::addProduct($cart['product'], $cart);

                                Cart::collectTotals();
                            }
                        } else {
                            $skuError[] = $column + 1;
                        }

                        if ($validator->fails()) {
                            $failedRules[$column+1] = $validator->errors();
                        }
                    }
                }

                if (isset($failedRules)) {
                    foreach ($failedRules as $coulmn => $fail) {
                        if ($fail->first('sku')) {
                            $errorMsg[$coulmn] = $fail->first('sku');
                        } else if ($fail->first('quantity')) {
                            $errorMsg[$coulmn] = $fail->first('quantity');
                        }
                    }

                    foreach ($errorMsg as $key => $msg) {
                        $msg = str_replace(".", "", $msg);
                        $message[] = $msg. ' at Row '  .$key . '.';
                    }

                    $finalMsg[] = implode(" ", $message);
                }

                if (isset($skuError)) {
                    $errorRows = implode(",", $skuError);
                    $finalMsg[] = trans('bulkaddtocart::app.products.sku-error') . ' ' . $errorRows . '.';
                }

                if (isset($sufficientQuantity)) {
                    $errorRows = implode(",", $sufficientQuantity);
                    $finalMsg[] = trans('bulkaddtocart::app.products.quantity-error') . ' ' . $errorRows . '.';
                }

                $cartItemsCount = 0;
                $cart = cart()->getCart();

                if ($cart) {
                    $items = $cart->items;

                    $cartItemsCount = $items->count();
                }

                if (! $cartItemsCount) {
                    session()->flash('error', trans('bulkaddtocart::app.products.invalid-data'));

                    return redirect()->back();
                }

                if (isset($finalMsg)) {
                    $finalErrorMsg = implode(" ", $finalMsg);
                    session()->forget('success');
                    session()->forget('warning');
                    session()->flash('error', $finalErrorMsg);

                    return redirect()->back();
                } else {
                    session()->flash('success', trans('bulkaddtocart::app.products.upload-success'));

                    return redirect()->route($this->_config['redirect']);
                }
            } catch (\Exception $e) {
                if (! ($e->getMessage() == "Undefined index: product_id")) {
                    session()->flash('error', trans($e->getMessage()));
                } else {
                    session()->flash('success', trans('bulkaddtocart::app.products.upload-success'));
                }

                return redirect()->back();
            }
        }
    }

    /**
     * Download Sample
     *
     * @return \Illuminate\Http\Response
     */
    public function downLoadSample()
    {
        return Storage::download('sample/sample.xls');
    }
}
