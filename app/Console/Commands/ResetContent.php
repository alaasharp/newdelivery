<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Restaurant;
use App\User;
use App\City;
use App\Order;
use App\DeliveryBoy;
use App\DeliveryBoyApiToken;
use App\DeliveryBoyMessage;
use App\Product;
use App\ProductImage;
use App\Category;
use App\PromoCode;
use App\Settings;
use App\Customer;
use App\NewsItem;
use App\DeliveryArea;
use App\OrderedProduct;
use App\OrderStatus;

// Completely reset database contents,
// to be run each 2 hours on demo server
class ResetContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:content';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill database with demo content';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->deleteAll();
        echo "Deleted everything\n";
        $city = City::create([
            'name' => 'Default city',
            'sort' => 500
        ]);
        $restaurant = Restaurant::create([
            'name' => 'Default restaurant',
            'city_id' => $city->id,
            'sort' => 500
        ]);
        $this->createNews($city->id);
        echo "Created news\n";
        $this->createProducts($restaurant->id, $city->id);
        echo "Created products\n";
        $this->createDeliveryAreas($city->id);
        DeliveryBoy::create([
            'name' => 'John Smith',
            'login' => 'boy1',
            'password' => bcrypt('password')
        ]);
        PromoCode::create([
            'name' => '$5 discount for orders above $30',
            'code' => 'simple',
            'discount' => 5,
            'discount_in_percent' => 0,
            'limit_use_count' => 0,
            'active_from' => date('Y-m-d'),
            'active_to' => '2050-01-01',
            'min_price' => 30
        ]);
        PromoCode::create([
            'name' => '30% discount for orders above $100',
            'code' => 'percent',
            'discount' => 30,
            'discount_in_percent' => 1,
            'limit_use_count' => 0,
            'active_from' => date('Y-m-d'),
            'active_to' => '2050-01-01',
            'min_price' => 100
        ]);
        Settings::create([
            'delivery_price' => 0,
            'currency_format' => 'EGP:value',
            'pushwoosh_id' => 'b4777be6-2850-4f99-8c71-b2fff49d92e6',
            'pushwoosh_token' => 'NTdlNmM1MGYtMGI5Ni00Y2FhLWE0ODEtNTgwODE0YTIwOTNk',
            'gcm_project_id' => '',
            'date_format' => 'd/m/Y',
            'notification_email' => 'delivrooapp@gmail.com',
            'mail_from_mail' => 'amedyouseef@gmail.com',
            'mail_from_new_order_subject' => 'طلب شراء جديد',
            'mail_from_name' => '🛵  من دليفرو',
            'stripe_publishable' => '987456321',
            'stripe_private' => '987654123',
            'paypal_client_id' => '123456789',
            'paypal_client_secret' => '987654321',
            'paypal_currency' => 'USD',
            'multiple_restaurants' => false,
            'multiple_cities' => false,
            'signup_required' => true,
            'time_format_backend' => 'd/M/Y H:i',
            'time_format_app' => 'dd/MM/yyyy HH:mm',
            'date_format_app' => 'dd/MM/yyyy',
            'driver_onesignal_id' => '4542d475-523d-4b74-84ab-3373528fad31',
            'driver_onesignal_token' => 'OGFhNmRmOTAtMWU3Mi00M2ZjLTlmYTEtMTcxZjFhZTczYjlj',
            'send_push_on_status_change' => true
        ]);
        User::create([
        	'email' => 'admin@example.com',
        	'password' => bcrypt('password'),
        	'name' => 'admin'
        ]);
        Customer::create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'phone' => '12341234',
            'name' => 'John Doe'
        ]);
        echo "Done!!!\n";
    }

    public function createDeliveryAreas($city_id)
    {
        DeliveryArea::create([
            'name' => 'Moscow centre',
            'coords' => '[{"lat":55.83554952813204,"lng":37.360978842609484},{"lat":55.92336689054051,"lng":37.498307944171984},{"lat":55.898736455276584,"lng":37.729020834796984},{"lat":55.80623291526097,"lng":37.866349936359484},{"lat":55.68255207589151,"lng":37.855363608234484},{"lat":55.60815527904811,"lng":37.794938803546984},{"lat":55.558478870586995,"lng":37.608171225421984},{"lat":55.63296991482031,"lng":37.437883139484484},{"lat":55.74907925458769,"lng":37.339006186359484},{"lat":55.807776445635554,"lng":37.355485678546984}]',
            'price' => 3.0,
            'city_id' => $city_id
        ]);
        DeliveryArea::create([
            'name' => 'Bangkok centre',
            'coords' => '[{"lat":13.718690982139048,"lng":100.40994079355471},{"lat":13.809393131961516,"lng":100.40444762949221},{"lat":13.809393131961516,"lng":100.50607116464846},{"lat":13.85206430222147,"lng":100.58572204355471},{"lat":13.73203173790169,"lng":100.61868102792971},{"lat":13.622615195912044,"lng":100.61868102792971},{"lat":13.611937692896884,"lng":100.52529723886721},{"lat":13.63062300674803,"lng":100.40444762949221}]',
            'price' => 2.5,
            'city_id' => $city_id
        ]);
        DeliveryArea::create([
            'name' => 'New York centre',
            'coords' => '[{"lat":40.941573014397186,"lng":-74.20888655729084},{"lat":41.030725206323886,"lng":-73.96169417447834},{"lat":40.976833635739865,"lng":-73.67879622525959},{"lat":40.7608268338786,"lng":-73.69802229947834},{"lat":40.59001719799534,"lng":-73.60463851041584},{"lat":40.583759768293646,"lng":-74.05233138150959},{"lat":40.498182895133695,"lng":-74.29952376432209},{"lat":40.60670081482032,"lng":-74.45607894010334},{"lat":40.81281587335936,"lng":-74.28029769010334}]',
            'price' => 5.0,
            'city_id' => $city_id
        ]);
        OrderStatus::create([
            'name' => 'Created',
            'sort' => 1,
            'is_default' => true,
            'available_to_delivery_boy' => false
        ]);
        OrderStatus::create([
            'name' => 'Processing',
            'sort' => 500,
            'is_default' => false,
            'available_to_delivery_boy' => false
        ]);
        OrderStatus::create([
            'name' => 'En Route',
            'sort' => 550,
            'is_default' => false,
            'available_to_delivery_boy' => false
        ]);
        OrderStatus::create([
            'name' => 'Delivered',
            'sort' => 600,
            'is_default' => false,
            'available_to_delivery_boy' => true
        ]);
        OrderStatus::create([
            'name' => 'Cancelled',
            'sort' => 650,
            'is_default' => false,
            'available_to_delivery_boy' => true
        ]);
    }

    public function createNews($city_id)
    {
        NewsItem::create([
            'title' => 'Double dose Butreco',
            'image' => '/demo/news1.jpg',
            'announce' => 'Only this Tuesday',
            'full_text' => 'Only this Tuesday! Buy one Butreco and get double dose for free! Promotion is available until Butreco in stock.',
            'city_id' => $city_id
        ]);
        NewsItem::create([
            'title' => 'New carrot juice is available for delivery now',
            'image' => '/demo/news2.jpg',
            'announce' => 'Refresh yourself!',
            'full_text' => 'Bringing healthy drinks! We are starting to deliver new absolutely amazing carrot juice just for $5/portion.',
            'city_id' => $city_id
        ]);
        NewsItem::create([
            'title' => 'Seafood menu',
            'image' => '/demo/news3.jpg',
            'announce' => 'New protein-reach menu, shrimps and other amazing seafood',
            'full_text' => 'Grilled shrimps, seabass and lobster in garlic sauce - discover now in our menu!',
            'city_id' => $city_id
        ]);
    }

    public function createProducts($restaurant_id, $city_id)
    {
        $pizza_cat = Category::create([
            'name' => 'Pizza',
            'parent_id' => null,
            'image' => '/demo/category1.jpg',
            'restaurant_id' => $restaurant_id,
            'city_id' => $city_id
        ]);
        $drinks_cat = Category::create([
            'name' => 'Drinks',
            'parent_id' => null,
            'image' => '/demo/category2.jpg',
            'restaurant_id' => $restaurant_id,
            'city_id' => $city_id
        ]);
        $noodle_cat = Category::create([
            'name' => 'Pasta and noodle',
            'parent_id' => null,
            'image' => '/demo/category3.jpg',
            'restaurant_id' => $restaurant_id,
            'city_id' => $city_id
        ]);
        $noodle_sub_cat = Category::create([
            'name' => 'Noodles',
            'parent_id' => $noodle_cat->id,
            'image' => '/demo/category4.jpg',
            'restaurant_id' => $restaurant_id,
            'city_id' => $city_id
        ]);
        $pasta_sub_cat = Category::create([
            'name' => 'Pasta',
            'parent_id' => $noodle_cat->id,
            'image' => '/demo/category5.jpg',
            'restaurant_id' => $restaurant_id,
            'city_id' => $city_id
        ]);
        $p1 = Product::create([
            'category_id' => $drinks_cat->id,
            'name' => 'Coca Cola',
            'description' => 'A 0.33l can of legendary Coke',
            'price' => '1.8'
        ]);
        ProductImage::create([
            'image' => '/demo/product1.jpg',
            'product_id' => $p1->id
        ]);
        $p2 = Product::create([
            'category_id' => $drinks_cat->id,
            'name' => 'Carrot juice',
            'description' => 'Refreshing carrot juice for your health. 0.5l plastic bottle on delivery',
            'price' => '2.3'
        ]);
        ProductImage::create([
            'image' => '/demo/product2.jpg',
            'product_id' => $p2->id
        ]);
        $p3 = Product::create([
            'category_id' => $pizza_cat->id,
            'name' => 'Sausage pizza',
            'description' => 'Amazing pizza, full of pure Italian sausages and salami',
            'price' => '9.5'
        ]);
        ProductImage::create([
            'image' => '/demo/product3.jpg',
            'product_id' => $p3->id
        ]);
        $p4 = Product::create([
            'category_id' => $pizza_cat->id,
            'name' => 'Margherita',
            'description' => 'Famous Italian four-cheeze pizza, reach of mozzarella',
            'price' => '8.5'
        ]);
        ProductImage::create([
            'image' => '/demo/product4.jpg',
            'product_id' => $p4->id
        ]);
        $p5 = Product::create([
            'category_id' => $pizza_cat->id,
            'name' => 'Vegetarian',
            'description' => 'Vegetarian pizza, with increased amount of cucumber and tomatoes',
            'price' => '9.0'
        ]);
        ProductImage::create([
            'image' => '/demo/product5.jpg',
            'product_id' => $p5->id
        ]);
        $p6 = Product::create([
            'category_id' => $pasta_sub_cat->id,
            'name' => 'Bolognese with cheese',
            'description' => 'Classic Bolognese spaghetti recipe, improved with additional cheese',
            'price' => '12.0'
        ]);
        ProductImage::create([
            'image' => '/demo/product6.jpg',
            'product_id' => $p6->id
        ]);
        $p7 = Product::create([
            'category_id' => $pasta_sub_cat->id,
            'name' => 'Shrimps pasta',
            'description' => 'High-protein pasta recipe with steamed tiger shrimps',
            'price' => '14.0'
        ]);
        ProductImage::create([
            'image' => '/demo/product7.jpg',
            'product_id' => $p7->id
        ]);
        $p8 = Product::create([
            'category_id' => $pasta_sub_cat->id,
            'name' => 'Cream cheese pasta',
            'description' => 'Pasta with ricotta cheese and basil leaves',
            'price' => '14.0'
        ]);
        ProductImage::create([
            'image' => '/demo/product8.jpg',
            'product_id' => $p8->id
        ]);
        $p9 = Product::create([
            'category_id' => $noodle_sub_cat->id,
            'name' => 'Pad Thai',
            'description' => 'Classical Thai meal with noodles, tofu and beans',
            'price' => '11.0'
        ]);
        ProductImage::create([
            'image' => '/demo/product9.jpg',
            'product_id' => $p9->id
        ]);
        $p10 = Product::create([
            'category_id' => $noodle_sub_cat->id,
            'name' => 'Egg Noodles',
            'description' => 'Egg Noodle with spices and leaves',
            'price' => '8.5'
        ]);
        ProductImage::create([
            'image' => '/demo/product10.jpg',
            'product_id' => $p10->id
        ]);
    }

    public function deleteAll()
    {
    	foreach (User::all() as $item) { $item->delete(); }
        foreach (OrderedProduct::all() as $item) { $item->delete(); }
        foreach (Order::all() as $item) { $item->delete(); }
        foreach (DeliveryArea::all() as $item) { $item->delete(); }
        foreach (PromoCode::all() as $item) { $item->delete(); }
        foreach (ProductImage::all() as $item) { $item->delete(); }
        foreach (Product::all() as $item) { $item->delete(); }
        foreach (Settings::all() as $item) { $item->delete(); }
        foreach (Category::all() as $item) { $item->delete(); }
        foreach (NewsItem::all() as $item) { $item->delete(); }
        foreach (\App\ApiToken::all() as $c) { $c->delete(); }
        foreach (\App\Customer::all() as $c) { $c->delete(); }
        foreach (Restaurant::all() as $item) { $item->delete(); }
        foreach (City::all() as $item) { $item->delete(); }
        foreach (OrderStatus::all() as $item) { $item->delete(); }
        foreach (DeliveryBoyMessage::all() as $item) { $item->delete(); }
        foreach (DeliveryBoyApiToken::all() as $item) { $item->delete(); }
        foreach (DeliveryBoy::all() as $item) { $item->delete(); }
    }
}
