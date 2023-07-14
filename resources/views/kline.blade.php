<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8"><link rel="icon" href="https://jscdn.com.cn/highcharts/images/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* css 代码  */
    </style>
    <script src="https://code.highcharts.com.cn/jquery/jquery-1.8.3.min.js"></script>
    <script src="https://code.highcharts.com.cn/highstock/highstock.js"></script>
    <script src="https://code.highcharts.com.cn/highcharts/modules/exporting.js"></script>
    <script src="https://code.highcharts.com.cn/highcharts-plugins/highcharts-zh_CN.js"></script>
    <script src="https://code.highcharts.com.cn/highcharts/themes/dark-unica.js"></script>

   
</head>
<body>
<div id="container" style="min-width:400px;height:400px"></div>
<script>
    /*$.getJSON('http://www.lara.com/data?callback=?', function (data) {
        // create the chart
        Highcharts.stockChart('container', {
            rangeSelector : {
                buttons : [{
                    count: 1,
                    type: 'minute',
                    text: '1M'
                }, {
                    count: 5,
                    type: 'minute',
                    text: '5M'
                },{
                    type : 'hour',
                    count : 1,
                    text : '1h'
                }, {
                    type : 'day',
                    count : 1,
                    text : '1D'
                }, {
                    type : 'all',
                    count : 1,
                    text : 'All'
                }],
                selected : 0
            },
            title : {
                text : 'AAPL Stock Price'
            },
            series : [{
                type : 'candlestick',
                name : 'AAPL Stock Price',
                data : data,
                color: 'green',
                lineColor: 'green',
                upColor: 'red',
                upLineColor: 'red',
                navigatorOptions: {
                    color: Highcharts.getOptions().colors[0]
                },
                dataGrouping : {
                    units : [
                        [
                            'week', // unit name
                            [1] // allowed multiples
                        ], [
                            'month',
                            [1]
                        ]
                    ]
                }
            }]
        });
    });*/

    $.getJSON('http://127.0.0.1:8000/data', function (data) {
        // create the chart
        Highcharts.stockChart('container', {
            title: {
                text: 'AAPL stock price by minute'
            },
            rangeSelector : {
                buttons : [{
                    count: 1,
                    type: 'minute',
                    text: '1M'
                }, {
                    count: 5,
                    type: 'minute',
                    text: '5M'
                },{
                    type : 'hour',
                    count : 1,
                    text : '1h'
                }, {
                    type : 'day',
                    count : 1,
                    text : '1D'
                }, {
                    type : 'all',
                    count : 1,
                    text : 'All'
                }],
                selected : 0,
                inputEnabled : false
            },
            tooltip: {
                split: false,
                valueDecimals: 5,
            },
            series : [{
                name : 'AAPL',
                type: 'candlestick',
                color: 'red',
                lineColor: 'red',
                upColor: 'green',
                upLineColor: 'green',
                decimals : 5,
                data : data,
                tooltip: {
                    valueDecimals: 2
                }
            }]
        });
    });
</script>
</body>
</html>
