<?php

/**
 * Modified by: Talemul Islam
 * Website: https://talemul.com
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\BlockLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlockController extends Controller
{
    private $user;
    public function __construct()
    {
        $this->user = auth()->guard('admin')->user();
        if($this->user->cannot("View - Block Data Alert") && $this->user->id != 1){
            abort(403);
        }
    }
    public function index(Request $request)
    {
        $query = Block::with('blockLines');

        if ($request->filled('q')) {
            $search = $request->q;

            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhereHas('blockLines', function ($q) use ($search) {
                    $q->where('data', 'LIKE', "%{$search}%");
                });
        }

        $blocks = $query->latest()->paginate(5)->withQueryString();
        $pageTitle = 'Blocked data';
        return view('admin.blocks.index', compact('pageTitle', 'blocks'));
    }

    public function create()
    {
        $pageTitle = 'Blocked data create';
        return view('admin.blocks.form', compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'block_lines.*' => 'required|string|max:191',
        ]);

        DB::transaction(function () use ($request) {
            $block = Block::create(['name' => $request->name]);

            foreach ($request->block_lines as $line) {
                $block->blockLines()->create(['data' => $line]);
            }
        });

        return redirect()->route('admin.blocks.index')->with('success', 'Block created successfully.');
    }

    public function show(Block $block)
    {
        $pageTitle = 'Blocked data show';
        $block->load('blockLines');
        return view('admin.blocks.show', compact('pageTitle', 'block'));
    }

    public function edit(Block $block)
    {
        $pageTitle = 'Blocked data edit';
        $block->load('blockLines');
        return view('admin.blocks.form', compact('pageTitle', 'block'));
    }

    public function update(Request $request, Block $block)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'block_lines.*' => 'nullable|string|max:191',
            'line_ids.*' => 'nullable|integer'
        ]);

        DB::transaction(function () use ($request, $block) {
            $block->update(['name' => $request->name]);

            $existingIds = $block->blockLines()->pluck('id')->toArray();
            $incomingIds = array_filter($request->line_ids ?? []);
            $deletedIds = array_diff($existingIds, $incomingIds);

            BlockLine::whereIn('id', $deletedIds)->delete();

            foreach ($request->block_lines as $index => $lineData) {
                $lineId = $request->line_ids[$index] ?? null;

                if ($lineId && in_array($lineId, $existingIds)) {
                    BlockLine::where('id', $lineId)->update(['data' => $lineData]);
                } else {
                    $block->blockLines()->create(['data' => $lineData]);
                }
            }
        });

        return redirect()->route('admin.blocks.index')->with('success', 'Block updated successfully.');
    }

    public function destroy(Block $block)
    {
        $block->delete();
        return redirect()->route('admin.blocks.index')->with('success', 'Block deleted successfully.');
    }
}
