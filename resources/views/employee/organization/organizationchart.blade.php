@extends('templates.monster.main')
@push('after-styles')
<link href="{{ asset('css/jquery.orgchart.css') }}" rel="stylesheet">
<style type="text/css">
.orgchart {
    background: #fff;
}

i.edge {
    display: none;
}

#chart-container {
    position: relative;
    display: inline-block;
    top: 10px;
    left: 10px;
    height: 420px;
    width: calc(100% - 24px);
    border: 2px dashed #aaa;
    border-radius: 5px;
    overflow: auto;
    text-align: center;
}
</style>

@endpush
@section('content')
@php
$chartData = isset( $orgChartDataData ) && !empty($orgChartDataData) ? json_encode($orgChartDataData, true) : '[{}]';
$chartData = substr($chartData, 1, -1);
@endphp
<div class="row">
    <div class="card" style="width:100% !important;">
        <div id="chart-container"></div>
    </div>
</div>
@endsection
@push('after-scripts')
<script src="/js/jquery.mockjax.min.js"></script>
<script src="/js/jquery.orgchart.js"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {
    var datascource = '<?php echo $chartData; ?>';
    //console.log( datascource );
    $.mockjax({
        url: '/orgchart/orgchartdata',
        responseTime: 100,
        contentType: 'application/json',
        responseText: datascource
    });
    var oc = $('#chart-container').orgchart({
        //'data' : '<?php //echo $chartData;?>',  
        'data': '/orgchart/orgchartdata',
        'nodeId': 'id',
        'nodeContent': 'title',
        'draggable': true,
        'dropCriteria': function($draggedNode, $dragZone, $dropZone) {
            if ($draggedNode.find('.content').text().indexOf('manager') > -1 && $dropZone.find(
                    '.content').text().indexOf('engineer') > -1) {
                return false;
            }
            return true;
        }
    });

    oc.$chart.on('nodedrop.orgchart', function(event, extraParams) {
        //console.log(event  )
        if (event.type == "nodedrop") {
            //console.log(extraParams.dropZone.children('.title').text())
            //console.log(extraParams.dragZone )
            //console.log("ID: " + extraParams.draggedNode.children('.id').text() )
            var _parentID = extraParams.dropZone[0].id;
            var _currentID = extraParams.draggedNode[0].id;
            if (_parentID !== undefined && _currentID !== undefined) {
                //console.log("ParentID: " + _parentID)
                //console.log("currentID: " + _currentID )
                _updateUserOrder(_currentID, _parentID); //update user 
            }
        }

        //console.log('draggedNode:' + extraParams.draggedNode.children('.title').text()
        //+ ', dragZone:' + extraParams.dragZone.children('.title').text()
        //+ ', dropZone:' + extraParams.dropZone.children('.title').text()
        //);

    });


    //ajax update user data          
    function _updateUserOrder(userId, parentId) {
        if (userId == null || parentId == null) return;
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: '{{ url("ansatte/organization/updateUserOrder") }}',
            data: {
                'userId': userId,
                'parentId': parentId,
            },
        }).done(function(response) {
            console.log(response)
        });
    }

});
</script>
@endpush