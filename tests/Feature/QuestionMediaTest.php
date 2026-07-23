<?php

namespace Tests\Feature;

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionMediaTest extends TestCase
{
    public function test_admin_can_stream_question_image_from_current_private_storage(): void
    {
        Storage::fake('soal_images');
        Storage::fake('soal_images_legacy');
        $filename = '11111111-1111-1111-1111-111111111111.png';
        Storage::disk('soal_images')->put('soal-images/'.$filename, 'image-content');

        $response = app(MediaController::class)->soalAdmin($filename);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_old_question_image_remains_readable_from_legacy_storage(): void
    {
        Storage::fake('soal_images');
        Storage::fake('soal_images_legacy');
        $filename = '22222222-2222-2222-2222-222222222222.jpg';
        Storage::disk('soal_images_legacy')->put('soal-images/'.$filename, 'legacy-image');

        $response = app(MediaController::class)->soalAdmin($filename);

        $this->assertSame(200, $response->getStatusCode());
    }
}
