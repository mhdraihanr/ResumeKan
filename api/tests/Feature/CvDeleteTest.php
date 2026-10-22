<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tombol Hapus di dashboard pernah dibatasi `language !== 'id'`, jadi CV
 * berbahasa Indonesia tidak bisa dihapus dari UI. Test ini menjaga agar
 * batasan bahasa itu tidak pernah naik ke server: `destroy()` hanya memeriksa
 * kepemilikan, bukan bahasa.
 */
class CvDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function buatCv(User $user, string $language): Cv
    {
        return $user->cvs()->create([
            'title' => 'CV Uji',
            'template' => 'modern',
            'language' => $language,
            'data' => [
                'personal' => [
                    'name' => 'Budi',
                    'email' => 'budi@email.com',
                    'phone' => '08123456789',
                    'address' => 'Jakarta',
                ],
            ],
        ]);
    }

    public function test_owner_dapat_menghapus_cv_bahasa_indonesia()
    {
        $user = $this->actingUser();
        $cv = $this->buatCv($user, 'id');

        $response = $this->deleteJson("/api/v1/cvs/{$cv->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('cvs', ['id' => $cv->id]);
    }

    public function test_owner_dapat_menghapus_cv_bahasa_inggris()
    {
        $user = $this->actingUser();
        $cv = $this->buatCv($user, 'en');

        $response = $this->deleteJson("/api/v1/cvs/{$cv->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('cvs', ['id' => $cv->id]);
    }

    public function test_user_lain_tidak_dapat_menghapus_cv()
    {
        $pemilik = User::factory()->create();
        $cv = $this->buatCv($pemilik, 'id');

        $userLain = $this->actingUser();

        $response = $this->deleteJson("/api/v1/cvs/{$cv->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('cvs', ['id' => $cv->id]);
    }

    public function test_tamu_tidak_dapat_menghapus_cv()
    {
        $pemilik = User::factory()->create();
        $cv = $this->buatCv($pemilik, 'id');

        $response = $this->deleteJson("/api/v1/cvs/{$cv->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('cvs', ['id' => $cv->id]);
    }
}
