<?php

namespace App\Http\Controllers;

use App\Models\GlTextReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TextReferenceController extends Controller
{
    /**
     * GET /text-references
     */
    public function index(Request $request)
    {
        $query = GlTextReference::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                  ->orWhere('cost_center', 'like', "%{$search}%")
                  ->orWhere('text_value', 'like', "%{$search}%");
            });
        }

        if ($request->filled('account')) {
            $query->where('account_number', $request->account);
        }

        $references = $query->orderByDesc('use_count')->orderBy('account_number')->paginate(30)->withQueryString();

        $stats = [
            'total' => GlTextReference::count(),
            'most_used' => GlTextReference::orderByDesc('use_count')->limit(1)->first(),
        ];

        return view('text_references.index', [
            'references' => $references,
            'filters' => $request->only(['search', 'account']),
            'stats' => $stats,
        ]);
    }

    /**
     * GET /text-references/create
     */
    public function create()
    {
        return view('text_references.create');
    }

    /**
     * POST /text-references
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|string|max:20',
            'cost_center' => 'nullable|string|max:20',
            'text_value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        GlTextReference::learnOrUpdate(
            $request->account_number,
            $request->cost_center ?: null,
            $request->text_value,
            'manual_input_' . now()->format('Y_m_d_H_i_s')
        );

        return redirect()
            ->route('text_references.index')
            ->with('success', 'Text reference berhasil disimpan.');
    }

    /**
     * GET /text-references/{reference}/edit
     */
    public function edit(GlTextReference $reference)
    {
        return view('text_references.edit', ['reference' => $reference]);
    }

    /**
     * PUT /text-references/{reference}
     */
    public function update(Request $request, GlTextReference $reference)
    {
        $validator = Validator::make($request->all(), [
            'text_value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $reference->update([
            'text_value' => $request->text_value,
        ]);

        return redirect()
            ->route('text_references.index')
            ->with('success', 'Text reference berhasil di-update.');
    }

    /**
     * DELETE /text-references/{reference}
     */
    public function destroy(GlTextReference $reference)
    {
        $reference->delete();
        return redirect()
            ->route('text_references.index')
            ->with('success', 'Text reference berhasil dihapus.');
    }
}