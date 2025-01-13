<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Store;
use App\Models\Product;
use App\Models\SubOrder;
use App\Facades\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\OrderCreateRequest;
use App\Models\Warehouse;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderController extends Controller
{
    public function index(){
        $user=request()->user();
        $prepering_orders=$this->bringOrders($user->id,0);
        $delevering_orders=$this->bringOrders($user->id,1);
        $delevered_orders=$this->bringOrders($user->id,2);
        return response()->json([
            "prepering_orders"=>$prepering_orders,
            "delevering_orders"=>$delevering_orders,
            "delevered_orders"=>$delevered_orders], 200, );
    }

    public function store(OrderCreateRequest $request) {
        if (!count($request->data)) {
            return response()->json(["message" => "There is no item"], 401);
        }

        $index = [];
        $quantities = [];
        $stores = [];
        $total_price = 0;
        $count = 0;
        $retrieval_time=0;

        foreach ($request->data as $product) {
            if ($product['quantity'] < 1) {
                continue;
            }
            $index[] = $product['id'];
            $quantities[$product['id']] = $product['quantity'];
        }

        if (empty($index)) {
            return response()->json(["message" => "Invalid Order"], 401);
        }

        $general_order = $this->createOrder($request);
        $products = Product::whereIn('id', $index)->get();

        foreach ($products as $product) {
            if ($product->inventory()->first()->quantity - $quantities[$product->id] < 0) {
                DB::rollback();
                return response()->json(["message" => "Invalid Order"], 401);
            }
            $inventory_retrieval_time=Warehouse::where('store_id',$product->store()->first()->id)->first()->retrieval_time;
            $retrieval_time=max($retrieval_time,$inventory_retrieval_time);
            $id = $product->store()->first()->id;
            if (!array_key_exists($id, $stores)) {
                $newSubOrder = $this->createSubOrder($id, $general_order["id"]);
                $stores[$id] = $newSubOrder["id"];
                $count++;
            }
            $offer=$product->offer()->first();

            if ($offer && $offer->started_at->lt(Carbon::now()) &&  $offer->ended_at->gt(Carbon::now())){
                $price = $quantities[$product->id] * ($product->price-$offer->discount);
            }
            else{
                $price = $quantities[$product->id] * ($product->price);
            }
            $new_quantity = $product->inventory()->first()->quantity - $quantities[$product->id];

            $product->inventory()->update(['quantity' => $new_quantity]);
            $product->subOrders()->attach($stores[$id], ["quantity" => $quantities[$product->id], "price" => $price]);

            $total_price += $price;
        }
        $retrieval_time+=(float)$this->getDuration($request['longitude'],$request['latitude']);

        $general_order->update([
            'price' => $total_price,
            'total_sub_orders' => $count,
            'delivery_time'=>$retrieval_time
        ]);

        return response()->json(["message" => "Your order is being prepared"], 201);
    }


    public function update($orderId){
        $order=$this->deleteOrder($orderId);
        return response()->json($order, 200);
    }

    public function updateState($SubOrderId){
        $subOrder=SubOrder::where('id',$SubOrderId)->first();
        if(request()->user()->role=='USER' || !request()->user()->stores()->where('id',$subOrder->store_id)->first()){
            return response()->json(['message'=>'Unvalid serves'], 403);
        }
        if($subOrder->state > 1){
            return response()->json(['message'=>'The order alredy delevering'], 401);
        }
        else if($subOrder->state == 0){
            $subOrder->update([
                'state'=>1
            ]);
            $order = $subOrder->order()->first();
            $order->update([
                'completed_sub_orders' => $order->completed_sub_orders + 1
            ]);
            if($order->completed_sub_orders == $order->total_sub_orders){
                $order->update([
                    'state' => 1
                ]);
            }
            return response()->json(["message"=>"Your order is on its way"], 200);
        }
        else{
            $order = $subOrder->order()->first();
            foreach($order->subOrders()->get() as $subOrder){
                if($subOrder->state<1){
                    return response()->json(["message"=>"Some products did not prepared yet"], 200);
                }
            }
            $order->update([
                'state' => 2
            ]);
            foreach($order->subOrders()->get() as $subOrder){
                $subOrder->update([
                    'state' => 2
                ]);
            }
            return response()->json(["message"=>"Your order has been delivered"], 200);
        }
    }

    public function destroy($orderId){
         $this->deleteOrder($orderId);
        return response()->json(["message"=>"The order has been deleted successfully"], 200,);
    }

    public function ShowSubOrders(Request $request){

        if(request()->user()->role=='USER'){
            return response()->json(['message'=>'Unvalid serves'], 403);
        }

        $stores=request()->user()->stores()->get();

        $prepering_orders=$this->bringSubOrders($stores,0);
        $delevering_orders=$this->bringSubOrders($stores,1);
        $delevered_orders=$this->bringSubOrders($stores,2);
        return response()->json([
            "prepering_orders"=>$prepering_orders,
            "delevering_orders"=>$delevering_orders,
            "delevered_orders"=>$delevered_orders], 200, );
    }

    private function createOrder($request)
    {
        $user=request()->user();
        $validated=$request->validated();
        $validated['order']['user_id']=$user->id;
        $order= Order::create($validated['order']);
        $order->address()->create($validated['address']);
        return $order;
    }

    private function createSubOrder($storeId,$orderId){
         return SubOrder::create([
            'order_id'=>$orderId,
            'store_id'=>$storeId
        ]);
    }


    private function bringOrders($id,$state){
        $orders=Order::where('user_id',$id)->where('state',$state)->with('subOrders.products')->with('address')->get();

        $bringOrders=$this->getProducts($orders);
        return $bringOrders;
    }

    private function bringSubOrders($stores,$state){
        $index=[];
        foreach($stores as $store){
            $index[]=$store->id;
        }
        $subOrders=SubOrder::whereIn('store_id',$index)->where('state',$state)->with('products')->get();

        $subOrdersWithUsers=$subOrders->map(function($subOrder){
            $order=clone $subOrder->order()->first();
            $subOrder->user=$order->user()->first();
            return $subOrder;
        }
    );
    return $subOrdersWithUsers;
}

private function deleteOrder($orderId){
    $order=Order::where('id',$orderId)->with('subOrders.products')->with('address')->get();
    $allProducts=$this->getProducts($order);
    $this->editQuantityWhenDeleteOrder($allProducts->first()->products);
    $order->first()->delete();
    return $allProducts;
}

private function getProducts($orders){
    $bringOrders=$orders->map(function($order){

        $productsArray=[];

        foreach(clone $order->subOrders as $subOrder){
            $products=$subOrder->products->map(function($product){
                $product->pivotQuantity=$product->pivot->quantity;
                return $product;
            });
            $productsArray=array_merge($productsArray,($products)->toArray());
        }

        $order->products=$productsArray;
        unset($order->subOrders);
        return $order;

    });

    return $bringOrders;
}
private function editQuantityWhenDeleteOrder($products) :void
{
    $index=[];
    $quantities=[];
    foreach($products as $oneProduct){
        array_push($index,$oneProduct['id']);
        $quantities[$oneProduct['id']]=$oneProduct['pivotQuantity'];
    }
     $products=Product::whereIn('id',$index)->get();
     foreach($products as $product){
        $new_quantity= $product->inventory()->first()->quantity+$quantities[$product->id];
          $product->inventory()->update([
             'quantity'=>$new_quantity
         ]);}

}

        private function getDuration($longitude, $latitude)
    {
        $response = Http::get(
            config('services.map.osrm.route') . '36.2920484,33.4953687;' . $longitude . ',' . $latitude,
            ['overview' => 'false']
        );

        throw_if(
            $response->failed(),
            HttpException::class,
            "Failed to fetch data from the OSRM API. Status: {$response->status()}, Body: {$response->body()}"
        );

        return $response->json('routes')[0]['duration'];
    }

}
