<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a paginated listing of registered listeners with search and filters.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $cleanPhone = preg_replace('/[^0-9+]/', '', $search);

            $query->where(function ($q) use ($search, $cleanPhone) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");

                if (strlen($cleanPhone) >= 3) {
                    $q->orWhere('phone', 'like', "%{$cleanPhone}%");
                }
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'disabled') {
                $query->where('is_active', false);
            }
        }

        $listeners = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'recent' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('admin.users.index', compact('listeners', 'stats'));
    }

    /**
     * Show the form for editing the specified listener.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified listener in storage with input validation.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'city' => 'nullable|string|max:100',
            'is_active' => 'required|boolean',
        ], [
            'name.required' => 'Please enter the listener name.',
            'phone.required' => 'Please enter a valid phone number.',
            'phone.unique' => 'This phone number is already registered to another listener.',
        ]);

        // Clean phone string
        $validated['phone'] = preg_replace('/[^0-9+]/', '', trim($validated['phone']));

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Listener profile updated successfully.');
    }

    /**
     * Toggle the active/disabled status of a listener account.
     */
    public function toggleStatus(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Listener profile #{$user->id} ({$user->name}) has been {$status}.");
    }

    /**
     * Remove the specified listener from storage.
     */
    public function destroy(User $user)
    {
        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "Listener '{$userName}' deleted successfully.");
    }

    /**
     * Export listener records to CSV format.
     */
    public function export(Request $request)
    {
        $filename = "listeners_export_" . date('Y-m-d_His') . ".csv";
        $query = User::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $cleanPhone = preg_replace('/[^0-9+]/', '', $search);

            $query->where(function ($q) use ($search, $cleanPhone) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");

                if (strlen($cleanPhone) >= 3) {
                    $q->orWhere('phone', 'like', "%{$cleanPhone}%");
                }
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'disabled') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Name', 'Phone Number', 'City', 'Status', 'Registered Date', 'Last Activity'];

        $callback = function () use ($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->phone,
                    $user->city ?? 'N/A',
                    $user->is_active ? 'Active' : 'Disabled',
                    $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : 'N/A',
                    $user->updated_at ? $user->updated_at->format('Y-m-d H:i:s') : 'N/A',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
