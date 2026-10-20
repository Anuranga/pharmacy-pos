@extends('layouts.app')

@section('title', 'Add New Medication - Master Catalog')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <a href="{{ route('master.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Drug Catalog
        </a>
    </div>

    <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200/90 shadow-sm">
        
        <div class="mb-6 pb-4 border-b border-slate-100">
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-emerald-600"></i>
                <span>Register New Medication into Master Catalog</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Specify scientific composition, therapeutic classification, barcode SKU, and reorder triggers.</p>
        </div>

        <form method="POST" action="{{ route('master.products.store') }}" class="space-y-4 text-xs">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Barcode / SKU Code *</label>
                    <input type="text" name="barcode_sku" value="{{ old('barcode_sku') }}" required placeholder="e.g. 890123456789"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Brand Name (Commercial Name) *</label>
                    <input type="text" name="brand_name" value="{{ old('brand_name') }}" required placeholder="e.g. Augmentin 625mg"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Generic Name (Chemical Salt Composition)</label>
                    <input type="text" name="generic_name" value="{{ old('generic_name') }}" placeholder="e.g. Amoxicillin + Clavulanic Acid"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Dosage Strength</label>
                    <input type="text" name="dosage_strength" value="{{ old('dosage_strength') }}" placeholder="e.g. 625 mg or 100 mcg/dose"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Therapeutic Category</label>
                    <select name="category_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Item Dosage Form *</label>
                    <select name="item_type" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="tablet">Tablet</option>
                        <option value="capsule">Capsule</option>
                        <option value="syrup">Syrup</option>
                        <option value="injection">Injection</option>
                        <option value="inhaler">Inhaler</option>
                        <option value="cream">Cream / Ointment</option>
                        <option value="drops">Drops</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Base UOM (Unit of Measure) *</label>
                    <input type="text" name="base_uom" value="{{ old('base_uom', 'Tablet') }}" required placeholder="e.g. Tablet, Bottle, Vial"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Units per Pack</label>
                    <input type="number" min="1" name="units_per_pack" value="{{ old('units_per_pack', 1) }}" required class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Reorder Min Level</label>
                    <input type="number" min="0" name="reorder_min" value="{{ old('reorder_min', 10) }}" required class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Unit Cost Price (Rs.) *</label>
                    <input type="number" step="0.01" min="0" name="unit_cost_price" value="{{ old('unit_cost_price', '0.00') }}" required class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg font-mono">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Unit Retail Price (Rs.) *</label>
                    <input type="number" step="0.01" min="0" name="unit_retail_price" value="{{ old('unit_retail_price', '0.00') }}" required class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg font-mono font-bold text-emerald-700">
                </div>
            </div>

            <div class="flex items-center space-x-6 p-2">
                <label class="flex items-center space-x-2 cursor-pointer font-medium text-slate-700">
                    <input type="checkbox" name="requires_prescription" value="1" {{ old('requires_prescription') ? 'checked' : '' }} class="rounded text-emerald-600">
                    <span>Requires Doctor's Prescription (Rx)</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer font-medium text-slate-700">
                    <input type="checkbox" name="is_controlled" value="1" {{ old('is_controlled') ? 'checked' : '' }} class="rounded text-purple-600">
                    <span>Controlled Dangerous Drug (Form D / Narcotic)</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('master.index') }}" class="px-4 py-2 border border-slate-300 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md transition-all">
                    Save Drug to Catalog
                </button>
            </div>
        </form>

    </div>

</div>
@endsection
