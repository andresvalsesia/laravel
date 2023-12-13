<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class vtex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:vtex {parametro*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'consultar a la API publica VTEX';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $search = $this->argument('parametro')[0];

        $accountName = "newsport";
        $environment = "vtexcommercestable";

        $api = "https://{$accountName}.{$environment}.com.br/api/catalog_system/pub/products/search/{$search}";

        //Inicio sesion cURL (Client URL)
        $ch = curl_init($api);

        // Configurar opciones de cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Realizar la solicitud cURL
        $response = curl_exec($ch);

        //Manejo de errores 
        if (curl_errno($ch)) {
            $this->error('Error al realizar la solicitud cURL: ' . curl_error($ch));
        } else {
            // Procesar la respuesta
            $data = json_decode($response, true);

            $message = "";

            //Busco previamente si existe en base
            
            $result =  DB::table('consulta_productos')
                ->where('nombre', $search)
                ->get();

            // Si existe actualizo fecha
            if (count($result) != 0) {
                DB::table('consulta_productos')
                ->where('nombre', $search)
                ->update([
                    'updated_at' => Carbon::now(),
                ]);

                $message = "actualizado";    
            } else {
                // Persisto en base
                DB::table('consulta_productos')->insert([
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'nombre' => $search,
                'resultados' => (int) count($data)
                ]);
                $message = "creado";
            }

            $this->info("Registro {$message} correctamente.");

            $results = DB::table('consulta_productos')->get();

            //Muestro tabla
            $this->table(
                ['id', 'created_at', 'updated_at', 'nombre', 'resultados'],
                $results->map(function ($item) {
                    return (array) $item;
                })->toArray()
            );
        }

        // Cerrar la sesión cURL
        curl_close($ch);
    }

}
