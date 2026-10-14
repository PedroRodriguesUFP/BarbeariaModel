<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::first(); // Obtém o primeiro cliente do banco de dados
        $barber = Barber::first(); // Obtém o primeiro cabeleireiro do banco de dados
        $service = Service::first(); // Obtém o primeiro serviço do banco de dados
  // se algum dos modelos não existir, o seeder não cria o agendamento
        if (! $client || ! $barber || ! $service) {
            return;
        }
    
        Appointment::create([
            'client_id' => $client->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'status' => Status::Pending->value,
        ]);
        
    }
}
