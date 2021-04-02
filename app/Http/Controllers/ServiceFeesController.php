<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\ServiceFeesDataTable;
use App\Models\ServiceFees;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;


class ServiceFeesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ServiceFeesDataTable $serviceFeesDataTable)
    {
        //
        // get all the sharks

        // load the view and pass the sharks
        // return View('service_fees.index')
        //     ->with('serviceFees', $serviceFees);
        return $serviceFeesDataTable->render('service_fees.index');
            // return view('service_fees.index', $serviceFees);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return View::make('service_fees.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validate
        // read more on validation at http://laravel.com/docs/validation
        $rules = array(
            'service_fees'       => 'required'
        );
        $validator = Validator::make(Input::all(), $rules);

        // process the login
        if ($validator->fails()) {
            return Redirect::to('serviceFees/create')
                ->withErrors($validator)
                ->withInput(Input::except('password'));
        } else {
            // store
            $serviceFees = new ServiceFees;
            $serviceFees->service_fees       = Input::get('service_fees');
            $serviceFees->save();

            // redirect
            Session::flash('message', 'Successfully created shark!');
            return Redirect::to('serviceFees');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // get the shark
        $serviceFees = ServiceFees::find($id);

        // show the view and pass the shark to it
        return View::make('service_fees.show')
            ->with('serviceFees', $serviceFees);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        // get the shark
        $serviceFees = ServiceFees::find($id);

        // show the edit form and pass the shark
        return View::make('service_fees.edit')
            ->with('serviceFees', $serviceFees);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rules = array(
            'service_fees'       => 'required'
        );
        $validator = Validator::make(Input::all(), $rules);

        // process the login
        if ($validator->fails()) {
            return Redirect::to('serviceFees/' . $id . '/edit')
                ->withErrors($validator)
                ->withInput(Input::except('password'));
        } else {
            // store
            $serviceFees = ServiceFees::find($id);
            $serviceFees->service_fees       = Input::get('service_fees');
            $serviceFees->save();

            // redirect
            Session::flash('message', 'Successfully updated shark!');
            return Redirect::to('serviceFees');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // delete
        $serviceFees = ServiceFees::find($id);
        $serviceFees->delete();

        // redirect
        Session::flash('message', 'Successfully deleted the service fees!');
        return Redirect::to('serviceFees');
    }
}
