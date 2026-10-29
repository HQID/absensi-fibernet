<?php

namespace App\Livewire;

use App\Models\Student;
use App\Services\StudentDeletionService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class StudentsIndex extends Component
{
    use WithPagination;

    public ?Student $editingStudent = null;

    public string $endDate = '';

    public string $status = 'active';

    public ?Student $deletingStudent = null;

    public int $deleteStep = 1;

    public int $deleteAttendanceCount = 0;

    public int $deletePhotoCount = 0;

    public string $deleteConfirmationInput = '';

    public function editStudent(int $studentId): void
    {
        $student = Student::with('user')->findOrFail($studentId);
        $this->editingStudent = $student;
        $this->endDate = $student->end_date->format('Y-m-d');
        $this->status = $student->status;
        Flux::modal('edit-student-modal')->show();
    }

    public function saveStudent(): void
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

            Flux::modal('edit-student-modal')->close();
            Flux::toast(
                text: 'Data siswa berhasil diperbarui.',
                heading: 'Tersimpan',
                variant: 'success',
            );
        }
    }

    public function confirmDeleteStudent(int $studentId): void
    {
        $student = Student::with('user')->findOrFail($studentId);

        $this->deletingStudent = $student;
        $this->deleteStep = 1;
        $this->deleteConfirmationInput = '';
        $this->deleteAttendanceCount = $student->attendances()->count();
        $this->deletePhotoCount = $student->attendances()
            ->whereNotNull('check_in_photo_path')
            ->where('check_in_photo_path', '!=', '')
            ->count();

        $this->resetErrorBag('deleteConfirmationInput');
        Flux::modal('delete-student-modal')->show();
    }

    public function proceedToFinalDeleteStep(): void
    {
        if (! $this->deletingStudent) {
            return;
        }

        $this->deleteStep = 2;
        $this->deleteConfirmationInput = '';
        $this->resetErrorBag('deleteConfirmationInput');
    }

    public function backToFirstDeleteStep(): void
    {
        $this->deleteStep = 1;
        $this->resetErrorBag('deleteConfirmationInput');
    }

    public function deleteStudent(StudentDeletionService $deletionService): void
    {
        $this->resetErrorBag('deleteConfirmationInput');

        if (! $this->deletingStudent || $this->deleteStep !== 2) {
            return;
        }

        $expectedCode = trim($this->deletingStudent->student_code);

        if (trim($this->deleteConfirmationInput) !== $expectedCode) {
            $this->addError('deleteConfirmationInput', 'NIM / NISN yang diketik tidak sesuai.');

            return;
        }

        $studentUser = $this->deletingStudent->user()->first();
        $studentName = $studentUser ? $studentUser->name : $expectedCode;
        $deletedPhotos = $deletionService->delete($this->deletingStudent);

        $this->deletingStudent = null;
        $this->deleteStep = 1;
        $this->deleteConfirmationInput = '';

        Flux::modal('delete-student-modal')->close();
        Flux::toast(
            text: "Data {$studentName} beserta riwayat presensi dan {$deletedPhotos} foto berhasil dihapus permanen.",
            heading: 'Peserta Dihapus',
            variant: 'success',
        );
    }

    public function render(): View
    {
        return view('livewire.students-index', [
            'students' => Student::with('user')->latest()->paginate(10),
        ]);
    }
}
