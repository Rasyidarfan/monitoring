<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AnomalyData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadAnomalyCSVIntelligentSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Activity $activity;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and activity
        $this->user = User::factory()->create();
        $this->activity = Activity::factory()->create();
    }

    /**
     * Test uploading single file in INTELLIGENT SYNC mode
     */
    public function test_uploads_single_file_intelligent_sync(): void
    {
        $csvContent = "KODE_DAERAH,ANOMALI,DSRT,NO_ART,NAMA_KRT,NAMA_ART\n";
        $csvContent .= "32010101,A01,1,1,Budi,Istri\n";
        $csvContent .= "32010101,A02,1,2,Budi,Anak\n";

        $file = $this->createCsvFile($csvContent, 'test1.csv');

        $response = $this->actingAs($this->user)
            ->postJson(
                route('upload.anomaly-csv-multi', $this->activity),
                [
                    'anomaly_csv_files' => [$file],
                    'mode' => 'intelligent_sync',
                ],
            );

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'files' => ['*' => ['filename', 'status', 'rows_processed']],
            'summary' => ['total_inserted', 'total_updated', 'total_deleted'],
        ]);

        $this->assertDatabaseCount('anomaly_data', 2);
    }

    /**
     * Test INTELLIGENT SYNC deletes orphaned records
     */
    public function test_intelligent_sync_deletes_orphaned_records(): void
    {
        // Create existing anomaly records
        AnomalyData::create([
            'activity_id' => $this->activity->id,
            'kode_daerah' => '32010101',
            'dsrt' => '1',
            'no_art' => 1,
            'anomali' => 'A01',
            'kecamatan' => 'JAKARTA PUSAT',
            'desa' => 'KEL. TANAH ABANG',
            'nama_krt' => 'Budi',
            'nama_art' => 'Istri',
            'checked' => false,
        ]);

        AnomalyData::create([
            'activity_id' => $this->activity->id,
            'kode_daerah' => '32010101',
            'dsrt' => '1',
            'no_art' => 2,
            'anomali' => 'A02',
            'kecamatan' => 'JAKARTA PUSAT',
            'desa' => 'KEL. TANAH ABANG',
            'nama_krt' => 'Budi',
            'nama_art' => 'Anak',
            'checked' => false,
        ]);

        // Upload CSV with only first anomaly
        $csvContent = "KODE_DAERAH,ANOMALI,DSRT,NO_ART,NAMA_KRT,NAMA_ART\n";
        $csvContent .= "32010101,A01,1,1,Budi,Istri\n";

        $file = $this->createCsvFile($csvContent, 'test1.csv');

        $response = $this->actingAs($this->user)
            ->postJson(
                route('upload.anomaly-csv-multi', $this->activity),
                [
                    'anomaly_csv_files' => [$file],
                    'mode' => 'intelligent_sync',
                ],
            );

        $response->assertOk();
        $response->assertJsonPath('summary.total_deleted', 1);
        $response->assertJsonPath('summary.total_updated', 1);

        // Verify orphaned record deleted
        $this->assertDatabaseCount('anomaly_data', 1);
        $this->assertDatabaseMissing('anomaly_data', [
            'anomali' => 'A02',
        ]);
    }

    /**
     * Test INTELLIGENT SYNC updates existing records
     */
    public function test_intelligent_sync_updates_existing_records(): void
    {
        AnomalyData::create([
            'activity_id' => $this->activity->id,
            'kode_daerah' => '32010101',
            'dsrt' => '1',
            'no_art' => 1,
            'anomali' => 'A01',
            'kecamatan' => 'JAKARTA PUSAT',
            'desa' => 'KEL. TANAH ABANG',
            'nama_krt' => 'Budi',
            'nama_art' => 'Istri',
            'checked' => true,
        ]);

        $csvContent = "KODE_DAERAH,ANOMALI,DSRT,NO_ART,NAMA_KRT,NAMA_ART\n";
        $csvContent .= "32010101,A01,1,1,Budi Updated,Istri Updated\n";

        $file = $this->createCsvFile($csvContent, 'test1.csv');

        $response = $this->actingAs($this->user)
            ->postJson(
                route('upload.anomaly-csv-multi', $this->activity),
                [
                    'anomaly_csv_files' => [$file],
                    'mode' => 'intelligent_sync',
                ],
            );

        $response->assertOk();
        $response->assertJsonPath('summary.total_updated', 1);

        // Verify record updated but checked flag reset
        $this->assertDatabaseHas('anomaly_data', [
            'anomali' => 'A01',
            'nama_krt' => 'Budi Updated',
            'checked' => false,
        ]);
    }

    /**
     * Test MERGE mode preserves all existing records
     */
    public function test_merge_mode_preserves_existing(): void
    {
        AnomalyData::create([
            'activity_id' => $this->activity->id,
            'kode_daerah' => '32010101',
            'dsrt' => '1',
            'no_art' => 1,
            'anomali' => 'A01',
            'kecamatan' => 'JAKARTA PUSAT',
            'desa' => 'KEL. TANAH ABANG',
            'nama_krt' => 'Budi',
            'nama_art' => 'Istri',
            'checked' => false,
        ]);

        $csvContent = "KODE_DAERAH,ANOMALI,DSRT,NO_ART,NAMA_KRT,NAMA_ART\n";
        $csvContent .= "32010102,A02,2,1,Ahmad,Istri\n";

        $file = $this->createCsvFile($csvContent, 'test1.csv');

        $response = $this->actingAs($this->user)
            ->postJson(
                route('upload.anomaly-csv-multi', $this->activity),
                [
                    'anomaly_csv_files' => [$file],
                    'mode' => 'merge',
                ],
            );

        $response->assertOk();
        $response->assertJsonPath('summary.total_deleted', 0);
        $response->assertJsonPath('summary.total_inserted', 1);

        // Verify both records exist
        $this->assertDatabaseCount('anomaly_data', 2);
    }

    /**
     * Test uploading multiple files
     */
    public function test_uploads_multiple_files(): void
    {
        $csv1 = "KODE_DAERAH,ANOMALI,DSRT,NO_ART,NAMA_KRT,NAMA_ART\n";
        $csv1 .= "32010101,A01,1,1,Budi,Istri\n";

        $csv2 = "KODE_DAERAH,ANOMALI,DSRT,NO_ART,NAMA_KRT,NAMA_ART\n";
        $csv2 .= "32010102,A02,2,1,Ahmad,Istri\n";

        $file1 = $this->createCsvFile($csv1, 'test1.csv');
        $file2 = $this->createCsvFile($csv2, 'test2.csv');

        $response = $this->actingAs($this->user)
            ->postJson(
                route('upload.anomaly-csv-multi', $this->activity),
                [
                    'anomaly_csv_files' => [$file1, $file2],
                    'mode' => 'intelligent_sync',
                ],
            );

        $response->assertOk();
        $response->assertJsonCount(2, 'files');
        $response->assertJsonPath('summary.total_inserted', 2);

        $this->assertDatabaseCount('anomaly_data', 2);
    }

    /**
     * Test rejects invalid CSV headers
     */
    public function test_rejects_invalid_csv_headers(): void
    {
        $csvContent = "INVALID,HEADERS,HERE\n";
        $csvContent .= "32010101,A01,1\n";

        $file = $this->createCsvFile($csvContent, 'test1.csv');

        $response = $this->actingAs($this->user)
            ->postJson(
                route('upload.anomaly-csv-multi', $this->activity),
                [
                    'anomaly_csv_files' => [$file],
                    'mode' => 'intelligent_sync',
                ],
            );

        $response->assertStatus(422);
        $response->assertJsonPath('status', 'error');
    }

    /**
     * Create temporary CSV file for testing
     */
    private function createCsvFile(string $content, string $filename): \Illuminate\Http\UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test_csv_');
        file_put_contents($path, $content);

        return new \Illuminate\Http\UploadedFile(
            $path,
            $filename,
            'text/csv',
            null,
            true
        );
    }
}
