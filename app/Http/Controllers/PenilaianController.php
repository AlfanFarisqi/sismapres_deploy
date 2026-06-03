<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Kriteria;
use App\Models\Penilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenilaianController extends Controller
{
    use \App\Traits\TopsisCalculator;

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Mahasiswa::with('berkas')->where('status_berkas', 'lolos');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('npm', 'like', "%{$search}%");
            });
        }

        if ($status === 'sudah') {
            $query->where('is_dinilai', true);
        } elseif ($status === 'belum') {
            $query->where('is_dinilai', false);
        }

        $mahasiswas = $query->get();
        $kriterias = Kriteria::all();
        
        // Ambil data penilaian yang dikelompokkan berdasarkan mahasiswa
        $penilaians = Penilaian::with(['mahasiswa', 'kriteria'])->get()->groupBy('mahasiswa_id');
        $detailPenilaians = \App\Models\DetailPenilaian::with(['mahasiswa', 'kriteria'])->get()->groupBy('mahasiswa_id');

        return view('admin.data-penilaian.input', compact('mahasiswas', 'kriterias', 'penilaians', 'detailPenilaians', 'search', 'status'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'nilai' => 'required|array',
        ]);

        $mahasiswa = Mahasiswa::findOrFail($request->mahasiswa_id);
        if ($mahasiswa->status_berkas !== 'lolos') {
            return redirect()->back()->with('error', 'Penilaian gagal: Mahasiswa ini belum lolos verifikasi administrasi/berkas.');
        }

        try {
            DB::beginTransaction();

            foreach ($request->nilai as $kriteria_id => $skor) {
                Penilaian::updateOrCreate(
                    [
                        'mahasiswa_id' => $request->mahasiswa_id,
                        'kriteria_id' => $kriteria_id,
                    ],
                    [
                        'nilai' => $skor,
                    ]
                );
            }
            
            // Tandai bahwa admin sudah memvalidasi/mengisi nilai
            $mahasiswa->update(['is_dinilai' => true]);

            // Jalankan perhitungan TOPSIS secara otomatis
            $this->calculateTopsis();

            DB::commit();
            return redirect()->back()->with('success', 'Penilaian berhasil disimpan dan peringkat telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
