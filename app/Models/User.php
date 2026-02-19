<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'juntify';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'current_organization_id',
        'username',
        'full_name',
        'email',
        'password',
        'roles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // Removed 'password' => 'hashed' to allow manual hash control
    ];

    /**
     * Get DDU membership from juntify_panels database
     */
    public function dduMembership()
    {
        return \App\Models\IntegrantesEmpresa::getDduMembership($this->id);
    }

    /**
     * Check if user is a DDU member.
     */
    public function isDduMember()
    {
        // Get juntify user ID from email since local and juntify IDs may differ
        $juntifyUser = DB::connection('juntify')
            ->table('users')
            ->where('email', $this->email)
            ->first();

        if (!$juntifyUser) {
            return false;
        }

        return \App\Models\IntegrantesEmpresa::isDduMember($juntifyUser->id);
    }

    /**
     * Get user's DDU role.
     */
    public function getDduRole()
    {
        $membership = $this->dduMembership();
        return $membership ? $membership->rol : null;
    }

    /**
     * Google token owned by the user.
     */
    public function googleToken(): HasOne
    {
        return $this->hasOne(GoogleToken::class, 'username', 'username');
    }

    /**
     * Meeting containers associated to the user.
     */
    public function meetingContainers(): HasMany
    {
        return $this->hasMany(MeetingContentContainer::class);
    }

    /**
     * Meetings synchronized for the user.
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(MeetingTranscription::class, 'user_id', 'id');
    }

    /**
     * Meeting groups created by the user.
     */
    public function ownedMeetingGroups(): HasMany
    {
        return $this->hasMany(MeetingGroup::class, 'owner_id');
    }

    /**
     * Meeting groups where the user is a member.
     */
    public function meetingGroups(): BelongsToMany
    {
        return $this->belongsToMany(MeetingGroup::class, 'meeting_group_user')->withTimestamps();
    }

    public function assistantSetting(): HasOne
    {
        return $this->hasOne(AssistantSetting::class);
    }

    public function assistantConversations(): HasMany
    {
        return $this->hasMany(AssistantConversation::class);
    }
}
