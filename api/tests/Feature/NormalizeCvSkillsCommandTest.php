<?php

namespace Tests\Feature;

use App\Console\Commands\NormalizeCvSkills;
use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizeCvSkillsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function cvWithSkills(mixed $skills, string $title = 'Uji'): Cv
    {
        $user = User::factory()->create();

        // user_id sengaja TIDAK ada di $fillable (dijaga model), jadi diset eksplisit.
        $cv = new Cv([
            'title' => $title,
            'template' => 'modern',
            'language' => 'id',
            'data' => ['skills' => $skills],
        ]);
        $cv->user_id = $user->id;
        $cv->save();

        return $cv->refresh();
    }

    public function test_legacy_object_is_converted_to_array_of_groups(): void
    {
        $cv = $this->cvWithSkills(['hard' => 'Go, PHP', 'soft' => 'Komunikasi']);

        $this->artisan('cv:normalize-skills')->assertSuccessful();

        $skills = $cv->refresh()->data['skills'];
        $this->assertIsList($skills);
        $this->assertSame([
            ['label' => 'Hard skills', 'items' => 'Go, PHP'],
            ['label' => 'Soft skills', 'items' => 'Komunikasi'],
        ], $skills);
    }

    public function test_one_sided_legacy_object_keeps_only_filled_group(): void
    {
        $cv = $this->cvWithSkills(['hard' => 'Rust', 'soft' => '']);

        $this->artisan('cv:normalize-skills')->assertSuccessful();

        $this->assertSame(
            [['label' => 'Hard skills', 'items' => 'Rust']],
            $cv->refresh()->data['skills'],
        );
    }

    public function test_all_empty_legacy_object_becomes_empty_array(): void
    {
        $cv = $this->cvWithSkills(['hard' => '', 'soft' => '   ']);

        $this->artisan('cv:normalize-skills')->assertSuccessful();

        $this->assertSame([], $cv->refresh()->data['skills']);
    }

    public function test_whitespace_only_group_is_dropped(): void
    {
        $cv = $this->cvWithSkills(['hard' => 'Git', 'soft' => "  \t "]);

        $this->artisan('cv:normalize-skills')->assertSuccessful();

        $this->assertSame(
            [['label' => 'Hard skills', 'items' => 'Git']],
            $cv->refresh()->data['skills'],
        );
    }

    public function test_already_normalized_cv_is_untouched(): void
    {
        $original = [
            ['label' => 'Hard skills', 'items' => 'Vue'],
            ['label' => 'Library & Frameworks', 'items' => 'Pinia'],
        ];
        $cv = $this->cvWithSkills($original);

        $this->artisan('cv:normalize-skills')->assertSuccessful();

        $this->assertSame($original, $cv->refresh()->data['skills']);
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        $cv = $this->cvWithSkills(['hard' => 'Go', 'soft' => 'Komunikasi']);

        $this->artisan('cv:normalize-skills', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(
            ['hard' => 'Go', 'soft' => 'Komunikasi'],
            $cv->refresh()->data['skills'],
            'Dry-run tidak boleh menulis ke database.',
        );
    }

    public function test_cv_without_skills_is_skipped(): void
    {
        $cv = new Cv([
            'title' => 'Tanpa skills',
            'template' => 'modern',
            'language' => 'id',
            'data' => ['personal' => ['name' => 'A']],
        ]);
        $cv->user_id = User::factory()->create()->id;
        $cv->save();

        $this->artisan('cv:normalize-skills')->assertSuccessful();

        $this->assertArrayNotHasKey('skills', $cv->refresh()->data);
    }

    public function test_command_is_idempotent(): void
    {
        $cv = $this->cvWithSkills(['hard' => 'Go, PHP', 'soft' => 'Komunikasi']);

        $this->artisan('cv:normalize-skills')->assertSuccessful();
        $first = $cv->refresh()->data['skills'];

        $this->artisan('cv:normalize-skills')->assertSuccessful();

        $this->assertSame($first, $cv->refresh()->data['skills']);
    }

    public function test_helper_matches_request_migration_logic(): void
    {
        $this->assertSame(
            [
                ['label' => 'Hard skills', 'items' => 'A'],
                ['label' => 'Soft skills', 'items' => 'B'],
            ],
            NormalizeCvSkills::normalizeLegacySkills(['hard' => 'A', 'soft' => 'B']),
        );

        $this->assertSame([], NormalizeCvSkills::normalizeLegacySkills([]));
    }
}
