<?php

namespace App\Http\Controllers;

use App\Models\BookCalculation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class BookCalculationController extends Controller
{
    /**
     * Calculate visible stacks from all directions and save to database
     * 
     * @param Request $request
     * @return JsonResponse
     */
  public function calculate(Request $request): JsonResponse
{
    // Get user from token using sanctum guard explicitly
    $user = $request->user('sanctum'); // ← ADD 'sanctum' HERE
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }

    // Validate input
    $validator = Validator::make($request->all(), [
        'grid_size' => 'required|integer|min:1|max:50',
        'grid_data' => 'required|array',
        'grid_data.*' => 'required|array',
        'grid_data.*.*' => 'required|integer|min:0|max:1000',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $gridSize = $request->input('grid_size');
    $gridData = $request->input('grid_data');

    // Validate grid dimensions
    if (count($gridData) !== $gridSize) {
        return response()->json([
            'success' => false,
            'message' => "Grid must have exactly {$gridSize} rows"
        ], 422);
    }

    foreach ($gridData as $rowIndex => $row) {
        if (count($row) !== $gridSize) {
            return response()->json([
                'success' => false,
                'message' => "Row {$rowIndex} must have exactly {$gridSize} columns"
            ], 422);
        }
    }

    // Calculate visible stacks using the algorithm
    $result = $this->calculateVisibleStacks($gridData, $gridSize);

    // Save to database with authenticated user
    $calculation = BookCalculation::create([
        'user_id' => $user->id,
        'grid_size' => $gridSize,
        'grid_data' => $gridData,
        'visible_stacks' => $result['visible_count'],
        'visibility_details' => $result['details'],
    ]);


    return response()->json([
        'success' => true,
        'message' => 'Calculation completed successfully',
        'data' => $calculation,
    ], 201);
}

    /**
     * Get all calculations with optional sorting
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $sortOrder = $request->query('sort', 'desc');

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $perPage = $request->query('per_page', 5);
        $perPage = min(max((int)$perPage, 1), 100); // Between 1 and 100

        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

       $paginationCount = BookCalculation::where('user_id', $user->id)->count();

        $calculations = BookCalculation::where('user_id', $user->id)
            ->orderBy('created_at', $sortOrder)
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $calculations,
            'paginationcount' => $paginationCount
        ]);
    }

    /**
     * Get a single calculation by ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $calculation = BookCalculation::where('user_id', $user->id)
            ->find($id);

        if (!$calculation) {
            return response()->json([
                'success' => false,
                'message' => 'Calculation not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $calculation
        ]);
    }

    /**
     * Delete a calculation
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $calculation = BookCalculation::where('user_id', $user->id)
            ->find($id);

        if (!$calculation) {
            return response()->json([
                'success' => false,
                'message' => 'Calculation not found'
            ], 404);
        }

        $calculation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Calculation deleted successfully'
        ]);
    }

    /**
     * MAIN ALGORITHM: Calculate visible stacks from all 4 directions
     * 
     * This is the core algorithm that solves the problem.
     * A stack is visible from a direction if there's no taller or equal-height 
     * stack between it and the viewer.
     * 
     * @param array $grid - 2D array of stack heights
     * @param int $n - Grid size (N×N)
     * @return array - Contains visible count and detailed information
     */
    private function calculateVisibleStacks(array $grid, int $n): array
    {
        $visibleCount = 0;
        $counted = []; // Track which stacks have been counted (avoid duplicates)
        
        // Store detailed visibility information
        $details = [
            'from_top' => [],
            'from_bottom' => [],
            'from_left' => [],
            'from_right' => [],
            'visible_positions' => []
        ];

        // ========================================
        // LOOK FROM TOP (for each column, scan downward)
        // ========================================
        for ($col = 0; $col < $n; $col++) {
            $maxHeight = -1; // Track the tallest stack seen so far
            
            for ($row = 0; $row < $n; $row++) {
                $currentHeight = $grid[$row][$col];
                
                // Stack is visible if it's taller than all previous stacks in this column
                if ($currentHeight > 0 && $currentHeight > $maxHeight) {
                    $key = "{$row}-{$col}";
                    
                    // Count this stack only if we haven't counted it before
                    if (!isset($counted[$key])) {
                        $visibleCount++;
                        $counted[$key] = true;
                        $details['visible_positions'][] = ['row' => $row, 'col' => $col];
                    }
                    
                    $details['from_top'][] = [
                        'row' => $row,
                        'col' => $col,
                        'height' => $currentHeight
                    ];
                    
                    $maxHeight = $currentHeight;
                }
            }
        }

        // ========================================
        // LOOK FROM BOTTOM (for each column, scan upward)
        // ========================================
        for ($col = 0; $col < $n; $col++) {
            $maxHeight = -1;
            
            for ($row = $n - 1; $row >= 0; $row--) {
                $currentHeight = $grid[$row][$col];
                
                if ($currentHeight > 0 && $currentHeight > $maxHeight) {
                    $key = "{$row}-{$col}";
                    
                    if (!isset($counted[$key])) {
                        $visibleCount++;
                        $counted[$key] = true;
                        $details['visible_positions'][] = ['row' => $row, 'col' => $col];
                    }
                    
                    $details['from_bottom'][] = [
                        'row' => $row,
                        'col' => $col,
                        'height' => $currentHeight
                    ];
                    
                    $maxHeight = $currentHeight;
                }
            }
        }

        // ========================================
        // LOOK FROM LEFT (for each row, scan rightward)
        // ========================================
        for ($row = 0; $row < $n; $row++) {
            $maxHeight = -1;
            
            for ($col = 0; $col < $n; $col++) {
                $currentHeight = $grid[$row][$col];
                
                if ($currentHeight > 0 && $currentHeight > $maxHeight) {
                    $key = "{$row}-{$col}";
                    
                    if (!isset($counted[$key])) {
                        $visibleCount++;
                        $counted[$key] = true;
                        $details['visible_positions'][] = ['row' => $row, 'col' => $col];
                    }
                    
                    $details['from_left'][] = [
                        'row' => $row,
                        'col' => $col,
                        'height' => $currentHeight
                    ];
                    
                    $maxHeight = $currentHeight;
                }
            }
        }

        // ========================================
        // LOOK FROM RIGHT (for each row, scan leftward)
        // ========================================
        for ($row = 0; $row < $n; $row++) {
            $maxHeight = -1;
            
            for ($col = $n - 1; $col >= 0; $col--) {
                $currentHeight = $grid[$row][$col];
                
                if ($currentHeight > 0 && $currentHeight > $maxHeight) {
                    $key = "{$row}-{$col}";
                    
                    if (!isset($counted[$key])) {
                        $visibleCount++;
                        $counted[$key] = true;
                        $details['visible_positions'][] = ['row' => $row, 'col' => $col];
                    }
                    
                    $details['from_right'][] = [
                        'row' => $row,
                        'col' => $col,
                        'height' => $currentHeight
                    ];
                    
                    $maxHeight = $currentHeight;
                }
            }
        }

        return [
            'visible_count' => $visibleCount,
            'details' => $details
        ];
    }
}