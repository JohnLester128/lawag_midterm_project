<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;

class EmployeeController extends Controller
{
    /**
     * Display dashboard with employees and departments
     */
    public function index(Request $request)
    {
        $query = Employee::with('department');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('department_filter')) {
            $query->where('department_id', $request->department_filter);
        }

        $employees = $query->latest()->get();
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
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('employee-photos', 'public');
            $validated['photo'] = $photoPath;
        }

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
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('photo')) {
           if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }
            $photoPath = $request->file('photo')->store('employee-photos', 'public');
            $validated['photo'] = $photoPath;
        }



        $employee->update($validated);

        return redirect()->back()->with('success', 'Employee updated successfully.');
    }

    /**
     * Delete an employee
     */
    public function destroy(Employee $employee)
    {

        
        $employee->delete();
        return redirect()->back()->with('success', 'Employee successfully move to trash.');
    }

    public function trash()
    {
        $employees = Employee::onlyTrashed()->with('department')->latest('deleted_at')->get();
        $departments = Department::all();

        return view('trash', compact('employees', 'departments'));
    }

    public function restore($id)
    {
        $employee = Employee::withTrashed()->findOrFail($id);
        $employee->restore();

        return redirect()->route('employees.trash')->with('success', 'Employee restored successfully.');
    }

    public function forceDelete($id)
    {
        $employee = Employee::withTrashed()->findOrFail($id);

        if ($employee->photo) {
            Storage::disk('public')->delete($employee->photo);
        }

        $employee->forceDelete();

        return redirect()->route('employees.trash')->with('success', 'Employee permanently deleted.');
    }

    /**
     * Export employees to PDF
     */
    public function export(Request $request)
    {
        $query = Employee::with('department');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('department_filter') && $request->department_filter != '') {
            $query->where('department_id', $request->department_filter);
        }

        $employees = $query->latest()->get();

        $filename = 'employees_export_' . date('Y-m-d_His') . '.pdf';

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Employees Export</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    background-color: #f5f5f5;
                }
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    background-color: white;
                    padding: 30px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #333;
                    text-align: center;
                    margin-bottom: 10px;
                }
                .export-info {
                    text-align: center;
                    color: #666;
                    margin-bottom: 30px;
                    font-size: 14px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th {
                    background-color: #4472C4;
                    color: white;
                    padding: 12px;
                    text-align: left;
                    font-weight: bold;
                    border: 1px solid #2e5c9a;
                }
                td {
                    padding: 10px 12px;
                    border: 1px solid #ddd;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                tr:hover {
                    background-color: #f0f0f0;
                }
                .footer {
                    margin-top: 20px;
                    padding: 15px;
                    background-color: #f0f0f0;
                    border-radius: 5px;
                    text-align: center;
                    font-weight: bold;
                    color: #333;
                }
                @media print {
                    body {
                        background-color: white;
                    }
                    .container {
                        box-shadow: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Employees Export Report</h1>
                <div class="export-info">
                    Exported on: ' . date('F d, Y \a\t h:i A') . '<br>
                    Total Records: ' . $employees->count() . '
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Salary</th>
                            <th>Department</th>
                            <th>Hired Date</th>
                        </tr>
                    </thead>
                    <tbody>';

                $number = 1;
                foreach ($employees as $employee) {
                    $html .= '<tr>
                    <td>' . $number++ . '</td>
                    <td>' . htmlspecialchars($employee->name) . '</td>
                    <td>' . htmlspecialchars($employee->email) . '</td>
                    <td>' . htmlspecialchars($employee->position) . '</td>
                    <td>$' . number_format($employee->salary, 2) . '</td>
                    <td>' . htmlspecialchars($employee->department ? $employee->department->department_name : 'N/A') . '</td>
                    <td>' . $employee->created_at->format('Y-m-d H:i:s') . '</td>
                </tr>';
                }

                $html .= '</tbody>
                </table>

                <div class="footer">
                    Total Employees: ' . $employees->count() . '
                </div>
            </div>
        </body>
        </html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->stream($filename, ['Attachment' => true]);
    }

}