<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class AdminController extends Controller
{
    public function index() {
        return view("admin.index");
    }

    public function brands() {
        $brands = Brand::orderBy('id', 'DESC')->paginate(10);
        return view('admin.brands', compact('brands'));
    }

    public function add_brand() {
        return view('admin.brand-add');
    }

    public function brand_store(Request $request) {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048',
        ]);

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->slug);
        $image = $request->file('image');  // Get The image info
        $file_extension = $request->file('image')->extension(); // get the image extension
        $file_name = Carbon::now()->timestamp . '.' . $file_extension; // file name like 1723391965.jpg
        $this->GenerateBrandThumbialsImage($image, $file_name, 'brands');
        $brand->image = $file_name;
        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand has been added successfully');
    }

    public function edit_brand($id) {
        $brand = Brand::find($id);
        return view('admin.brand-edit', compact('brand'));
    }

    public function update_brand(Request $request) {
        $request->validate(([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,'.$request->id,
            'image' => 'mimes:png,jpg,jpeg|max:2048',
        ]));

        $brand = Brand::find($request->id);
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->slug);
        if($request->hasFile('image')) {
            if(File::exists(public_path("uploads/brands/$brand->image"))){
                File::delete("uploads/brands/$brand->image");
            }
            $image = $request->file('image');  // Get The image info
            $file_extension = $request->file('image')->extension(); // get the image extension
            $file_name = Carbon::now()->timestamp . '.' . $file_extension; // file name like 1723391965.jpg
            $this->GenerateBrandThumbialsImage($image, $file_name, 'brands');
            $brand->image = $file_name;
        }
        
        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand has been updated successfully');
    }

    public function brand_destroy($id) {
        $brand = Brand::find($id);
        if(File::exists(public_path("uploads/brands/$brand->image"))) {
            File::delete("uploads/brands/$brand->image");
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status', 'Brand has been deleted successfully');
    }

    public function GenerateBrandThumbialsImage($image, $imageName, $folder) {
        $destinationPath = public_path("uploads/$folder");
        $img = Image::read($image->path());
        $img->cover(124,124,"top");
        $img->resize(124, 124, function($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath.'/'.$imageName);
    }

    public function categories() {
        $categories = Category::orderBy('id', 'DESC')->paginate(10);
        return view('admin.categories', compact('categories'));
    }

    public function add_category() {
        return view('admin.category-add');
    }

    public function store_category(Request $request) {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug',
            'image' => 'mimes:jpg,png,jpeg|max:2048'
        ]);
        $category = new Category();
        $category->name = $request->name;
        $category->slug = Str::slug($request->slug);
        $image = $request->file('image');
        $file_extension = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . "." . $file_extension;
        $this->GenerateBrandThumbialsImage($image, $file_name, 'categories');
        $category->image = $file_name;
        $category->save();
        return redirect()->route('admin.categories')->with('status', 'New Category has been added successfully');
    }

    public function edit_category($id) {
        $category = Category::find($id);
        return view('admin.category-edit', compact('category'));
    }

    public function update_category(Request $request) {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,'.$request->id,
            'image' => 'mimes:jpg,jpeg,png|max:2048'
        ]);

        $category = Category::find($request->id);
        $category->name = $request->name;
        $category->slug = Str::slug($request->slug);
        if($request->hasFile('image')) {
            if(File::exists(public_path("uploads/categories/$category->image"))) {
                File::delete(public_path("uploads/categories/$category->image"));
            }
            $image = $request->file('image');
            $file_extension = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . "." . $file_extension;
            $this->GenerateBrandThumbialsImage($image, $file_name, 'categories');
            $category->image = $file_name;
        }
        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category has been Updated successfully');
    }

    public function delete_category($id) {
        $category = Category::find($id);
        if(File::exists(public_path("uploads/brands/$category->image"))) {
            File::delete("uploads/categories/$category->image");
        }
        $category->delete();
        return redirect()->route('admin.categories')->with('status', 'Category has been deleted successfully');
    }

    public function products() {
        $products = Product::orderBy('id', 'DESC')->paginate(10);
        return view('admin.products', ['products' => $products]);
    }

    public function add_product() {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands     = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.product_add', compact('categories', 'brands'));
    }

    public function store_product(Request $request) {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'required|mimes:jpg,jpeg,png|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->slug);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbialsImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;

        if($request->hasFile('images')) {
            $allowedFileExtension = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach($files as $file) {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedFileExtension);
                if($gcheck) {
                    $gfileName = $current_timestamp . '-' . $counter . '.' . $gextension;
                    $this->GenerateProductThumbialsImage($file, $gfileName);
                    array_push($gallery_arr, $gfileName);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',', $gallery_arr);
        }
        $product->images = $gallery_images;
        $product->save();
        return redirect()->route('admin.products')->with('status', 'Product has been added successfully');
    }

    public function GenerateProductThumbialsImage($image, $imageName) {
        $destinationPath = public_path('uploads/products');
        $destinationPathThumbnail = public_path('uploads/products/thumbnails');
        $img = Image::read($image->path());
        $img->cover(540, 689, "top");
        $img->resize(540, 689, function($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);

        $img->resize(104, 104, function($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPathThumbnail . '/' . $imageName);
    }

    public function edit_product($id) {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands     = Brand::select('id', 'name')->orderBy('name')->get();
        $product    = Product::findOrFail($id);
        return view('admin.product_edit', compact('categories', 'brands', 'product'));
    }

    public function update_product(Request $request) {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,'.$request->id,
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'mimes:jpg,jpeg,png|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = Product::findOrFail($request->id);
        $product->name = $request->name;
        $product->slug = Str::slug($request->slug);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;

        if($request->hasFile('image')) {
            if(File::exists(public_path("uploads/products/$product->image"))) {
                File::delete(public_path("uploads/products/$product->image"));
            }
            if(File::exists(public_path("uploads/products/thumbnails/$product->image"))) {
                File::delete(public_path("uploads/products/thumbnails/$product->image"));
            }
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbialsImage($image, $imageName);
            $product->image = $imageName;
        }


        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;

        if($request->hasFile('images')) {
            foreach(explode(',', $product->images) as $file) {
                if(File::exists(public_path('uploads/products' . '/' . $file))){
                    File::delete(public_path('uploads/products' . '/' . $file));
                }
                if(File::exists(public_path('uploads/products/thumbnails' . '/' . $file))) {
                    File::delete(public_path('uploads/products/thumbnails' . '/' . $file));
                }
            }
            $allowedFileExtension = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach($files as $file) {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedFileExtension);
                if($gcheck) {
                    $gfileName = $current_timestamp . '-' . $counter . '.' . $gextension;
                    $this->GenerateProductThumbialsImage($file, $gfileName);
                    array_push($gallery_arr, $gfileName);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',', $gallery_arr);
            $product->images = $gallery_images;
        }
        $product->save();
        return redirect()->route('admin.products')->with('status', 'Product has been updated successfully');
    }

    public function delete_product($id) {
        $product = Product::findOrFail($id);
        if(File::exists(public_path("uploads/products/$product->image"))){
            File::delete(public_path("uploads/products/$product->image"));
        }
        if(File::exists(public_path("uploads/products/thumbnails/$product->image"))) {
            File::delete(public_path("uploads/products/thumbnails/$product->image"));
        }

        foreach(explode(',', $product->images) as $file) {
            if(File::exists(public_path('uploads/products' . '/' . $file))){
                File::delete(public_path('uploads/products' . '/' . $file));
            }
            if(File::exists(public_path('uploads/products/thumbnails' . '/' . $file))) {
                File::delete(public_path('uploads/products/thumbnails' . '/' . $file));
            }
        }
        $product->delete();
        return redirect()->route('admin.products')->with('status', 'Product has been deleted successfully');
    }

    public function coupons() {
        $coupons = Coupon::orderBy('expiry_date', 'DESC')->paginate(12);
        return view('admin.coupons', compact('coupons'));
    }

    public function add_coupon() {
        return view('admin.coupon_add');
    }

    public function store_coupon(Request $request) {
        $request->validate([
            'code' => 'required|unique:coupons,code',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date',
        ]);

        $coupon = new Coupon();
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'New Coupon has been added successfully');
    }

    public function edit_coupon($id) {
        $coupon = Coupon::findOrFail($id);
        return view('admin.coupon_edit', compact('coupon'));
    }

    public function update_coupon(Request $request) {
        $request->validate([
            'code' => 'required|unique:coupons,code,'.$request->id,
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date',
        ]);

        $coupon = Coupon::find($request->id);
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been Updated successfully');
    }

    public function delete_coupon($id) {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been Deleted successfully');
    }

    public function orders() {
        $orders = Order::orderBy('id', 'DESC')->paginate(12);
        return view('admin.orders', compact('orders'));
    }

    public function order_details($order_id) {
        $order = Order::findOrFail($order_id);
        if($order_id) {
            $orderItems = OrderItem::where('order_id', $order->id)->orderBy('id')->paginate(12);
            $transaction = Transaction::where('order_id', $order->id)->first();
            return view('admin.order-details', compact('order', 'orderItems', 'transaction'));
        }      
    }

    public function update_order_status(Request $request) {
        $order = Order::findOrFail($request->order_id);
        $order->status = $request->order_status;
        if($request->order_status == 'delivered') {
            $order->delivered_date = Carbon::now();
        }elseif($request->order_status == 'canceled') {
            $order->canceled_date = Carbon::now();
        }
        $order->save();
        if($request->order_status == 'delivered') {
            $transaction = Transaction::where('order_id', $request->order_id)->firstOrFail();
            $transaction->status = 'approved';
            $transaction->save();
        }
        return back()->with('status', 'Status changed successfully');
    }
}
