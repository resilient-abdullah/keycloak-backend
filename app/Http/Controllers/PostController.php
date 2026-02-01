<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    private function roles(Request $request)
    {
        return $request->get('keycloak_user')->realm_access->roles ?? [];
    }

    public function index()
    {
        $posts = Post::all();
        return response()->json($posts);
    }

    public function store(Request $request)
    {
        if (!array_intersect(['admin', 'editor'], $this->roles($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = Post::create($validated);

        return response()->json($post, 201);
    }

    public function show($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        return response()->json($post);
    }

    public function update(Request $request, $id)
    {
        if (!in_array('admin', $this->roles($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $post = Post::find($id);

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ]);

        $post->update($validated);

        return response()->json($post);
    }

    public function destroy(Request $request, $id)
    {
        if (!in_array('admin', $this->roles($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $post = Post::find($id);

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        $post->delete();

        return response()->json(null, 204);
    }

}
