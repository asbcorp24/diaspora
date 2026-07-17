<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $diasporaId = app('currentDiaspora')->id;

        $newsItems = DB::table('news')
            ->leftJoin('users', 'users.id', '=', 'news.author_user_id')
            ->where('news.diaspora_id', $diasporaId)
            ->where('news.is_published', true)
            ->whereNotNull('news.published_at')
            ->when($request->filled('category'), fn ($query) => $query->where('news.category', $request->input('category')))
            ->select('news.*', 'users.name as author_name')
            ->orderByDesc('news.is_pinned')
            ->latest('news.published_at')
            ->paginate(12)
            ->withQueryString();

        $categories = DB::table('news')
            ->where('diaspora_id', $diasporaId)
            ->where('is_published', true)
            ->distinct()->orderBy('category')->pluck('category');

        return view('news.index', compact('newsItems', 'categories'));
    }

    public function show(string $slug): View
    {
        $diasporaId = app('currentDiaspora')->id;
        $newsItem = DB::table('news')
            ->leftJoin('users', 'users.id', '=', 'news.author_user_id')
            ->where('news.diaspora_id', $diasporaId)
            ->where('news.slug', $slug)
            ->where('news.is_published', true)
            ->select('news.*', 'users.name as author_name')
            ->first();

        abort_unless($newsItem, 404);

        $relatedNews = DB::table('news')
            ->where('diaspora_id', $diasporaId)
            ->where('is_published', true)
            ->where('id', '!=', $newsItem->id)
            ->where('category', $newsItem->category)
            ->latest('published_at')->limit(4)->get();

        return view('news.show', compact('newsItem', 'relatedNews'));
    }
}
