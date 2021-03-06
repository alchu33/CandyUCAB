<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Session;
use Redirect;
use Validator;

class ClientsController extends Controller
{
    /*  Consulta para las direcciones del cliente: 
        select q.*
        from lugar q, (select s.lug_id, s.lug_nombre from lugar s,lugar a where a.lug_nombre = 'Apure' and a.lug_id = s.fklugar) as x
        where x.lug_nombre = 'Achaguas' and q.fklugar = x.lug_id;  */
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $naturales=DB::select(DB::raw("SELECT cli_nombre, cli_apellido, cli_correo, cli_ci, cli_id, cli_tipo from Cliente where cli_tipo = 'N'"));
        $juridicos=DB::select(DB::raw("SELECT cli_pagina_web, cli_razon_social, cli_correo, cli_id, cli_tipo from Cliente where cli_tipo = 'J'"));
        return view('listar-clientes', compact('naturales', 'juridicos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(){
        $estados =   DB::Select(DB::raw("SELECT lug_nombre, lug_id from lugar where lug_tipo= 'Estado';"));
        $municipios= DB::Select(DB::raw("SELECT lug_nombre, lug_id from lugar where lug_tipo = 'Municipio';"));
        $parroquias= DB::Select(DB::raw("SELECT lug_nombre, lug_id from lugar where lug_tipo = 'Parroquia';"));
        $tiendas = DB::select(DB::raw("SELECT t.tie_tipo, t.tie_id,l.lug_nombre from tienda t, lugar l where t.fklugar = l.lug_id;"));
        return view ('candy-registro', compact('estados','municipios','parroquias','tiendas'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        $rules = [
            'rif' => 'required|string|between:1,50',
            'correo' => 'required|string|between:1,50',
            'pagina_web' => 'nullable|string|between:1,50',
            'total_capital'=>'nullable|numeric',
            'deno_comercial' => 'nullable|string|between:1,50', 
            'razon_social' => 'nullable|string|between:1,50',
            'ci' =>'nullable|integer',
            'nombre' =>'nullable|string|between:1,50',    
            'apellido' =>'nullable|string|between:1,50',
            'num_carnet' =>'nullable|string|between:1,50',
            'tienda' =>'required|string|between:1,50',
            'username'=> 'required|string|between:1,50',
            'clave'=> 'required|string|between:1,50',
            'telefono'=>'required|numeric',
            'contacto'=> 'nullable|string|between: 1,50'
        ];
        $customMessages = [
            'rif.required' => 'Debe introducir su RIF personal',
            'correo.required' => 'Debe introducir su correo',
            'tienda.required' => 'Debe introducir su tienda de preferencia',
            'username.required' => 'Debe introducir un usuario para este cliente',
            'clave.required' => 'Debe introducir una clave para el usuario',
            'telefono.required' => 'Debe introducir un telefono de contacto'
        ];
            $this->validate($request, $rules, $customMessages);
            $rif = $request->input('rif');
            (string)$correo = $request->input('correo');
            $pagina_web = $request->input('pagina_web');
            $razon_social = $request->input('razon_social');
            $total_capital = $request->input('total_capital');
            $deno_comercial = $request->input('deno_comercial');
            $ci = $request->input('ci');
            $nombre = $request->input('nombre');
            $apellido = $request->input('apellido');
            $num_carnet = $request->input('num_carnet');
            $tienda = $request->input('tienda');

            /* Variables para la tabla Usuario */
            (string)$usuario = $request->input('username');
            (string)$clave = $request->input('clave');
            $usu_tipo= 'Cliente';

            /* Variables para la tabla Telefono */
            $telefono=$request->input('telefono');
            $tel_tipo='Principal';
            
            if ($pagina_web == null){
                $tipo= 'N';
                $dir_tipo='P';

                /* Variables para la tabla Cli_lug */
                $estado = $request->input('estado');
                $municipio = $request->input('municipio');
                $parroquia = $request->input('parroquia');
                DB::insert('Insert into Cliente (Cli_rif, Cli_correo, Cli_ci, Cli_nombre, Cli_apellido,  Cli_tipo, fktienda)
                values(?,?,?,?,?,?,?)', [$rif, $correo,$ci,$nombre,$apellido,$tipo,$tienda]);
                $cliente =DB::select('select cli_id from Cliente  where cli_correo = ? ',[$correo]);
                $id=$cliente[0]->cli_id;
                /* Esta consulta me regresa un arreglo de objetos, al cual como me regresa un solo cliente con un solo atributo accedo
                    $id=$cliente[0]->cli_id, si hubiera mas clientes debo recorrerlo con un foreach */
                
                /* Insertes para la tabla de Cli_lug*/ 
                DB::insert('Insert into Cli_lug (fklugar, fkcliente, cli_tipo) values(?,?,?)', [$estado,$id,$dir_tipo]);
                DB::insert('Insert into Cli_lug (fklugar, fkcliente, cli_tipo) values(?,?,?)', [$municipio,$id,$dir_tipo]);
                DB::insert('Insert into Cli_lug (fklugar, fkcliente, cli_tipo) values(?,?,?)', [$parroquia,$id,$dir_tipo]);
            }
            else {
                $tipo='J';
                DB::insert('Insert into Cliente (CLi_rif, Cli_correo, Cli_pagina_web,Cli_razon_social ,Cli_deno_comercial, Cli_total_capital, Cli_tipo ,fktienda)
                values (?,?,?,?,?,?,?,?)', [$rif, $correo, $pagina_web, $razon_social,$deno_comercial, $total_capital, $tipo, $tienda]);
                
                $cliente =DB::select('select cli_id from Cliente  where cli_correo = ? ',[$correo]);
                $id=$cliente[0]->cli_id;

                /* Tabla Contacto */
                $contacto=$request->input('contacto');

                DB::insert('Insert into Contacto (con_nombre, fkcliente) values(?,?)', [$contacto,$id]);

                /* Tabla Cli_lug */
                $dir1_tipo='F'; $dir2_tipo='FP';
                $estadoF = $request->input('estadoF');
                $municipioF = $request->input('municipioF');
                $parroquiaF = $request->input('parroquiaF');
                $estadoFP = $request->input('estadoFP');
                $municipioFP = $request->input('municipioFP');
                $parroquiaFP = $request->input('parroquiaFP');

                DB::insert('Insert into Cli_lug (fklugar, fkcliente, cli_tipo) values(?,?,?)', [$estadoF,$id,$dir1_tipo]);
                DB::insert('Insert into Cli_lug (fklugar, fkcliente, cli_tipo) values(?,?,?)', [$municipioF,$id,$dir1_tipo]);
                DB::insert('Insert into Cli_lug (fklugar, fkcliente, cli_tipo) values(?,?,?)', [$parroquiaF,$id,$dir1_tipo]); 
                DB::insert('Insert into Cli_lug (fklugar, fkcliente, cli_tipo) values(?,?,?)', [$estadoFP,$id,$dir2_tipo]);
                DB::insert('Insert into Cli_lug (fklugar, fkcliente, cli_tipo) values(?,?,?)', [$municipioFP,$id,$dir2_tipo]);
                DB::insert('Insert into Cli_lug (fklugar, fkcliente, cli_tipo) values(?,?,?)', [$parroquiaFP,$id,$dir2_tipo]);
            }

            /* Tabla de Usuario */
            $rol=DB::select('select rol_id from Rol where rol_tipo = ?',[$usu_tipo]);
            $id_rol=$rol[0]->rol_id;
            DB::insert('Insert into Usuario (Usu_nombre, Usu_contrasena, Usu_tipo, fkcliente, fkrol)
            values(?,?,?,?,?)', [$usuario, $clave, $usu_tipo, $id, $id_rol]);
            DB::insert('Insert into Telefono (Tel_tipo, Tel_numero, fkCliente) values (?,?,?)', [$tel_tipo, $telefono, $id]);

            Session::flash('message', 'Cliente creado');
            return Redirect::to('registro');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        $cliente = DB::select('select * from Cliente where cli_correo = ?', [$correo]);
        return $cliente;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id){
        /* $usuarios= DB::Select('select usu_nombre from Usuario where fkCliente= :id' ,['id'=>$id]);
        $usuario=$usuarios;
        \Log::info($usuarios); */
        $clientes = DB::select('select * from Cliente where cli_id = :id', ['id'=>$id]);
        $cliente=$clientes[0];
        return view('editar-cliente', compact('cliente','id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){
        $rules = [
            'nombre' =>'nullable|string|between:1,50',    
            'apellido' =>'nullable|string|between:1,50',
            'pagina_web' => 'nullable|string|between:1,50',
            'correo' => 'nullable|string|between:1,50',
            'tienda' => 'nullable|number',
            'telefono'=>'nullable|numeric|between:1,50',
            'clave'=>'nullable|string|between:1,50'
        ];
        $this->validate($request, $rules);
        $nombre = $request->input('nombre');
        $apellido = $request->input('apellido');
        $pagina_web = $request->input('pagina_web');
        $correo = $request->input('correo');
        $telefono = $request->input('telefono');
        //$clave=$request->input('clave');

        $tipos = DB::select('select cli_tipo from Cliente where cli_id = :id', ['id'=>$id]);
        $tipo=$tipos[0]->cli_tipo;
        
        if ($telefono == NULL){
            DB::update('update Cliente set cli_nombre = ?, cli_apellido=? ,cli_pagina_web=?, cli_correo=? where cli_id= ?', 
            [$nombre,$apellido,$pagina_web,$correo,$id]);
        }
        else if ($telefono != NULL && ($nombre==NULL && $apellido ==NULL && $pagina_web==NULL && $correo==NULL && $clave)){
            DB::update('update Telefono set tel_numero =? where fkCliente = ?',[$telefono,$id]);
        }
        else {
            DB::update('update Cliente set cli_nombre = ?, cli_apellido=? ,cli_pagina_web=?, cli_correo=? where cli_id= ?', 
            [$nombre,$apellido,$pagina_web,$correo,$id]);
            DB::update('update Telefono set tel_numero =? where fkCliente = ?',[$telefono,$id]);
        }
        return redirect()->action('ClientsController@index')->with('success','El cliente fue editado');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        DB::delete('delete from cli_lug where fkCliente = :id', ['id'=>$id]);
        DB::delete('delete from usuario where fkCliente = :id', ['id'=>$id]);
        DB::delete('delete from contacto where fkCliente = :id', ['id'=>$id]);
        DB::delete('delete from telefono where fkCliente = :id', ['id'=>$id]);
        DB::delete('delete from pedido where fkCliente = :id', ['id'=>$id]);
        DB::delete('delete from metodo_pago where fkCliente = :id', ['id'=>$id]);
        DB::delete('delete from cliente where Cli_id = :id ', ['id'=>$id]);
        return redirect()->action('ClientsController@index')->with('success','El cliente fue eliminado');
    }
}
