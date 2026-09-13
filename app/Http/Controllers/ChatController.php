<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageStoreRequest;
use App\Http\Resources\MessageThreadResource;
use App\Models\MessageThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $threads = MessageThread::with([
                'designRequest.user.userInfo',
                'designRequest.order',
                'designRequest.players',
                'messages.user.userInfo',
            ])
            ->whereHas('designRequest', fn($q) => $q->where('user_id', $request->user()->id))
            ->get();

        return Inertia::render('Client/Chat', [
            'threads' => MessageThreadResource::collection($threads),
            'design_request_id' => $request->query('design_request_id'),
        ]);
    }


    public function reply(MessageStoreRequest $request, MessageThread $thread)
    {
        abort_unless($thread->designRequest->user_id === $request->user()->id, 403);

        $path = $request->hasFile('image')
            ? $request->file('image')->store('messages', 'public')
            : null;

        $thread->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->input('body'),
            'attachment_path' => $path,
        ]);

        $thread->touch();
        $thread->update(['client_last_read_at' => now()]);

        return back();
    }

    public function markRead(Request $request, MessageThread $thread)
    {
        abort_unless($thread->designRequest->user_id === $request->user()->id, 403);

        $thread->update(['client_last_read_at' => now()]);

        return back();
    }
}
