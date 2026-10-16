<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:income,expense'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $query = Category::withCount('transactions');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'deductible' => (clone $query)->where('type', 'expense')->where('is_tax_deductible', true)->count(),
            'non_deductible' => (clone $query)->where('type', 'expense')->where('is_tax_deductible', false)->count(),
        ];
        $perPage = (int) ($filters['per_page'] ?? 10);
        $categories = $query->orderBy('code', 'asc')->paginate($perPage)->withQueryString();

        return view('categories.index', compact('categories', 'stats', 'perPage'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code'              => 'required|string|max:50|unique:categories,code',
            'name'              => 'required|string|max:255',
            'type'              => 'required|in:income,expense',
            'is_tax_deductible' => 'required|boolean',
            'description'       => 'nullable|string',
        ]);

        Category::create([
            'code'              => $request->code,
            'name'              => $request->name,
            'type'              => $request->type,
            'is_tax_deductible' => (bool) $request->is_tax_deductible,
            'description'       => $request->description,
        ]);

        return redirect()->route('categories.index')->with('success', 'Akun Kategori berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'code'              => 'required|string|max:50|unique:categories,code,' . $category->id,
            'name'              => 'required|string|max:255',
            'type'              => 'required|in:income,expense',
            'is_tax_deductible' => 'required|boolean',
            'description'       => 'nullable|string',
        ]);

        $category->update([
            'code'              => $request->code,
            'name'              => $request->name,
            'type'              => $request->type,
            'is_tax_deductible' => (bool) $request->is_tax_deductible,
            'description'       => $request->description,
        ]);

        return redirect()->route('categories.index')->with('success', 'Akun Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->transactions()->exists()) {
            return redirect()->route('categories.index')
                ->with('error', "Kategori [{$category->code}] {$category->name} tidak dapat dihapus karena telah digunakan pada {$category->transactions()->count()} transaksi.");
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Akun Kategori berhasil dihapus.');
    }
}
