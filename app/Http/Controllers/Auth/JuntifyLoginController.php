<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class JuntifyLoginController extends Controller
{
    /**
     * Mostrar el formulario de login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Procesar el login validando contra Juntify
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            // Validar contra el endpoint de Juntify
            $juntifyApiUrl = env('JUNTIFY_API_URL', 'https://juntify.com/api');
            $response = Http::timeout(10)->post("{$juntifyApiUrl}/auth/validate-user", [
                'email' => $request->email,
                'password' => $request->password,
                'nombre_empresa' => 'DDU'
            ]);

            $data = $response->json();

            Log::info('Respuesta de Juntify:', $data ?? []);

            // Verificar respuesta exitosa
            if ($response->successful() && 
                isset($data['success']) && 
                $data['success'] === true && 
                isset($data['belongs_to_company']) && 
                $data['belongs_to_company'] === true) {
                
                // Usuario válido y pertenece a DDU
                Session::put('juntify_user', $data['user']);
                Session::put('juntify_company', $data['company']);
                Session::put('authenticated', true);
                Session::put('juntify_auth_time', now()->timestamp);
                
                Log::info('Login exitoso para usuario: ' . $data['user']['email']);
                
                return redirect()->intended(route('dashboard'))
                    ->with('success', 'Bienvenido ' . $data['user']['name']);
            }

            // Usuario no válido o no pertenece a DDU
            Log::warning('Login fallido para: ' . $request->email, [
                'belongs_to_company' => $data['belongs_to_company'] ?? false,
                'message' => $data['message'] ?? 'Sin mensaje'
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => $data['message'] ?? 'No tienes acceso a este sistema. Solo usuarios de DDU pueden ingresar.'
                ]);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Error de conexión con Juntify: ' . $e->getMessage());
            
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Error al conectar con el servidor de autenticación. Verifica que Juntify esté corriendo en el puerto 8000.'
                ]);
        } catch (\Exception $e) {
            Log::error('Error general en login: ' . $e->getMessage());
            
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Error al procesar la autenticación. Intenta nuevamente.'
                ]);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        $userName = Session::get('juntify_user.name', 'Usuario');
        
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Log::info('Sesión cerrada para: ' . $userName);
        
        return redirect()->route('login')
            ->with('success', 'Sesión cerrada correctamente');
    }
}
