<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Employee;

class DepartmentController extends Controller
{
    /**
     * Display the courses page
     * Shows all courses in the database
     */
    public function index()
    {

        $departments = Department::latest()->get();
        return view('department', compact('departments'));
    }

    /**
     * Store a new department in database
     * Validates input then creates department
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'goal' => 'nullable|string|max:1000',
        ]);

        Department::create($validated);
        return redirect()->back()->with('success', 'Department added successfully.');
    }

    /**
     * Update an existing department
     * Finds department by ID and updates it
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'department_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'goal' => 'nullable|string|max:1000',
        ]);


        $department->update($validated);
        return redirect()->back()->with('success', 'Department updated successfully.');
    }

    /**
     * Delete a department from database
     * Removes department by ID
     */
    public function destroy(Department $department)
    {
        $department->delete();
        return redirect()->back()->with('success', 'Department deleted successfully.');
    }
}
