<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\JudgeSubmission;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of all registered users.
     */
    public function index()
    {
        $users = User::latest()->get();

        return view('pages.users', compact('users'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in(['admin', 'instructor', 'student', 'user'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => strtolower($validated['role']),
            'status' => $validated['status'] ?? 'active',
            'password' => $validated['password'],
        ]);

        ActivityLogger::log(
            'User Created',
            "Admin created user '{$user->name}' ({$user->email}) with role " . strtoupper($user->role),
            'success',
            $request
        );

        return redirect()->route('users.index')->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(['admin', 'instructor', 'student', 'user'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => strtolower($validated['role']),
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $user->update($updateData);

        ActivityLogger::log(
            'User Updated',
            "Admin updated user '{$user->name}' ({$user->email})",
            'info',
            $request
        );

        return redirect()->route('users.index')->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent currently authenticated admin from deleting their own account
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own admin account.');
        }

        $name = $user->name;
        $email = $user->email;

        // Safely handle foreign key references before deleting user
        try {
            DB::transaction(function () use ($user) {
                // Nullify user references in judge submissions if any
                JudgeSubmission::where('user_id', $user->id)->update(['user_id' => null]);

                // Nullify user references in activity logs if any
                ActivityLog::where('user_id', $user->id)->update(['user_id' => null]);

                // Clear any active sessions for this user
                DB::table('sessions')->where('user_id', $user->id)->delete();

                $user->delete();
            });

            ActivityLogger::log(
                'User Deleted',
                "Admin deleted user '{$name}' ({$email})",
                'warning',
                request()
            );

            return redirect()->route('users.index')->with('success', "User '{$name}' deleted successfully.");
        } catch (\Throwable $e) {
            return redirect()->route('users.index')->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
}

