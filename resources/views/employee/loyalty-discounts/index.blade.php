<x-employee-layout title="Réductions fidélité">
    <x-slot name="headerActions">
        <a href="{{ route('employee.loyalty-discounts.create') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 sm:gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="hidden sm:inline">Nouvelle réduction</span>
            <span class="sm:hidden">Nouveau</span>
        </a>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">

        @if($discounts->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">
                <p>Aucune réduction configurée.</p>
            </div>
        @else

            {{-- Vue desktop --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Réduction</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Coût</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Valeur</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Disponibilité</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Statut</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($discounts as $discount)
                        @php
                            $isValid   = $discount->isValidForUse();
                            $isSoldOut = $discount->isSoldOut();
                        @endphp
                        <tr class="hover:bg-stone-50 transition-colors">
                            <td class="px-5 py-3">
                                <p class="font-medium text-stone-800">{{ $discount->name }}</p>
                                @if($discount->description)
                                    <p class="text-xs text-stone-500 mt-0.5 max-w-xs truncate">{{ $discount->description }}</p>
                                @endif
                                @if($discount->employee_only)
                                    <span class="inline-flex mt-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">Salariés</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-stone-700 whitespace-nowrap">{{ $discount->points_cost }} pts</td>
                            <td class="px-5 py-3 text-stone-700 whitespace-nowrap">{{ $discount->display_value }}</td>
                            <td class="px-5 py-3 text-stone-600 text-xs leading-relaxed">
                                @if($discount->is_permanent)
                                    <span class="text-stone-500">Permanente</span>
                                @else
                                    <span>{{ optional($discount->starts_at)->format('d/m/Y H:i') ?? '—' }}</span>
                                    <br>
                                    <span>→ {{ optional($discount->ends_at)->format('d/m/Y H:i') ?? 'Sans fin' }}</span>
                                @endif
                                <p class="mt-0.5 text-stone-400">
                                    @if($discount->quantity_limit)
                                        {{ $discount->remaining_quantity }} / {{ $discount->quantity_limit }} restant(s)
                                    @else
                                        Stock illimité
                                    @endif
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                @if(!$discount->is_active)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-600">Désactivée</span>
                                @elseif($isSoldOut)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Soldée</span>
                                @elseif($isValid)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Hors plage</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('employee.loyalty-discounts.edit', $discount) }}" class="text-amber-700 hover:text-amber-600 font-medium text-xs">Modifier</a>
                                    <form action="{{ route('employee.loyalty-discounts.destroy', $discount) }}" method="POST"
                                          onsubmit="return confirm('Supprimer la réduction \u00ab {{ addslashes($discount->name) }} \u00bb ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-medium text-xs transition-colors">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vue mobile --}}
            <div class="sm:hidden divide-y divide-stone-100">
                @foreach($discounts as $discount)
                @php
                    $isValid   = $discount->isValidForUse();
                    $isSoldOut = $discount->isSoldOut();
                @endphp
                <div class="px-4 py-3.5 hover:bg-stone-50 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <a href="{{ route('employee.loyalty-discounts.edit', $discount) }}" class="flex-1 min-w-0 block">
                        <div class="min-w-0">
                            <p class="font-medium text-stone-800 text-sm truncate">{{ $discount->name }}</p>
                            <p class="text-xs text-stone-500 mt-0.5">{{ $discount->points_cost }} pts → {{ $discount->display_value }}</p>
                            @if($discount->employee_only)
                                <span class="inline-flex mt-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">Salariés</span>
                            @endif
                        </div>
                        </a>
                        <div class="flex-shrink-0 flex flex-col items-end gap-2">
                            @if(!$discount->is_active)
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-600">Désactivée</span>
                            @elseif($isSoldOut)
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Soldée</span>
                            @elseif($isValid)
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Hors plage</span>
                            @endif
                            <form action="{{ route('employee.loyalty-discounts.destroy', $discount) }}" method="POST"
                                  onsubmit="return confirm('Supprimer cette réduction ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if($discounts->hasPages())
                <div class="px-5 py-4 border-t border-stone-100">
                    {{ $discounts->links() }}
                </div>
            @endif

        @endif
    </div>
</x-employee-layout>

