<?php

namespace App\Http\Controllers;

use App\Models\GlAccountMapping;
use App\Models\GlEntity;
use App\Models\GlMappingProfile;
use App\Models\GlStrategyDConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MappingController extends Controller
{
    /**
     * GET /mapping - tampilkan list semua entity + profile
     */
    public function index()
    {
        $entities = GlEntity::active()
            ->with(['mappingProfiles' => function ($q) {
                $q->withCount('accountMappings')->orderByDesc('is_default')->orderBy('name');
            }])
            ->orderBy('region')
            ->orderBy('name')
            ->get();

        return view('mapping.index', [
            'entitiesByRegion' => $entities->groupBy('region'),
        ]);
    }

    /**
     * GET /mapping/profile/{profile} - detail profile + list mapping
     */
    public function showProfile(GlMappingProfile $profile)
    {
        $profile->load(['entity', 'accountMappings' => fn($q) => $q->orderBy('order_index'), 'strategyDConfig']);
        return view('mapping.profile', ['profile' => $profile]);
    }

    /**
     * GET /mapping/profile/{profile}/duplicate - form untuk duplicate profile
     */
    public function duplicateForm(GlMappingProfile $profile)
    {
        $profile->load('entity');
        return view('mapping.duplicate', ['profile' => $profile]);
    }

    /**
     * POST /mapping/profile/{profile}/duplicate - eksekusi duplicate
     */
    public function duplicate(Request $request, GlMappingProfile $profile)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $profile->load('accountMappings', 'strategyDConfig');

        // Return profile dari transaction (clean & no warning)
        $newProfile = DB::transaction(function () use ($request, $profile) {
            $created = GlMappingProfile::create([
                'entity_id' => $profile->entity_id,
                'name' => $request->name,
                'is_default' => false,
                'description' => $request->description ?: "Duplicated from {$profile->name}",
                'created_by' => $request->user()?->name ?? 'system',
            ]);

            // Copy semua account mappings
            foreach ($profile->accountMappings as $m) {
                GlAccountMapping::create([
                    'profile_id' => $created->id,
                    'mapping_key' => $m->mapping_key,
                    'account_number' => $m->account_number,
                    'account_name' => $m->account_name,
                    'account_type' => $m->account_type,
                    'transaction_value' => $m->transaction_value,
                    'cost_center' => $m->cost_center,
                    'profit_center' => $m->profit_center,
                    'use_profit_center' => $m->use_profit_center,
                    'components' => $m->components,
                    'match_account_name' => $m->match_account_name,
                    'match_keywords' => $m->match_keywords,
                    'order_index' => $m->order_index,
                    'is_active' => $m->is_active,
                ]);
            }

            // Copy strategy D config jika ada
            if ($profile->strategyDConfig) {
                GlStrategyDConfig::create([
                    'profile_id' => $created->id,
                    'debit_accounts' => $profile->strategyDConfig->debit_accounts,
                    'debit_keywords' => $profile->strategyDConfig->debit_keywords,
                    'default_dc' => $profile->strategyDConfig->default_dc,
                ]);
            }

            return $created;
        });

        return redirect()
            ->route('mapping.profile', $newProfile->id)
            ->with('success', "Profile '{$newProfile->name}' berhasil dibuat.");
    }

    /**
     * GET /mapping/{mapping}/edit - form edit mapping row
     */
    public function editMapping(GlAccountMapping $mapping)
    {
        // Eager load profile + entity untuk safe access
        $mapping->load('profile.entity');

        // Defensive: pastikan profile ke-load
        $profile = $mapping->profile;
        if ($profile === null) {
            return redirect()->route('mapping.index')->with('error', 'Profile tidak ditemukan untuk mapping ini.');
        }

        return view('mapping.edit_row', ['mapping' => $mapping]);
    }

    /**
     * PUT /mapping/{mapping} - update mapping row
     */
    public function updateMapping(Request $request, GlAccountMapping $mapping)
    {
        // Eager load + defensive null check
        $mapping->load('profile');
        $profile = $mapping->profile;

        if ($profile === null) {
            return back()->with('error', 'Profile tidak ditemukan untuk mapping ini.');
        }

        // Cek apakah profile-nya default (default profile read-only via UI)
        if ($profile->is_default) {
            return back()->with('error', 'Profile Default tidak bisa di-edit langsung. Silakan duplicate dulu menjadi profile custom.');
        }

        $validator = Validator::make($request->all(), [
            'mapping_key' => 'required|string|max:100',
            'account_number' => 'required|string|max:20',
            'account_name' => 'required|string|max:200',
            'account_type' => 'required|in:Cost center,Aggregate,Individual',
            'transaction_value' => 'required|in:Debit,Credit',
            'cost_center' => 'nullable|string|max:20',
            'profit_center' => 'nullable|string|max:20',
            'use_profit_center' => 'nullable|boolean',
            'components' => 'nullable|string',
            'match_account_name' => 'nullable|string|max:200',
            'match_keywords' => 'nullable|string',
            'order_index' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $mapping->update([
            'mapping_key' => $request->mapping_key,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'transaction_value' => $request->transaction_value,
            'cost_center' => $request->cost_center,
            'profit_center' => $request->profit_center,
            'use_profit_center' => (bool) $request->use_profit_center,
            'components' => $this->parseLinesToArray($request->components),
            'match_account_name' => $request->match_account_name,
            'match_keywords' => $this->parseLinesToArray($request->match_keywords),
            'order_index' => (int) $request->order_index,
            'is_active' => (bool) $request->is_active,
        ]);

        return redirect()
            ->route('mapping.profile', $mapping->profile_id)
            ->with('success', "Mapping '{$mapping->mapping_key}' berhasil di-update.");
    }

    /**
     * GET /mapping/profile/{profile}/add - form tambah mapping row
     */
    public function createMapping(GlMappingProfile $profile)
    {
        if ($profile->is_default) {
            return redirect()
                ->route('mapping.profile', $profile->id)
                ->with('error', 'Profile Default tidak bisa di-edit. Duplicate dulu menjadi profile custom.');
        }

        $profile->load('entity');
        return view('mapping.add_row', ['profile' => $profile]);
    }

    /**
     * POST /mapping/profile/{profile}/add - simpan mapping baru
     */
    public function storeMapping(Request $request, GlMappingProfile $profile)
    {
        if ($profile->is_default) {
            return back()->with('error', 'Profile Default tidak bisa di-edit.');
        }

        $validator = Validator::make($request->all(), [
            'mapping_key' => 'required|string|max:100',
            'account_number' => 'required|string|max:20',
            'account_name' => 'required|string|max:200',
            'account_type' => 'required|in:Cost center,Aggregate,Individual',
            'transaction_value' => 'required|in:Debit,Credit',
            'cost_center' => 'nullable|string|max:20',
            'profit_center' => 'nullable|string|max:20',
            'use_profit_center' => 'nullable|boolean',
            'components' => 'nullable|string',
            'match_account_name' => 'nullable|string|max:200',
            'match_keywords' => 'nullable|string',
            'order_index' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $orderIndex = $request->order_index ?: ($profile->accountMappings()->max('order_index') + 1);

        GlAccountMapping::create([
            'profile_id' => $profile->id,
            'mapping_key' => $request->mapping_key,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'transaction_value' => $request->transaction_value,
            'cost_center' => $request->cost_center,
            'profit_center' => $request->profit_center,
            'use_profit_center' => (bool) $request->use_profit_center,
            'components' => $this->parseLinesToArray($request->components),
            'match_account_name' => $request->match_account_name,
            'match_keywords' => $this->parseLinesToArray($request->match_keywords),
            'order_index' => (int) $orderIndex,
            'is_active' => true,
        ]);

        return redirect()
            ->route('mapping.profile', $profile->id)
            ->with('success', "Mapping baru berhasil ditambahkan.");
    }

    /**
     * DELETE /mapping/{mapping} - hapus mapping row
     */
    public function destroyMapping(GlAccountMapping $mapping)
    {
        $mapping->load('profile');
        $profile = $mapping->profile;

        if ($profile === null) {
            return back()->with('error', 'Profile tidak ditemukan.');
        }

        if ($profile->is_default) {
            return back()->with('error', 'Profile Default tidak bisa di-edit.');
        }

        $profileId = $mapping->profile_id;
        $mapping->delete();

        return redirect()
            ->route('mapping.profile', $profileId)
            ->with('success', 'Mapping berhasil dihapus.');
    }

    /**
     * DELETE /mapping/profile/{profile} - hapus profile (non-default only)
     */
    public function destroyProfile(GlMappingProfile $profile)
    {
        if ($profile->is_default) {
            return back()->with('error', 'Profile Default tidak bisa dihapus.');
        }

        $profileName = $profile->name;
        $profile->delete();

        return redirect()
            ->route('mapping.index')
            ->with('success', "Profile '{$profileName}' berhasil dihapus.");
    }

    /**
     * Helper: convert textarea multi-line ke array
     */
    private function parseLinesToArray(?string $text): ?array
    {
        if (empty(trim($text ?? ''))) return null;
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $items = array_filter(array_map('trim', $lines), fn($l) => $l !== '');
        return array_values($items) ?: null;
    }
}