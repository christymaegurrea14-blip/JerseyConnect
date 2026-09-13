<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageStoreRequest;
use App\Http\Resources\MessageThreadResource;
use App\Models\MessageThread;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminMessageController extends Controller
{
    public function index(Request $request)
    {
        $threads = MessageThread::with([
                'designRequest.user.userInfo',
                'designRequest.order',
                'designRequest.players',
                'messages.user.userInfo',
            ])
            ->get();

        return Inertia::render('Admin/Messages', [
            'threads' => MessageThreadResource::collection($threads),
            'design_request_id' => $request->query('design_request_id'),
        ]);
    }

    public function reply(MessageStoreRequest $request, MessageThread $thread)
    {
        $path = $request->hasFile('image')
            ? $request->file('image')->store('messages', 'public')
            : null;

        $thread->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->input('body'),
            'attachment_path' => $path,
        ]);

        $thread->touch();
        $thread->update(['admin_last_read_at' => now()]);

        return back();
    }

    public function markRead(MessageThread $thread)
    {
        $thread->update(['admin_last_read_at' => now()]);

        return back();
    }
}
