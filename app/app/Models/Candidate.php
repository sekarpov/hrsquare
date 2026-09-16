<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use App\Enums\CandidateStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Candidate extends Model
{
    protected $fillable = ['full_name', 'position', 'city', 'company', 'division', 'project', 'status'];

    protected function casts(): array
    {
        return ['status' => CandidateStatus::class];
    }

    public function hiringManagers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'candidate_hiring_managers');
    }

    public function recruiters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'candidate_recruiters');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(CandidateAssessment::class);
    }

    public function currentAssessment(): HasOne
    {
        return $this->hasOne(CandidateAssessment::class)->ofMany(['completed_at' => 'max', 'id' => 'max'], fn (Builder $q) => $q->where('status', AssessmentStatus::COMPLETED));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->role === UserRole::RECRUITER ? $query : $query->whereHas('hiringManagers', fn (Builder $q) => $q->where('users.id', $user->id));
    }
}
