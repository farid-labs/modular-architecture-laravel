<?php

namespace Modules\Workspace\Infrastructure\Persistence\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model for workspace_members table.
 *
 * @property int $workspace_id
 * @property int $user_id
 * @property string $role
 * @property CarbonInterface|null $joined_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class WorkspaceMemberPivot extends Pivot
{
    /**
     * The table associated with the pivot model.
     */
    protected $table = 'workspace_members';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
        'joined_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
