<?php

namespace App\DataTables;

use App\Models\ServiceFees;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Html\Editor\Editor;

class ServiceFeesDataTable extends DataTable
{
    /**
     * custom fields columns
     * @var array
     */
    public static $customFields = [];

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', 'service_fees.datatables_actions');
            // ->rawColumns(array_merge($columns, ['action']));
        // $dataTable = new EloquentDataTable($query);
        // Storage::put('file12aasds.txt',$model->newQuery());;
        // $columns = array_column($this->getColumns(), 'data');
        // $dataTable = $dataTable
        //     ->editColumn('service_fees', function ($serviceFees) {
        //         return getMediaColumn($serviceFees, 'service_fees');
        //     })
        //     // ->editColumn('price', function ($product) {
        //     //     return getPriceColumn($product);
        //     // })
        //     // ->editColumn('discount_price', function ($product) {
        //     //     return getPriceColumn($product,'discount_price');
        //     // })
        //     ->editColumn('created_at', function ($serviceFees) {
        //         return getDateColumn($serviceFees, 'created_at');
        //     })
        //     ->editColumn('updated_at', function ($serviceFees) {
        //         return getDateColumn($serviceFees, 'updated_at');
        //     })
        //     ->editColumn('featured', function ($serviceFees) {
        //         return getBooleanColumn($serviceFees, 'featured');
        //     })
        //     ->addColumn('action', 'service_fees.datatables_actions')
        //     ->rawColumns(array_merge($columns, ['action']));

        // return $dataTable;
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\ServiceFees $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ServiceFees $model)
    {
        Storage::put('file12as.txt',$model->newQuery());
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('servicefees-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->dom('Bfrtip')
                    ->orderBy(1)
                    ->buttons(
                        Button::make('create'),
                        Button::make('export'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('id'),
            Column::make('service_fees'),
            Column::make('created_at'),
            Column::make('updated_at'),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->addClass('text-center')
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'ServiceFees_' . date('YmdHis');
    }
}
