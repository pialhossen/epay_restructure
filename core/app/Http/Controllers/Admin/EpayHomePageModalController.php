<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EpayHomePageModalModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EpayHomePageModalController extends Controller
{
    public function index()
    {
        $pageTitle = 'Epay Home Page Modals';
        $items = EpayHomePageModalModel::latest('cd')->paginate(15);

        return view('admin.epaymodal.index', compact('pageTitle', 'items'));
    }

    public function create()
    {
        $pageTitle = 'Create New Modal';
        return view('admin.epaymodal.form', compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'button_name' => 'nullable|string|max:255',
            'image_link' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|integer|in:0,1',
            // 'remarks' => 'nullable|string',
        ]);
        
        $imagePath = null;
        if ($request->hasFile('image_link')) {
            $file = $request->file('image_link');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('uploads/modalImage');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $imagePath = 'uploads/modalImage/' . $filename;
        }

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'button_name' => $request->button_name,
            'image' => $imagePath,
            'cd' => now(),
            'status' => (int)$request->status,
        ];

        if ($request->has('status')) {
            if ((int)$request->status === 1) {
                EpayHomePageModalModel::query()->update(['status' => 0]);
            }
            $data['status'] = (int)$request->status;
        }

        EpayHomePageModalModel::create($data);

        return redirect()->route('admin.epaymodal.index')->with('success', 'Modal created successfully.');
    }

    public function edit($id)
    {
        $pageTitle = 'Edit Modal';
        $item = EpayHomePageModalModel::findOrFail($id);

        return view('admin.epaymodal.form', compact('pageTitle', 'item'));
    }

    public function update(Request $request, $id)
    {
        $item = EpayHomePageModalModel::findOrFail($id);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'button_name' => 'nullable|string|max:255',
            'image_link' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|integer|in:0,1',
            // 'remarks' => 'nullable|string',
        ]);
        if ($request->hasFile('image_link')) {
            // Delete old image if exists
            if ($item->image_link && file_exists(public_path($item->image_link))) {
                unlink(public_path($item->image_link));
            }
            
            $file = $request->file('image_link');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('uploads/modalImage');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $imagePath = 'uploads/modalImage/' . $filename;
        } else {
            $imagePath = $item->image; // keep old if no new file uploaded
        }

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'button_name' => $request->button_name,
            'image' => $imagePath,
            'cd' => now(),
            'status' => (int)$request->status,
        ];

        if ($data['status'] == 1 && $data['status'] != $item->status) {
            EpayHomePageModalModel::where('id','!=',$item->id)->update(['status' => 0]);
        }

        $item->update($data);

        return redirect()->route('admin.epaymodal.index')->with('success', 'Modal updated successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:epay_home_page_modals,id',
            'status' => 'required|in:0,1'
        ]);

        $item = EpayHomePageModalModel::findOrFail($request->id);
        $item->status = $request->status == 1 ? 1 : 0;
        $item->ub = Auth::id();
        $item->ud = now();
        $item->save();

        return response()->json(['success' => true, 'status' => $item->status]);
    }


    public function delete($id)
    {
        $item = EpayHomePageModalModel::findOrFail($id);

        // Delete image from public folder
        if ($item->image_link && file_exists(public_path($item->image_link))) {
            unlink(public_path($item->image_link));
        }

        $item->delete();

        return back()->with('success', 'Modal deleted successfully.');
    }
}
