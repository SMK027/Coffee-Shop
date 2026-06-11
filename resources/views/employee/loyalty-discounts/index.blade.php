<x-employee-layout title="Reductions fidelite">
    <x-slot name="headerActions">
        <a href="{{ route('employee.loyalty-discounts.create') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            Nouvelle reduction
        </a>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-50 text-stone-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Reduction</th>
                        <th class="px-4 py-3 text-left font-medium">Cout</th>
                        <th class="px-4 py-3 text-left font-medium">Valeur</th>
                        <th class="px-4 py-3 text-left font-medium">Disponibilite</th>
                        <th class="px-4 py-3 text-left font-medium">Statut</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($discounts as $discount)
                        @php
                            $isValid = $discount->isValidForUse();
                            $isSoldOut = $discount->isSoldOut();
                        @endphp
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-stone-800">{{ $discount->name }}</p>
                                @if($discount->description)
                                    <p class="text-xs text-stone-500 mt-1">{{ $discount->description }}</p>
                                @endif
                                @if($discount->employee_only)
                                    <span class="inline-flex mt-2 px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-xs">Reservee salaries</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-stone-700">{{ $discount->points_cost }} pts</td>
                            <td class="px-4 py-3 align-top text-stone-700">{{ $discount->display_value }}</td>
                            <td class="px-4 py-3 align-top text-stone-600">
                                @if($discount->is_permanent)
                                    Permanente
                                @else
                                    {{ optional($discount->starts_at)->format('d/m/Y H:i') ?? 'Maintenant' }}
                                    <br>
                                    {{ optional($discount->ends_at)->format('d/m/Y H:i') ?? 'Sans fin' }}
                                @endif
                                <div class="mt-1 text-xs">
                                    @if($discount->quantity_limit)
                                        Stock: {{ $discount->remaining_quantity }} / {{ $discount->quantity_limit }}
                                    @else
                                        Stock: illimite
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if(!$discount->is_active)
                                    <span class="inline-flex px-2 py-0.5 rounded bg-stone-200 text-stone-700 text-xs">Desactivee</span>
                                @elseif($isSoldOut)
                                    <span class="inline-flex px-2 py-0.5 rounded bg-red-100 text-red-700 text-xs">Soldee</span>
                                @elseif($isValid)
                                    <span class="inline-flex px-2 py-0.5 rounded bg-green-100 text-green-700 text-xs">Active</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded bg-amber-100 text-amber-700 text-xs">Hors plage</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-right">
                                <a href="{{ route('employee.loyalty-discounts.edit', $discount) }}" class="text-amber-700 hover:text-amber-600 font-medium">Modifier</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-stone-500">Aucune reduction configuree.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $discounts->links() }}</div>
</x-employee-layout>
