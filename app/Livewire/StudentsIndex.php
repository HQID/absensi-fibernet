<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Student;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class StudentsIndex extends Component
{
    use WithPagination;

    public $editingStudent = null;
    public $endDate = '';
    public $status = 'active';

    public function editStudent($studentId)
    {
        $student = Student::findOrFail($studentId);
        $this->editingStudent = $student;
        $this->endDate = $student->end_date->format('Y-m-d');
        $this->status = $student->status;
        \Flux\Flux::modal('edit-student-modal')->show();
    }

    public function saveStudent()
    {
        $this->validate([
            'endDate' => 'required|date',
            'status' => 'required|in:active,inactive',
        ]);

        if ($this->editingStudent) {
            $this->editingStudent->update([
                'end_date' => $this->endDate,
                'status' => $this->status,
            ]);
            
            \Flux\Flux::modal('edit-student-modal')->close();
            flux()->toast(
                text: 'Data siswa berhasil diperbarui.',
                heading: 'Tersimpan',
                variant: 'success',
            );
        }
    }

    public function render()
    {
        return view('livewire.students-index', [
            'students' => Student::with('user')->latest()->paginate(10)
        ]);
    }
}

