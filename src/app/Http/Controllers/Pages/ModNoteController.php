<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\ModNote;

class ModNoteController extends Controller
{
    public function store(Request $request, $uuid)
    {
        $request->validate([
            'actual_user_id' => 'required|exists:users,id',
            'note'           => 'required|string',
            'attachment'     => 'nullable|file|mimes:doc,docx,xls,xlsx,pdf,jpg,jpeg,png|max:10240',
        ]);

        $report = Report::where('uuid', $uuid)->firstOrFail();

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            
            $attachmentPath = $file->store('mod-notes', 'complaints'); 
        }

        ModNote::create([
            'report_id'       => $report->id,
            'actual_user_id'  => $request->actual_user_id,
            'note'            => $request->note,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        return redirect()->back()->with('success', 'Catatan MOD berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        try {
            $modNote = \App\Models\ModNote::findOrFail($id);
            if ($modNote->actual_user_id != auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak! Anda hanya dapat mengubah catatan atas nama Anda sendiri.'
                ], 403);
            }

            $request->validate([
                'note' => 'required|string'
            ]);

            $modNote->update([
                'note' => $request->note,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Catatan MOD berhasil diperbarui.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
