<div class="space-y-3">
    <div class="text-sm text-gray-600">
        Menampilkan hingga {{ $limit }} pengajuan yang berstatus diajukan (STNK & KIR).
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 font-semibold text-gray-700">Jenis</th>
                    <th class="px-3 py-2 font-semibold text-gray-700">Nomor Dokumen</th>
                    <th class="px-3 py-2 font-semibold text-gray-700">Waktu Diajukan</th>
                    <th class="px-3 py-2 font-semibold text-gray-700">Grand Total</th>
                    <th class="px-3 py-2 font-semibold text-gray-700">Jumlah Item</th>
                    <th class="px-3 py-2 font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $fmtRp = function (?int $v): string {
                        $v = (int)($v ?? 0);
                        return 'Rp ' . number_format($v, 0, ',', '.');
                    };
                @endphp
                @forelse($rows as $row)
                    <tr class="border-t border-gray-100">
                        <td class="px-3 py-2">{{ $row['jenis'] }}</td>
                        <td class="px-3 py-2">{{ $row['nomor'] }}</td>
                        <td class="px-3 py-2">
                            {{ \Illuminate\Support\Carbon::parse($row['submitted_at'])->format('d M Y H:i') }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $fmtRp($row['grand_total']) }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $row['items_count'] }}
                        </td>
                        <td class="px-3 py-2">
                            <a href="{{ $row['url'] }}" class="text-primary-600 hover:underline">Lihat / Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-3 text-center text-gray-500" colspan="6">
                            Tidak ada pengajuan yang menunggu approval.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>