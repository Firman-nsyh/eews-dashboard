<?php

namespace App\Http\Controllers;

use App\Events\SeismicDataReceived;
use Illuminate\Http\Request;
use InfluxDB2\Client;
use InfluxDB2\Model\DeletePredicateRequest;
use InfluxDB2\Service\DeleteService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelemetryController extends Controller
{
    private function influx(): Client
    {
        return new Client([
            'url'    => config('influxdb.url'),
            'token'  => config('influxdb.token'),
            'org'    => config('influxdb.org'),
            // Kita tidak memanggil config bucket di sini karena kita akan hardcode 
            // namanya sesuai dengan flow Node-RED (telemetry_live & alert_history)
        ]);
    }

    // ========================================================
    // 1. TERIMA DATA DARI NODE-RED (HANYA UNTUK WEBSOCKET)
    // ========================================================
    public function store(Request $request)
    {
        $data = $request->validate([
            'sensor_id'        => 'required|string',
            'timestamp'        => 'nullable', 
            'stalta_ratio'     => 'required|numeric',
            'sta_value'        => 'nullable|numeric',
            'lta_value'        => 'nullable|numeric',
            'deviation'        => 'nullable|numeric',
            'status'           => 'required|string',
            'latitude'         => 'required|numeric',
            'longitude'        => 'required|numeric',
            'is_alert'         => 'nullable|boolean',
        ]);

        // 🔥 PERUBAHAN: Laravel TIDAK LAGI menulis ke InfluxDB karena Node-RED 
        // sudah melakukannya. Laravel hanya fokus menyiarkan data ke UI Web.
        broadcast(new SeismicDataReceived($data));

        return response()->json(['success' => true]);
    }

    // ========================================================
    // 2. KIRIM PERINTAH KONTROL KE NODE-RED (HARDWARE)
    // ========================================================
    public function sendControl(Request $request)
    {
        $request->validate([
            'cmd'      => 'required|string|in:SIREN,RELAY,CALIB',
            'duration' => 'nullable|integer',
            'action'   => 'nullable|integer|in:0,1',
        ]);

        try {
            $response = Http::post('http://127.0.0.1:1880/api/control', $request->all());
            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'Perintah kontrol berhasil diteruskan ke Gateway!']);
            }
            return response()->json(['success' => false, 'error' => 'Node-RED menolak perintah kontrol.'], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ========================================================
    // 3. HARD DELETE: SAPU BERSIH DATA KALIBRASI PADA KEDUA BUCKET
    // ========================================================
    public function deleteCalibrationData(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date', 
            'end_time'   => 'required|date|after:start_time',
        ]);

        try {
            $client = $this->influx();
            $service = $client->createService(DeleteService::class);
            $org = config('influxdb.org');

            $startDateTime = new \DateTime($request->start_time);
            $endDateTime   = new \DateTime($request->end_time);

            $predicate = new DeletePredicateRequest();
            $predicate->setStart($startDateTime);
            $predicate->setStop($endDateTime);
            $predicate->setPredicate('_measurement="status_gempa"'); // Sesuai Node-RED
            
            // Hapus di bucket Live
            $service->postDelete($predicate, null, $org, 'telemetry_live');
            // Hapus di bucket Arsip Alert
            $service->postDelete($predicate, null, $org, 'alert_history');

            $client->close();

            return response()->json([
                'success' => true, 
                'message' => 'Brankas InfluxDB dibersihkan! Data kalibrasi berhasil dihanguskan.'
            ]);
        } catch (\Throwable $e) { 
            Log::error('Gagal Hard Delete InfluxDB: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ========================================================
    // 4. DATA LIVE UTAMA (BACA DARI BUCKET: telemetry_live)
    // ========================================================
    public function latest()
    {
        try {
            $client   = $this->influx();
            $queryApi = $client->createQueryApi();

            $flux = '
                from(bucket: "telemetry_live")
                    |> range(start: -24h)
                    |> filter(fn: (r) => r._measurement == "status_gempa")
                    |> pivot(rowKey: ["_time"], columnKey: ["_field"], valueColumn: "_value")
                    |> sort(columns: ["_time"], desc: true)
                    |> limit(n: 1)
            ';

            $result = $queryApi->query($flux);
            $client->close();

            $rows = [];
            foreach ($result as $table) {
                foreach ($table->records as $record) {
                    $rows[] = [
                        'time'         => $record->getTime(),
                        'sensor_id'    => $record->values['sensor_id']    ?? null,
                        'status'       => $record->values['status']       ?? 'IDLE',
                        'stalta_ratio' => $record->values['stalta_ratio'] ?? 0,
                        'deviation'    => $record->values['deviation']    ?? 0,
                    ];
                }
            }
            return response()->json(['success' => true, 'data' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ========================================================
    // 5. LOG DATA HISTORY RAW (BACA DARI BUCKET: telemetry_live)
    // ========================================================
    public function history(Request $request)
    {
        $range = $request->get('range', '24h');
        try {
            $client   = $this->influx();
            $queryApi = $client->createQueryApi();

            // 🔥 PERBAIKAN: Mengambil data dari telemetry_live, menggunakan measurement 
            // "status_gempa" dan memakai |> group() agar tabel tidak kosong saat refresh.
            $flux = '
                from(bucket: "telemetry_live")
                    |> range(start: -' . $range . ')
                    |> filter(fn: (r) => r._measurement == "status_gempa")
                    |> group()
                    |> pivot(rowKey: ["_time"], columnKey: ["_field"], valueColumn: "_value")
                    |> sort(columns: ["_time"], desc: true)
                    |> limit(n: 500)
            ';

            $result = $queryApi->query($flux);
            $client->close();

            $rows = [];
            foreach ($result as $table) {
                foreach ($table->records as $record) {
                    $rows[] = [
                        'time'         => $record->getTime(),
                        'sensor_id'    => $record->values['sensor_id']    ?? null,
                        'status'       => $record->values['status']       ?? 'IDLE',
                        'stalta_ratio' => $record->values['stalta_ratio'] ?? 0,
                        'deviation'    => $record->values['deviation']    ?? 0,
                    ];
                }
            }
            return response()->json(['success' => true, 'data' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ========================================================
    // 6. COUNTER EVENT GEMPA (BACA DARI BUCKET: alert_history)
    // ========================================================
    public function alertStats()
    {
        try {
            $client   = $this->influx();
            $queryApi = $client->createQueryApi();

            $flux = '
                from(bucket: "alert_history")
                    |> range(start: -24h)
                    |> filter(fn: (r) => r._measurement == "status_gempa")
                    |> group()
                    |> pivot(rowKey: ["_time"], columnKey: ["_field"], valueColumn: "_value")
            ';
            $result = $queryApi->query($flux);
            $client->close();
            
            $stats = ['EARTHQUAKE' => 0, 'GEMPA' => 0, 'EVALUASI' => 0, 'P-WAVE' => 0, 'S-WAVE' => 0, 'RESET' => 0, 'timeline' => []];
            foreach ($result as $table) {
                foreach ($table->records as $record) {
                    $status = $record->values['status'] ?? 'UNKNOWN';
                    if (isset($stats[$status])) { $stats[$status]++; }
                    if (in_array($status, ['EARTHQUAKE', 'GEMPA', 'P-WAVE', 'S-WAVE'])) {
                        $stats['timeline'][] = [
                            'time' => $record->getTime(), 'status' => $status,
                            'stalta_ratio' => $record->values['stalta_ratio'] ?? 0,
                        ];
                    }
                }
            }
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ========================================================
    // 7. GRAFIK SEISMOGRAF (BACA DARI BUCKET: telemetry_live)
    // ========================================================
    public function seismografData()
    {
        try {
            $client   = $this->influx();
            $queryApi = $client->createQueryApi();

            $flux = '
                from(bucket: "telemetry_live")
                    |> range(start: -24h)
                    |> filter(fn: (r) => r._measurement == "status_gempa")
                    |> pivot(rowKey: ["_time"], columnKey: ["_field"], valueColumn: "_value")
                    |> sort(columns: ["_time"], desc: true)
                    |> limit(n: 50)
                    |> sort(columns: ["_time"], desc: false)
            ';

            $result = $queryApi->query($flux);
            $client->close();

            $rows = [];
            foreach ($result as $table) {
                foreach ($table->records as $record) {
                    $rows[] = [
                        'time'         => $record->getTime(),
                        'stalta_ratio' => (float)($record->values['stalta_ratio'] ?? 0),
                        'deviation'    => (float)($record->values['deviation']    ?? 0),
                    ];
                }
            }
            return response()->json(['success' => true, 'data' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}