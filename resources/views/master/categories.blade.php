@extends('layouts.app')

@section('title', 'Therapeutic Categories')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-tags text-emerald-600"></i>
                <span>Therapeutic Categories</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Classify medications by pharmacological category (Antibiotics, Analgesics, Antihistamines, etc.).</p>
        </div>

        <a href="{{ route('master.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Drug Catalog
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Add Category Form (Col 4) -->
        <div class="lg:col-span-4 bg-white rounded-3xl p-5 border border-slate-200/90 shadow-sm h-fit">
            <h3 class="font-display font-bold text-sm text-slate-900 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-emerald-600"></i>
                <span>Add Category</span>
            </h3>

            <form method="POST" action="{{ route('master.categories.store') }}" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Category Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Antihypertensives" class="w-full px-3 py-2 border rounded-xl">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Code / Abbreviation</label>
                    <input type="text" name="code" placeholder="e.g. HYPR" class="w-full px-3 py-2 border rounded-xl font-mono">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="3" placeholder="Category notes..." class="w-full px-3 py-2 border rounded-xl"></textarea>
                </div>
                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm transition-colors">
                    Save Category
                </button>
            </form>
        </div>

        <!-- Categories List (Col 8) -->
        <div class="lg:col-span-8 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-slate-50 font-bold text-xs uppercase tracking-wider text-slate-600">
                Registered Categories ({{ $categories->count() }})
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($categories as $cat)
                <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-sm text-slate-900">{{ $cat->name }}</h4>
                            @if($cat->code)
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">{{ $cat->code }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $cat->description ?? 'No description provided.' }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                            {{ $cat->products_count }} products
                        </span>
                        <a href="{{ route('master.index', ['category_id' => $cat->id]) }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">
                            View Drugs &rarr;
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

</div>
@endsection
