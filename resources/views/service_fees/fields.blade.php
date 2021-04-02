<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">
<!-- Service Fee Field -->
    <div class="form-group row ">
        {!! Form::label('Service Fees', "", ['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::number('service_fees', null,  ['class' => 'form-control','placeholder'=>  "Insert Service Fees",'step'=>".1", 'min'=>"0"]) !!}
            <div class="form-text text-muted">
            Insert Service Fees
                <!-- {{ trans("lang.product_price_help") }} -->
            </div>
        </div>
    </div>

</div>

<!-- Submit Field -->
<div class="form-group col-12 text-right">
    <button type="submit" class="btn btn-{{setting('theme_color')}}"><i class="fa fa-save"></i> {{trans('lang.save')}} Service Fees</button>
    <a href="{!! route('serviceFees.index') !!}" class="btn btn-default"><i class="fa fa-undo"></i> {{trans('lang.cancel')}}</a>
</div>
