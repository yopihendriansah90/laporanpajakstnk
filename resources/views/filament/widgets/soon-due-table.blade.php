<div class="space-y-3">
    <div class="text-sm text-gray-600">
        Menampilkan hingga {{ $limit }} entri jatuh tempo dalam ≤ {{ $days }} hari ke depan.
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 font-semibold text-gray-700">Jenis</th>
                    <th class="px-3 py-2 font-semibold text-gray-700">Identitas</th>
                    <th class="px-3 py-2 font-semibold text-gray-700">Tanggal Jatuh Tempo</th>
                    <th class="px-3 py-2 font-semibold text-gray-700">Sisa Hari</th>
                    <th class="px-3 py-2 font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-t border-gray-100">
                        <td class="px-3 py-2">{{ $row['jenis'] }}</td>
                        <td class="px-3 py-2">{{ $row['identitas'] }}</td>
                        <td class="px-3 py-2">
                            {{ \Illuminate\Support\Carbon::parse($row['due_date'])->format('d M Y') }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $row['days_left'] }} hari
                        </td>
                        <td class="px-3 py-2">
                            <a href="{{ $row['url'] }}" class="text-primary-600 hover:underline">Lihat / Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-3 text-center text-gray-500" colspan="5">
                            Tidak ada data jatuh tempo dalam ≤ {{ $days }} hari.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>