<div class='btn-group btn-group-sm'>
  @can('serviceFees.show')
  <!-- {{trans('lang.view_details')}} -->
  <a data-toggle="tooltip" data-placement="bottom" title="AB" href="{{ route('serviceFees.show', $id) }}" class='btn btn-link'>
    <i class="fa fa-eye"></i>
  </a>
  @endcan

  @can('serviceFees.edit')
  <!-- {{trans('lang.product_edit')}} -->
  <a data-toggle="tooltip" data-placement="bottom" title="CD" href="{{ route('serviceFees.edit', $id) }}" class='btn btn-link'>
    <i class="fa fa-edit"></i>
  </a>
  @endcan

  @can('serviceFees.destroy')
{!! Form::open(['route' => ['serviceFees.destroy', $id], 'method' => 'delete']) !!}
  {!! Form::button('<i class="fa fa-trash"></i>', [
  'type' => 'submit',
  'class' => 'btn btn-link text-danger',
  'onclick' => "return confirm('Are you sure?')"
  ]) !!}
{!! Form::close() !!}
  @endcan
</div>