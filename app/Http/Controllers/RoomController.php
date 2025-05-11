<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::query();
    
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
    
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
    
        $rooms = $query->latest()->paginate(10)->withQueryString(); // Keep filters during pagination
    
        return view('admin.rooms.index', compact('rooms'));
    }    

    public function create()
    {
        return view('admin.rooms.add');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'floor' => 'nullable|string|max:255',
            'category' => 'required|in:Sala,Auditorio,Roof',
            'image' => 'nullable|image|max:2048',
            'available_from' => 'required|date_format:H:i',
            'available_to' => 'required|date_format:H:i',
            'capacity' => 'required|integer|min:1',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('rooms', 'public');
        }

        Room::create($validated);

        return redirect()->route('admin.rooms.index')->with('success', 'Room created successfully.');
    }

    public function edit(Room $room)
    {
        return view('admin.rooms.add', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'floor' => 'nullable|string|max:255',
            'category' => 'required|in:Sala,Auditorio,Roof',
            'image' => 'nullable|image|max:2048',
            'available_from' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'available_to' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'capacity' => 'required|integer|min:1',
        ]);

        // Handle image removal if requested
        if ($request->has('remove_image') && $room->image_url) {
            Storage::disk('public')->delete($room->image_url);
            $validated['image_url'] = null;
        }

        // Handle new image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('rooms', 'public');
            $validated['image_url'] = $imagePath;
        }

        $room->update($validated);

        return redirect()->route('admin.rooms.index')->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return redirect()->route('admin.rooms.index')->with('success', 'Room deleted successfully.');
    }
}