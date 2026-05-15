<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $r)
    {
        //
        $user = $r->user();
        if ($user->role === 'pegawai') {
            $list = LeaveRequest::where('user_id', $user->id)->latest()->get();
        } elseif ($user->role === 'atasan') {
            $list = LeaveRequest::where('status', 'pending')->latest()->get();
        } else { // admin
            $list = LeaveRequest::latest()->get();
        }
        return response()->json($list);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        try {
            $data = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'type' => 'required|string',
                'reason' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:5120'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';
        // Normalize dates to start of day to avoid timezone/time issues
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $data['days'] = $start->diffInDays($end) + 1;
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }
        $lr = LeaveRequest::create($data);
        return response()->json($lr, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
        try {
            return response()->json(LeaveRequest::findOrFail($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengajuan cuti tidak ditemukan'], 404);
        }
    }

    public function approve($id, Request $r)
    {
        try {
            $lr = LeaveRequest::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengajuan cuti tidak ditemukan'], 404);
        }

        // Hanya atasan atau admin yang bisa approve
        if (!in_array($r->user()->role, ['atasan', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $lr->update([
            'status' => 'approved',
            'approved_by' => $r->user()->id,
            'approved_at' => now()
        ]);
        return response()->json($lr);
    }


    public function reject($id, Request $r)
    {
        try {
            $lr = LeaveRequest::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengajuan cuti tidak ditemukan'], 404);
        }

        // Hanya atasan atau admin yang bisa reject
        if (!in_array($r->user()->role, ['atasan', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $lr->update([
            'status' => 'rejected',
            'approved_by' => $r->user()->id,
            'approved_at' => now()
        ]);
        return response()->json($lr);
    }

    public function destroy($id, Request $r)
    {
        try {
            $lr = LeaveRequest::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengajuan cuti tidak ditemukan'], 404);
        }

        if ($r->user()->role === 'pegawai') {
            if ($lr->user_id !== $r->user()->id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            if ($lr->status !== 'pending') {
                return response()->json(['message' => 'Hanya pengajuan dengan status pending yang boleh dihapus'], 403);
            }
        }

        $lr->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $lr = LeaveRequest::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengajuan cuti tidak ditemukan'], 404);
        }

        // Hanya pegawai pemilik atau admin yang bisa update
        if ($request->user()->role === 'pegawai' && $lr->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $data = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'type' => 'required|string',
                'reason' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $data['days'] = $start->diffInDays($end) + 1;

        $lr->update($data);
        return response()->json($lr);
    }
    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        //
    }

    

    /**
     * Remove the specified resource from storage.
     */
}
