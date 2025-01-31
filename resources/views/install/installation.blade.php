@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => [
        'welcome' => 'selected done',
        'requirements' => 'selected done',
        'permissions' => 'selected done',
        'database' => 'selected done',
        'installation' => 'selected'
    ]])

    <form action="{{ route('install.install') }}" method="POST">
        @csrf
        <div class="step-content">
            <h3>Installation</h3>
            <hr>
            <p>EduResultChain is ready to be installed!</p>
            <p>Before you proceed, please provide the name for your application below:</p>
            <div class="form-group">
                <label for="app_name">App Name</label>
                <input type="text" class="form-control" id="app_name" name="app_name" value="EduResultChain">
            </div>
            <button class="btn btn-green pull-right" data-toggle="loader" data-loading-text="Installing" type="submit">
                <i class="fa fa-play"></i>
                Install
            </button>
            <div class="clearfix"></div>
        </div>
    </form>
@stop
