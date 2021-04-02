<div class='btn-group btn-group-sm'>
  @can('markets.show')
  <a data-toggle="tooltip" data-placement="bottom" title="{{trans('lang.view_details')}}" href="{{ route('markets.show', $id) }}" class='btn btn-link'>
    <i class="fa fa-eye"></i>
  </a>
  @endcan

  @can('markets.edit')
  <a data-toggle="tooltip" data-placement="bottom" title="{{trans('lang.market_edit')}}" href="{{ route('markets.edit', $id) }}" class='btn btn-link'>
    <i class="fa fa-edit"></i>
  </a>
  @endcan

  @can('markets.deleteAllProducts')
  {!! Form::open(['route' => ['markets.deleteAllProducts', $id], 'method' => 'post']) !!}
  {!! Form::button('<i class="fa fa-warning" style="color: orangered;"></i>', [
  'type' => 'submit',
  'class' => 'btn btn-link text-danger',
  'onclick' => "return confirm('Are you sure you want to delete all products of this market?')"
  ]) !!}
{!! Form::close() !!}
  @endcan

  @can('markets.destroy')
{!! Form::open(['route' => ['markets.destroy', $id], 'method' => 'delete']) !!}
  {!! Form::button('<i class="fa fa-trash"></i>', [
  'type' => 'submit',
  'class' => 'btn btn-link text-danger',
  'onclick' => "return confirm('Are you sure?')"
  ]) !!}
{!! Form::close() !!}
  @endcan
</div>
