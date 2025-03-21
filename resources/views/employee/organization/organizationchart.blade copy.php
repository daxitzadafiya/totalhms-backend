@extends('templates.monster.main')

@push('after-styles')
@endpush
@section('content')
<div class="row">
        <div class="card" style="width:100% !important;">
            <div id="tree" style="width:100% !important"></div>
        </div>
     </div>
        @endsection
        @push('after-scripts')
        <script src="/js/org-chart.js"></script>
        <script>
            window.onload = function() {
                var chart = new OrgChart(document.getElementById("tree"), {
                    template: "derek",
                    enableDragDrop: true,
                    toolbar: true,
                    menu: {
                        pdf: {
                            text: "Export PDF"
                        },
                        png: {
                            text: "Export PNG"
                        },
                        svg: {
                            text: "Export SVG"
                        },
                        csv: {
                            text: "Export CSV"
                        }
                    },
                    nodeMenu: {
                        details: {
                            text: "Details"
                        },
                        add: {
                            text: "Add New"
                        },
                        edit: {
                            text: "Edit"
                        },
                        remove: {
                            text: "Remove"
                        },
                    },
                    nodeBinding: {
                        field_0: "name",
                        field_1: "title",
                        img_0: "img",
                        field_number_children: "field_number_children"
                    },
                    nodes: [{
                            id: 1,
                            name: "Denny Curtis",
                            title: "CEO",
                            img: "https://balkangraph.com/js/img/2.jpg"
                        },
                        {
                            id: 2,
                            pid: 1,
                            name: "Ashley Barnett",
                            title: "Sales Manager",
                            img: "https://balkangraph.com/js/img/3.jpg"
                        },
                        {
                            id: 3,
                            pid: 1,
                            name: "Caden Ellison",
                            title: "Dev Manager",
                            img: "https://balkangraph.com/js/img/4.jpg"
                        },
                        {
                            id: 4,
                            pid: 1,
                            name: "Elliot Patel",
                            title: "Sales",
                            img: "https://balkangraph.com/js/img/5.jpg"
                        },
                        {
                            id: 5,
                            pid: 1,
                            name: "Lynn Hussain",
                            title: "Sales",
                            img: "https://balkangraph.com/js/img/6.jpg"
                        },
                        {
                            id: 6,
                            pid: 1,
                            name: "Tanner May",
                            title: "Developer",
                            img: "https://balkangraph.com/js/img/7.jpg"
                        },
                        {
                            id: 7,
                            pid: 3,
                            name: "Fran Parsons",
                            title: "Developer",
                            img: "https://balkangraph.com/js/img/8.jpg"
                        }
                    ]
                });
            };
        </script>
        @endpush