<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;

class EmployeeController extends Controller
{
    /**
     * Display dashboard with employees and departments
     */
    public function index()
    {

        $employees = Employee::with('department')->latest()->get();
        $departments = Department::all();
        $activeDepartments = Department::count();

        return view('dashboard', compact('employees', 'departments', 'activeDepartments'));
    }

    /**
     * Store a new employee
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'position' => 'required|string|max:255',
            'salary' => 'required|integer|min:0',
            'department_id' => 'required|exists:departments,id',
        ]);

        Employee::create($validated);
        return redirect()->back()->with('success', 'Employee added successfully.');
    }

    /**
     * Update an existing employee
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'position' => 'required|string|max:255',
            'salary' => 'required|integer|min:0',
            'department_id' => 'required|exists:departments,id',
        ]);

        $employee->update($validated);

        return redirect()->back()->with('success', 'Employee updated successfully.');
    }

    /**
     * Delete an employee
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->back()->with('success', 'Employee deleted successfully.');
    }
}