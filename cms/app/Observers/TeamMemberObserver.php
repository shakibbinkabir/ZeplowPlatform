<?php

namespace App\Observers;

use App\Models\TeamMember;
use App\Services\SyncService;
use Illuminate\Support\Str;

class TeamMemberObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(TeamMember $member): void
    {
        $this->syncService->syncContent(
            siteKey: $member->site->key,
            contentType: 'team_member',
            slug: Str::slug($member->name . '-' . $member->id),
            data: [
                'name' => $member->name,
                'role' => $member->role,
                'bio' => $member->bio,
                'photo' => $member->getFirstMediaUrl('photo'),
                'linkedin' => $member->linkedin,
                'email' => $member->email,
                'is_founder' => $member->is_founder,
                'sort_order' => $member->sort_order,
            ],
            publishedAt: now()->toISOString(),
        );
    }

    public function deleted(TeamMember $member): void
    {
        $this->syncService->deleteContent(
            siteKey: $member->site->key,
            contentType: 'team_member',
            slug: Str::slug($member->name . '-' . $member->id),
        );
    }
}
