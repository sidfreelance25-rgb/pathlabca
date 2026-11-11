<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Doctor $doctor): bool
    {
        return $doctor->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Doctor $doctor): bool
    {
        return $doctor->user_id === $user->id;
    }

    public function delete(User $user, Doctor $doctor): bool
    {
        return $doctor->user_id === $user->id;
    }
}
