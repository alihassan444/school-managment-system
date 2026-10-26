<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FeeCategory;
use App\Models\SiblingDiscountRule;
use Illuminate\Http\Request;

class SiblingDiscountController extends Controller
{
    public function index()
    {
        return view('family-fees.discounts', [
            'rules' => SiblingDiscountRule::with('feeCategory')->orderByDesc('priority')->orderBy('id')->get(),
            'categories' => FeeCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'discount_type' => ['required', 'in:fixed,percentage,sibling_order,manual'],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'minimum_sibling_order' => ['required', 'integer', 'min:1', 'max:20'],
            'fee_category_id' => ['nullable', 'exists:fee_categories,id'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        if (in_array($validated['discount_type'], ['percentage', 'sibling_order'], true) && (float) $validated['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage discounts cannot exceed 100%.'])->withInput();
        }
        $rule = SiblingDiscountRule::create($validated + ['is_active' => true]);
        AuditLog::create([
            'user_id' => $request->user()?->id, 'action' => 'sibling-discount-created',
            'auditable_type' => $rule::class, 'auditable_id' => $rule->id,
            'new_values' => $rule->toArray(), 'ip_address' => $request->ip(),
        ]);
        return back()->with('success', 'Sibling discount rule created. It will only apply to new invoices.');
    }

    public function toggle(Request $request, SiblingDiscountRule $rule)
    {
        $old = $rule->is_active;
        $rule->update(['is_active' => ! $old]);
        AuditLog::create([
            'user_id' => $request->user()?->id, 'action' => 'sibling-discount-toggled',
            'auditable_type' => $rule::class, 'auditable_id' => $rule->id,
            'old_values' => ['is_active' => $old], 'new_values' => ['is_active' => $rule->is_active],
            'ip_address' => $request->ip(),
        ]);
        return back()->with('success', 'Discount rule status updated. Existing invoices were not changed.');
    }
}
