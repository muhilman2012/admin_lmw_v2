<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\KmsArticle;
use App\Models\User;

class KmsController extends Controller
{
    /**
     * Terapkan middleware untuk membatasi akses CRUD.
     */
    protected $middleware = [
        'role:superadmin|admin' => ['except' => ['index', 'show']],
    ];

    /**
     * Menampilkan halaman indeks KMS dengan fungsi pencarian.
     */
    public function index(Request $request)
    {
        $search = $request->input('q');
        $tag = $request->input('tag');
        $user = Auth::user();
        
        $query = KmsArticle::query();

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            $query->where('is_active', true);
        }
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if ($tag) {
            $query->where('tags', 'like', "%{$tag}%"); 
        }

        $articles = $query->latest()->paginate(10);
        
        $popularTags = DB::table('kms_articles')
            ->select(DB::raw('TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(tags, ",", numbers.n), ",", -1)) AS tag'))
            ->from(DB::raw('kms_articles JOIN (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5) numbers ON CHAR_LENGTH(tags) - CHAR_LENGTH(REPLACE(tags, ",", "")) >= numbers.n - 1'))
            ->where(function ($q) use ($user) {
                if (!$user->hasAnyRole(['superadmin', 'admin'])) {
                    $q->where('is_active', true);
                }
            })
            ->whereNotNull('tags')
            ->groupBy('tag')
            ->orderByRaw('COUNT(*) DESC')
            ->havingRaw('LENGTH(tag) > 0')
            ->limit(10)
            ->pluck('tag');


        return view('pages.kms.index', compact('articles', 'search', 'tag', 'popularTags'));
    }

    /**
     * Menampilkan form untuk membuat artikel baru.
     */
    public function create()
    {
        // Middleware sudah membatasi akses
        $article = new KmsArticle();
        $categories = ['Kebijakan', 'Prosedur', 'FAQ', 'Teknis']; // Daftar kategori KMS
        return view('pages.kms.create_edit', compact('article', 'categories'));
    }

    /**
     * Menyimpan artikel KMS baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:100',
            'tags' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            KmsArticle::create([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'category' => $validated['category'],
                'tags' => $validated['tags'] ? Str::lower($validated['tags']) : null, // Simpan tags lowercase
                'is_active' => $request->has('is_active'),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('kms.index')->with('success', 'Artikel KMS berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan artikel KMS: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan artikel KMS. Cek log server.');
        }
    }

    /**
     * Menampilkan detail satu artikel KMS.
     */
    public function show(KmsArticle $article)
    {
        if (!$article->is_active && !Auth::user()->hasAnyRole(['superadmin', 'admin'])) {
            abort(404); // Sembunyikan artikel tidak aktif dari publik
        }
        return view('pages.kms.show', compact('article'));
    }

    /**
     * Menampilkan form untuk mengedit artikel.
     */
    public function edit(KmsArticle $article)
    {
        $categories = ['Kebijakan', 'Prosedur', 'FAQ', 'Teknis']; 
        return view('pages.kms.create_edit', compact('article', 'categories'));
    }

    /**
     * Memperbarui artikel KMS yang ada.
     */
    public function update(Request $request, KmsArticle $article)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:100',
            'tags' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);
        
        try {
            $article->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'category' => $validated['category'],
                'tags' => $validated['tags'] ? Str::lower($validated['tags']) : null,
                'is_active' => $request->has('is_active'),
                // user_id TIDAK diupdate, karena user_id adalah pembuatnya
            ]);

            return redirect()->route('kms.show', $article)->with('success', 'Artikel KMS berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui artikel KMS: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memperbarui artikel KMS. Cek log server.');
        }
    }

    /**
     * Menghapus artikel KMS.
     */
    public function destroy(KmsArticle $article)
    {
        try {
            $article->delete();
            return redirect()->route('kms.index')->with('success', 'Artikel KMS berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus artikel KMS: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus artikel KMS.');
        }
    }
}
