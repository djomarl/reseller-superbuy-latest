<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SuperbuyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SuperbuyController extends Controller
{
  protected $superbuyService;

  public function __construct(SuperbuyService $superbuyService)
  {
    $this->superbuyService = $superbuyService;
  }

  public function index()
  {
    // Check if user has saved curl/cookie in session or db?
    // Current requirement is just to show the viewer.
    return view('superbuy.index');
  }

  public function fetch(Request $request)
  {
    $request->validate([
      'curl' => 'required|string',
      'pages' => 'nullable|integer|min:1|max:20',
    ]);

    try
    {
      $orders = $this->superbuyService->getOrdersFromCurl(
        $request->input('curl'),
        $request->input('pages', 3)
      );

      return response()->json([
        'success' => true,
        'orders' => $orders
      ]);
    }
    catch (\Exception $e)
    {
      Log::error("Superbuy Fetch Error: " . $e->getMessage());
      return response()->json([
        'success' => false,
        'error' => $e->getMessage()
      ], 400); // Bad Request or 500
    }
  }

  public function import(Request $request)
  {
    $request->validate([
      'items' => 'required|array', // Array of items to import
      // Expected structure: items[0] = { title, price, ... }
    ]);

    $count = 0;
    /** @var User $user */
    $user = Auth::user();

    foreach ($request->input('items') as $itemData)
    {
      // itemData needs to contain orderNo as well, which is flattened in the JS viewer probably?
      // Or we just pass the structure we got from fetch?
      // Let's assume the frontend sends the exact item object + orderNo.

      $orderNo = $itemData['orderNo'] ?? 'UNKNOWN'; // Should be passed from frontend

      try
      {
        $this->superbuyService->importItem($user, $itemData, $orderNo);
        $count++;
      }
      catch (\Exception $e)
      {
        // Continue or log?
        Log::warning("Failed to import item: " . ($itemData['title'] ?? 'N/A'));
      }
    }

    return response()->json([
      'success' => true,
      'message' => "$count items succesfully imported!"
    ]);
  }
}
