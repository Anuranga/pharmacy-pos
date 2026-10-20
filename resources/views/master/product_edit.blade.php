@extends('layouts.app')

@section('title', 'Edit ' . $product->brand_name . ' - Master Catalog')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{
    costPrice: {{ (float) old('unit_cost_price', $product->unit_cost_price) }},
    retailPrice: {{ (float) old('unit_retail_price', $product->unit_retail_price) }},
    get profitMargin() {
        if (!this.costPrice || this.costPrice <= 0 || !this.retailPrice) return 0;
        return (((this.retailPrice - this.costPrice) / this.retailPrice) * 100).toFixed(1);
    },
    get markup() {
        if (!this.costPrice || this.costPrice <= 0 || !this.retailPrice) return 0;
        return (((this.retailPrice - this.costPrice) / this.costPrice) * 100).toFixed(1);
    }
}">
    
    <!-- Top Breadcrumb Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('master.products.show', $product->id) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5 transition-colors">
            <i class="fa-solid fa-arrow-left"></i> Back to {{ $product->brand_name }}
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('master.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">
                Drug Catalog
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-xs font-semibold text-slate-700">Edit Item #{{ $product->id }}</span>
        </div>
    </div>

    <!-- Error Alerts -->
    @if(isset($errors) && $errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs space-y-1">
        <div class="font-bold flex items-center gap-1.5">
            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
            <span>Please resolve the following input errors:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-rose-700">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Main Edit Card -->
    <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200/90 shadow-sm">
        
        <div class="mb-6 pb-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-2">
            <div>
                <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                    <span>Edit Medication Details</span>
                </h1>
                <p class="text-xs text-slate-500 mt-1">Update pharmaceutical composition, therapeutic classification, pricing, barcode, and inventory thresholds.</p>
            </div>
            <div class="shrink-0">
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-xl bg-slate-100 text-slate-700 border border-slate-200">
                    ID: #{{ $product->id }}
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('master.products.update', $product->id) }}" class="space-y-5 text-xs">
            @csrf
            @method('PUT')

            <!-- Row 1: Barcode & Brand Name -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Barcode / SKU Code <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-barcode text-sm"></i>
                        </div>
                        <input type="text" name="barcode_sku" value="{{ old('barcode_sku', $product->barcode_sku) }}" required placeholder="e.g. 890123456789"
                               class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-mono text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Unique identifier for scanner lookup</span>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Brand Name (Commercial Name) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="brand_name" value="{{ old('brand_name', $product->brand_name) }}" required placeholder="e.g. Augmentin 625mg"
                           class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Primary trade name displayed on POS search & thermal receipt</span>
                </div>
            </div>

            <!-- Row 2: Generic Name & Dosage Strength -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Generic Name (Chemical Salt Composition)
                    </label>
                    <input type="text" name="generic_name" value="{{ old('generic_name', $product->generic_name) }}" placeholder="e.g. Amoxicillin + Clavulanic Acid"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Used by pharmacists for generic substitution lookup</span>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Dosage Strength
                    </label>
                    <input type="text" name="dosage_strength" value="{{ old('dosage_strength', $product->dosage_strength) }}" placeholder="e.g. 625 mg, 500mg, 100 mcg"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Active pharmaceutical ingredient concentration</span>
                </div>
            </div>

            <!-- Row 3: Category, Form, UOM -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Therapeutic Category
                    </label>
                    <select name="category_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium">
                        <option value="">-- Unassigned Category --</option>
                        @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ old('category_id', $product->category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Dosage Form <span class="text-rose-500">*</span>
                    </label>
                    <select name="item_type" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium">
                        @php $type = old('item_type', $product->item_type); @endphp
                        <option value="tablet" {{ $type == 'tablet' ? 'selected' : '' }}>Tablet</option>
                        <option value="capsule" {{ $type == 'capsule' ? 'selected' : '' }}>Capsule</option>
                        <option value="syrup" {{ $type == 'syrup' ? 'selected' : '' }}>Syrup / Suspension</option>
                        <option value="injection" {{ $type == 'injection' ? 'selected' : '' }}>Injection / Infusion</option>
                        <option value="inhaler" {{ $type == 'inhaler' ? 'selected' : '' }}>Inhaler / Respule</option>
                        <option value="cream" {{ $type == 'cream' ? 'selected' : '' }}>Cream / Ointment / Gel</option>
                        <option value="drops" {{ $type == 'drops' ? 'selected' : '' }}>Eye / Ear Drops</option>
                        <option value="device" {{ $type == 'device' ? 'selected' : '' }}>Medical Device / Surgical</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Base UOM (Unit of Measure) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="base_uom" value="{{ old('base_uom', $product->base_uom) }}" required placeholder="e.g. Tablet, Bottle, Ampoule"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium">
                </div>
            </div>

            <!-- Row 4: Pricing & Inventory Control Box -->
            <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/90 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
                    <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-coins text-amber-500"></i>
                        <span>Pricing & Stock Level Policy</span>
                    </span>
                    <div class="flex items-center gap-3 text-[11px] font-mono">
                        <span class="text-slate-500">Margin: <strong class="text-emerald-700" x-text="profitMargin + '%'"></strong></span>
                        <span class="text-slate-500">Markup: <strong class="text-teal-700" x-text="markup + '%'"></strong></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Units per Pack <span class="text-rose-500">*</span></label>
                        <input type="number" min="1" name="units_per_pack" value="{{ old('units_per_pack', $product->units_per_pack) }}" required 
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 font-semibold">
                        <span class="text-[9px] text-slate-400 mt-0.5 block">Default pack size</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Reorder Min Level <span class="text-rose-500">*</span></label>
                        <input type="number" min="0" name="reorder_min" value="{{ old('reorder_min', $product->reorder_min) }}" required 
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 font-semibold">
                        <span class="text-[9px] text-slate-400 mt-0.5 block">Trigger low stock alert</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Reorder Max Level <span class="text-rose-500">*</span></label>
                        <input type="number" min="0" name="reorder_max" value="{{ old('reorder_max', $product->reorder_max ?? 100) }}" required 
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 font-semibold">
                        <span class="text-[9px] text-slate-400 mt-0.5 block">Optimal shelf capacity</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Unit Cost Price (Rs.) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_cost_price" 
                               x-model.number="costPrice"
                               value="{{ old('unit_cost_price', $product->unit_cost_price) }}" required 
                               class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-slate-800 font-bold">
                        <span class="text-[9px] text-slate-400 mt-0.5 block">Standard purchase unit cost</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-1">
                    <div class="md:col-start-4">
                        <label class="block font-semibold text-emerald-800 mb-1">Unit Retail Price (Rs.) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_retail_price" 
                               x-model.number="retailPrice"
                               value="{{ old('unit_retail_price', $product->unit_retail_price) }}" required 
                               class="w-full px-2.5 py-1.5 bg-emerald-50 border border-emerald-300 rounded-lg font-mono font-black text-emerald-800 text-sm focus:bg-white">
                        <span class="text-[9px] text-emerald-600 mt-0.5 block">Customer shelf selling price</span>
                    </div>
                </div>
            </div>

            <!-- Row 5: Regulatory Compliance Flags -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-3 bg-slate-50/60 rounded-2xl border border-slate-200">
                <label class="flex items-start space-x-2.5 cursor-pointer p-1">
                    <input type="checkbox" name="requires_prescription" value="1" 
                           {{ old('requires_prescription', $product->requires_prescription) ? 'checked' : '' }} 
                           class="rounded text-emerald-600 mt-0.5 focus:ring-emerald-500">
                    <div>
                        <span class="font-bold text-slate-800 block">Requires Doctor's Prescription (Rx)</span>
                        <span class="text-[10px] text-slate-500">Forces cashier to record doctor and patient details at checkout</span>
                    </div>
                </label>

                <label class="flex items-start space-x-2.5 cursor-pointer p-1">
                    <input type="checkbox" name="is_controlled" value="1" 
                           {{ old('is_controlled', $product->is_controlled) ? 'checked' : '' }} 
                           class="rounded text-purple-600 mt-0.5 focus:ring-purple-500">
                    <div>
                        <span class="font-bold text-purple-900 block">Controlled Dangerous Drug (Form D / Narcotic)</span>
                        <span class="text-[10px] text-slate-500">Requires strict audit ledger and special storage compliance</span>
                    </div>
                </label>
            </div>

            <!-- Actions Footer -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <a href="{{ route('master.products.show', $product->id) }}" class="px-4 py-2 border border-slate-300 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold transition-colors">
                    Cancel & Return
                </a>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>
        </form>

    </div>

</div>
@endsection
