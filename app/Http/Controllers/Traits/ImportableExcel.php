<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

trait ImportableExcel
{
    /**
     * Lee un archivo CSV o Excel, normaliza los encabezados y procesa cada fila
     * mediante el callback recibido. El callback debe devolver 'creado' o
     * 'actualizado' para contabilizar el resultado.
     *
     * @return array{0: int, 1: int, 2: array<int, string>} [$importados, $actualizados, $errores]
     */
    private function leerArchivoImportacion(Request $request, callable $procesarFila, array $mapaEncabezados): array
    {
        $archivo = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());

        $importados = 0;
        $actualizados = 0;
        $errores = [];

        if (in_array($extension, ['csv', 'txt'])) {
            $handle = fopen($archivo->getPathname(), 'r');
            $primeraLinea = fgets($handle);

            if ($primeraLinea === false) {
                fclose($handle);
                throw new \Exception('El archivo CSV no tiene encabezados.');
            }

            $delimitador = $this->detectarDelimitador($primeraLinea);
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimitador);

            if (! $headers) {
                fclose($handle);
                throw new \Exception('El archivo CSV no tiene encabezados.');
            }

            $headers = $this->normalizarEncabezados($headers, $mapaEncabezados);
            $linea = 1;

            while (($row = fgetcsv($handle, 0, $delimitador)) !== false) {
                $linea++;
                try {
                    $row = array_map([$this, 'aUtf8'], $row);
                    $row = array_slice($row, 0, count($headers));
                    if (empty(array_filter($row, fn ($v) => trim((string) $v) !== ''))) {
                        continue;
                    }
                    $data = array_combine($headers, $row);
                    $resultado = $procesarFila($data);
                    if ($resultado === 'actualizado') {
                        $actualizados++;
                    } else {
                        $importados++;
                    }
                } catch (QueryException $e) {
                    $errores[] = "Linea {$linea}: No se pudo guardar el registro, revisa el formato de los datos.";
                } catch (\Exception $e) {
                    $errores[] = "Linea {$linea}: ".$e->getMessage();
                }
            }
            fclose($handle);
        } else {
            $spreadsheet = IOFactory::load($archivo->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                throw new \Exception('El archivo no tiene datos.');
            }

            $headers = $this->normalizarEncabezados($rows[0], $mapaEncabezados);

            for ($i = 1; $i < count($rows); $i++) {
                try {
                    $row = array_map([$this, 'aUtf8'], $rows[$i]);
                    $row = array_slice($row, 0, count($headers));
                    if (empty(array_filter($row, fn ($v) => trim((string) $v) !== ''))) {
                        continue;
                    }
                    $rowData = array_combine($headers, $row);
                    $resultado = $procesarFila($rowData);
                    if ($resultado === 'actualizado') {
                        $actualizados++;
                    } else {
                        $importados++;
                    }
                } catch (QueryException $e) {
                    $errores[] = 'Fila '.($i + 1).': No se pudo guardar el registro, revisa el formato de los datos.';
                } catch (\Exception $e) {
                    $errores[] = 'Fila '.($i + 1).': '.$e->getMessage();
                }
            }
        }

        return [$importados, $actualizados, $errores];
    }

    private function normalizarEncabezados(array $headers, array $mapa): array
    {
        $headers = array_map('trim', array_map([$this, 'aUtf8'], $headers));
        $headers = array_map(function (string $header) use ($mapa) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
            $header = mb_strtolower(trim($header));

            return $mapa[$header] ?? $header;
        }, $headers);

        return array_values(array_filter($headers, fn ($h) => $h !== ''));
    }

    /**
     * Detecta el separador real del CSV. Excel en configuracion regional en
     * espanol guarda los archivos con punto y coma en lugar de coma.
     */
    private function detectarDelimitador(string $primeraLinea): string
    {
        $primeraLinea = preg_replace('/^\xEF\xBB\xBF/', '', $primeraLinea) ?? $primeraLinea;

        $conteos = [
            ',' => substr_count($primeraLinea, ','),
            ';' => substr_count($primeraLinea, ';'),
            "\t" => substr_count($primeraLinea, "\t"),
        ];
        arsort($conteos);
        $delimitador = array_key_first($conteos);

        return $conteos[$delimitador] > 0 ? $delimitador : ',';
    }

    /**
     * Normaliza a UTF-8 los valores leidos del archivo. Excel guarda los CSV
     * en Windows-1252, lo que corrompe los acentos y rompe la insercion.
     */
    private function aUtf8($valor)
    {
        if (! is_string($valor)) {
            return $valor;
        }

        $valor = preg_replace('/^\xEF\xBB\xBF/', '', $valor) ?? $valor;

        if ($valor === '' || mb_check_encoding($valor, 'UTF-8')) {
            return $valor;
        }

        return mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');
    }
}
