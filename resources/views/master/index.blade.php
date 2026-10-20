@extends('layouts.app')

@section('title', 'Master Drug Catalog')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ adjustModal: false, selectedBatchId: '', selectedProductName: '', currentQty: 0 }">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-boxes-stacked text-emerald-600"></i>
                <span>Master Drug Directory & Catalog</span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">Manage all pharmaceuticals, dosage forms, SKU barcodes, and live inventory balances.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('master.categories') }}" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-semibold rounded-xl text-xs shadow-xs transition-colors flex items-center gap-2">
                <i class="fa-solid fa-tags text-slate-500"></i>
                <span>Categories</span>
            </a>
            <a href="{{ route('master.products.create') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Add New Medication</span>
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Indexed Drugs</span>
                <div class="text-2xl font-extrabold font-display text-slate-900 mt-1">{{ number_format($totalProducts) }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-pills"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Inventory Batches</span>
                <div class="text-2xl font-extrabold font-display text-teal-600 mt-1">{{ number_format($totalBatches) }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-cubes-stacked"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Therapeutic Categories</span>
                <div class="text-2xl font-extrabold font-display text-sky-600 mt-1">{{ $categories->count() }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-folder-tree"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Inventory Health</span>
                <div class="text-sm font-bold text-emerald-600 mt-1 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> FEFO Engine Active
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('master.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs">
            
            <div class="md:col-span-4 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Brand Name, Generic Composition, or SKU..."
                       class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="md:col-span-3">
                <select name="category_id" class="w-full py-2 px-3 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">-- All Categories --</option>
                    @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <select name="item_type" class="w-full py-2 px-3 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">-- All Dosage Forms --</option>
                    <option value="tablet" {{ request('item_type') == 'tablet' ? 'selected' : '' }}>Tablet</option>
                    <option value="capsule" {{ request('item_type') == 'capsule' ? 'selected' : '' }}>Capsule</option>
                    <option value="syrup" {{ request('item_type') == 'syrup' ? 'selected' : '' }}>Syrup</option>
                    <option value="injection" {{ request('item_type') == 'injection' ? 'selected' : '' }}>Injection</option>
                    <option value="inhaler" {{ request('item_type') == 'inhaler' ? 'selected' : '' }}>Inhaler</option>
                    <option value="cream" {{ request('item_type') == 'cream' ? 'selected' : '' }}>Cream</option>
                </select>
            </div>

            <div class="md:col-span-2 flex items-center">
                <label class="flex items-center space-x-2 text-slate-600 font-medium cursor-pointer">
                    <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} class="rounded text-emerald-600">
                    <span>Low Stock Only</span>
                </label>
            </div>

            <div class="md:col-span-1 flex gap-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-xs transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">SKU / Barcode</th>
                        <th class="py-3.5 px-4">Medication & Composition</th>
                        <th class="py-3.5 px-4">Dosage / Form</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4 text-right">Cost (Rs.)</th>
                        <th class="py-3.5 px-4 text-right">Retail (Rs.)</th>
                        <th class="py-3.5 px-4 text-center">Live Stock</th>
                        <th class="py-3.5 px-4 text-center">Flags</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($products as $prod)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-mono text-slate-500">
                            {{ $prod->barcode_sku }}
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('master.products.show', $prod->id) }}" class="font-bold text-slate-900 hover:text-emerald-600 text-sm block">
                                {{ $prod->brand_name }}
                            </a>
                            <span class="text-[11px] text-slate-500 italic block">{{ $prod->generic_name ?? '—' }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-semibold text-slate-800">{{ $prod->dosage_strength ?? '—' }}</span>
                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono block w-fit mt-0.5">{{ ucfirst($prod->item_type) }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ $prod->category->name ?? 'General' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600">
                            {{ number_format($prod->unit_cost_price, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                            {{ number_format($prod->unit_retail_price, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @php $stock = $prod->total_stock; @endphp
                            <span class="font-bold font-mono px-2 py-1 rounded-lg {{ $stock <= $prod->reorder_min ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800' }}">
                                {{ $stock }} {{ $prod->base_uom }}
                            </span>
                            @if($stock <= $prod->reorder_min)
                            <span class="block text-[9px] text-rose-600 font-bold uppercase mt-0.5">Reorder Min ({{ $prod->reorder_min }})</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                @if($prod->requires_prescription)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-700 border border-rose-200 uppercase">Rx</span>
                                @endif
                                @if($prod->is_controlled)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 text-purple-700 border border-purple-200 uppercase">Controlled</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('master.products.edit', $prod->id) }}" class="p-1.5 hover:bg-emerald-50 rounded-lg text-emerald-600 hover:text-emerald-700 transition-colors" title="Edit Drug Details">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </a>
                                <a href="{{ route('master.products.show', $prod->id) }}" class="p-1.5 hover:bg-slate-200 rounded-lg text-slate-600" title="View Batches & Stock Details">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-prescription-bottle text-4xl mb-2 text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-600">No drugs found in catalog</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $products->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
