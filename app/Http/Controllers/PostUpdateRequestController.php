<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostUpdateRequest;
use Illuminate\Http\Request;

class PostUpdateRequestController extends Controller
{
    private function roles(Request $request)
    {
        return $request->get('keycloak_user')->realm_access->roles ?? [];
    }

    /* Moderator creates update request */
    public function store(Request $request, $postId)
    {
        if (!in_array('moderator', $this->roles($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $post = Post::findOrFail($postId);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string'
        ]);

        $req = PostUpdateRequest::create([
            'post_id' => $post->id,
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'requested_by' => $request->get('keycloak_user')->name,
        ]);

        return response()->json($req, 201);
    }

    /* Editor/Admin view requests */
    public function index(Request $request)
    {
        if (!array_intersect(['editor', 'admin'], $this->roles($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return PostUpdateRequest::where('status', 'pending')->get();
    }

    /* Approve request */
    public function approve(Request $request, $id)
    {
        if (!array_intersect(['editor', 'admin'], $this->roles($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $req = PostUpdateRequest::findOrFail($id);
        $post = $req->post;

        $post->update(array_filter([
            'title' => $req->title,
            'content' => $req->content,
        ]));

        $req->update(['status' => 'approved']);

        return response()->json(['message' => 'Approved']);
    }

    /* Reject request */
    public function reject(Request $request, $id)
    {
        if (!array_intersect(['editor', 'admin'], $this->roles($request))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        PostUpdateRequest::findOrFail($id)->update([
            'status' => 'rejected'
        ]);

        return response()->json(['message' => 'Rejected']);
    }
}
