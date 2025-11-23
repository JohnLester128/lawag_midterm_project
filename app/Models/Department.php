<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * This allows us to use Course::create() with these fields
     */
    protected $fillable = [
        'department_name',
        'description',
        'goal',
    ];

    /**
     * Get all students enrolled in this course
     * Relationship: One course has many students
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}