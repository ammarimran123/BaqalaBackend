@extends('layouts.app')
@push('css_lib')
<!-- iCheck -->
<link rel="stylesheet" href="{{asset('plugins/iCheck/flat/blue.css')}}">
<!-- select2 -->
<link rel="stylesheet" href="{{asset('plugins/select2/select2.min.css')}}">
<!-- bootstrap wysihtml5 - text editor -->
<link rel="stylesheet" href="{{asset('plugins/summernote/summernote-bs4.css')}}">
{{--dropzone--}}
<link rel="stylesheet" href="{{asset('plugins/dropzone/bootstrap.min.css')}}">
<!-- <style>
        .inputfile {
          width: 0.1px;
          height: 0.1px;
          opacity: 0;
          overflow: hidden;
          position: absolute;
          z-index: -1;
        }
        .inputfile + label {
            font-size: 1.25em;
            font-weight: 700;
            color: white;
            background-color: black;
            display: inline-block;
        }

        .inputfile:focus + label,
        .inputfile + label:hover {
            background-color: red;
        }
        .inputfile + label {
          cursor: pointer; /* "hand" cursor */
        }
      </style> -->
@endpush
@section('content')
<!-- Content Header (Page header) -->
<div id="first" style="display:none">
  <h1 style="text-align: center;margin-top: 40vh;">Please Wait..</h1>
  <div style="margin-left:auto; margin-right:auto;" class="loader"></div>

  <div id="loaderBar1" style="margin-left: auto; margin-right: auto; margin-top: 5%;" class="progress">
    <div id="bar" class="bar progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
      0% Uploaded
    </div>
  </div>

  <div id="loaderBar2" style="display:none;margin-left: auto; margin-right: auto; margin-top: 5%;" class="progress">
    <div id="bar2" class="bar2 progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
      0% Completed
    </div>
  </div>

  <div id="status"></div>
  <!-- <div id="results" style="border:1px solid #000; padding:10px; width:300px; height:250px; overflow:auto; background:#eee;"></div> -->
</div>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">{{trans('lang.product_plural')}}<small class="ml-3 mr-3">|</small><small>{{trans('lang.product_desc')}}</small></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{url('/dashboard')}}"><i class="fa fa-dashboard"></i> {{trans('lang.dashboard')}}</a></li>
          <li class="breadcrumb-item"><a href="{!! route('products.index') !!}">{{trans('lang.product_plural')}}</a>
          </li>
          <li class="breadcrumb-item active">{{trans('lang.product_create')}}</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->
<div class="content">
  <div class="clearfix"></div>
  @include('flash::message')
  @include('adminlte-templates::common.errors')
  <div class="clearfix"></div>
  <div class="card">
    <div class="card-header">
      <ul class="nav nav-tabs align-items-end card-header-tabs w-100">
        @can('products.index')
        <li class="nav-item">
          <a class="nav-link" href="{!! route('products.index') !!}"><i class="fa fa-list mr-2"></i>{{trans('lang.product_table')}}</a>
        </li>
        @endcan
        <li class="nav-item">
          <a class="nav-link active" href="{!! url()->current() !!}"><i class="fa fa-plus mr-2"></i>{{trans('lang.product_create')}}</a>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <div class="card-body">
        <style>
          #first {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            top: 0;
            opacity: 0.8;
            background-color: #000;
            color: #fff;
            z-index: 9999;
          }

          .loader {
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #28a745;
            width: 120px;
            height: 120px;
            -webkit-animation: spin 2s linear infinite;
            /* Safari */
            animation: spin 2s linear infinite;
          }

          @-webkit-keyframes spin {
            0% {
              -webkit-transform: rotate(0deg);
            }

            100% {
              -webkit-transform: rotate(360deg);
            }
          }

          @keyframes spin {
            0% {
              transform: rotate(0deg);
            }

            100% {
              transform: rotate(360deg);
            }
          }

          .progress {
            position: relative;
            width: 45%;
            height: 1.6rem;
            border: 1px solid #ddd;
            padding: 1px;
            border-radius: 3px;
          }

          

          .bar {
            justify-content: space-around;
            color: #343A40;
            background-color: #28a745;
            font-size: initial;
            font-weight: bolder;
            font-style: italic;
            width: 0%;
            border-radius: 3px;
          }

          .bar2 {
            justify-content: space-around;
            color: #343A40;
            background-color: #007bff;
            font-size: initial;
            font-weight: bolder;
            font-style: italic;
            width: 0%;
            border-radius: 3px;
          }

          .percent {
            position: absolute;
            display: inline-block;
            top: 3px;
            left: 48%;
          }
        </style>
        <label class="inputfile">Import From CSV</label>
        <form id="myform" method='post' action='uploadCSV' enctype='multipart/form-data'>
          {{ csrf_field() }}
          <input id="fileHolder" type='file' name='files[]' multiple>
          <input type='submit' name='submit' class="btn btn-{{setting('theme_color')}}" value='Import' onclick='runOnSubmit();'>
        </form>
        <br>
      </div>

      <div class="clearfix"></div>
      <hr>
      {!! Form::open(['route' => 'products.store']) !!}
      <div class="row">
        @include('products.fields')
      </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>
@include('layouts.media_modal')
@endsection
@push('scripts_lib')
<!-- iCheck -->
<script src="{{asset('plugins/iCheck/icheck.min.js')}}"></script>
<!-- select2 -->
<script src="{{asset('plugins/select2/select2.min.js')}}"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="{{asset('plugins/summernote/summernote-bs4.min.js')}}"></script>
 <!-- jQuery for Loader -->
<script src="http://malsup.github.com/jquery.form.js"></script>
<script>
  $(document).ready(function() {
    // setTimeout(() => {

      var bar = $('#bar');
      var l1 = $('#loaderBar1');
      var bar2 = $('#bar2');
      var l2 = $('#loaderBar2');
      var percent = $('.percent');
      var percent2 = $('.percent2');
      var status = $('#status');

      document.cookie = "importBarPercentage=0; path=/products";
      // console.log("Bar: ", bar);

      $('form[id="myform"]').ajaxForm({
        beforeSend: function() {
          status.empty();
          var percentVal = '0%';
          bar.width(percentVal)
          bar.html(percentVal+' Uploaded');
        },
        uploadProgress: function(event, position, total, percentComplete) {
          var percentVal = percentComplete + '%';
          bar.width(percentVal)
          bar.html(percentVal+' Uploaded');
        },
        success: function() {
          var percentVal = '100%';
          bar.width(percentVal)
          bar.html(percentVal+' Uploaded');
          /* l1.hide(1000);
          setInterval(() => {
            l2.show(1000);
            var cookie = getCookie('importBarPercentage');
            console.log("Cookie: ",cookie);
            bar2.width(percentVal)
            bar2.html(percentVal+' Completed');
          }, 500); */
        },
        complete: function(xhr) {
          // status.html(xhr.responseText);
          console.log(xhr);
            document.getElementById("first").style.display = "none";
            window.location.replace(window.location.origin + '/products');
        }
      });
    // }, 500);
  });
</script>
<script>
  function runOnSubmit() {
    if (document.getElementById('fileHolder').files.length != 2) {
      event.preventDefault();
      alert('Please select files to upload!');
      // document.getElementById("first").style.display = "block";
      // return false;
    } else {
      document.getElementById("first").style.display = "block";
      // return true;
    }
  }
</script>

{{--dropzone--}}
<script src="{{asset('plugins/dropzone/dropzone.js')}}"></script>
<script type="text/javascript">
  Dropzone.autoDiscover = false;
  var dropzoneFields = [];
</script>
@endpush