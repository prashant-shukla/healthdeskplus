<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class MedicalRecordController extends Controller
{
    public function index(Request $request)
    {
        $doctor = $request->user()->doctor;
        $records = MedicalRecord::where('doctor_id', $doctor->id)
                               ->with(['patient', 'appointment'])
                               ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $records
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'record_date' => 'required|date',
            'type' => 'required|in:consultation,lab_report,imaging,surgery,vaccination,other',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = $request->user()->doctor;
        $record = MedicalRecord::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'practice_id' => $doctor->practice_id,
            'record_number' => 'REC-' . uniqid(),
            'record_date' => $request->record_date,
            'type' => $request->type,
            'title' => $request->title,
            'content' => $request->content,
            'vital_signs' => $request->vital_signs,
            'attachments' => $request->attachments,
            'notes' => $request->notes,
            'is_private' => $request->is_private ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Medical record created successfully',
            'data' => $record
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $record = MedicalRecord::where('doctor_id', $doctor->id)
                             ->with(['patient', 'appointment'])
                             ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $record
        ]);
    }

    public function update(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $record = MedicalRecord::where('doctor_id', $doctor->id)->findOrFail($id);
        
        $record->update($request->only([
            'record_date', 'type', 'title', 'content', 'vital_signs',
            'attachments', 'notes', 'is_private'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Medical record updated successfully',
            'data' => $record
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $doctor = $request->user()->doctor;
        $record = MedicalRecord::where('doctor_id', $doctor->id)->findOrFail($id);
        
        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medical record deleted successfully'
        ]);
    }
}