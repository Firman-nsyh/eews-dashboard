<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeismicDataReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('seismic'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'data.received';
    }

    public function broadcastWith(): array
    {
        return [
            'sensor_id'    => $this->data['sensor_id'],
            'timestamp'    => $this->data['timestamp'],
            'stalta_ratio' => $this->data['stalta_ratio'],
            'sta_value'    => $this->data['sta_value']    ?? 0,
            'lta_value'    => $this->data['lta_value']    ?? 0,
            'deviation'    => $this->data['deviation']    ?? 0,
            'status'       => $this->data['status'],
            'latitude'     => $this->data['latitude'],
            'longitude'    => $this->data['longitude'],
            'is_alert'     => $this->data['is_alert']     ?? false,
        ];
    }
}