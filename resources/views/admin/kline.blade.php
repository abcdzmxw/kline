<!DOCTYPE html>
<html lang="cn">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>风控</title>
     <link href="https://server.gtcoin.one/js/libs/element-ui/2.15.0/theme-chalk/index.min.css" rel="stylesheet">
    <script src="https://server.gtcoin.one/js/libs/echarts/5.0.1/echarts.min.js"></script>
    <script src="https://server.gtcoin.one/js/libs/vue/2.6.9/vue.min.js"></script>
    <script src="https://server.gtcoin.one/js/libs/element-ui/2.15.0/index.js"></script>
    <script src="https://server.gtcoin.one/js/libs/axios/0.21.1/axios.min.js"></script>
    <style>
        body {
            background: #4CA1AF;
            /* fallback for old browsers */
            background: -webkit-linear-gradient(to right, #C4E0E5, #4CA1AF);
            /* Chrome 10-25, Safari 5.1-6 */
            background: linear-gradient(to right, #C4E0E5, #4CA1AF);
            /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
        }

        .chart1 {
            height: 400px;
            border: 1px solid #996;
        }
        .chart2{
            height: 400px;
            border: 1px solid #996;
        }
        .form {
            margin: 20px 0;
        }

        .s-input {
            width: 100px;
        }
        .kline-button{
            padding-top: 5px;
        }
    </style>
</head>

<body>
<div id="app">
    <div class="form">
        <el-date-picker v-model="day" type="date" @change="changeDate" placeholder="选择日期">
        </el-date-picker>

        <el-select name="" @change="changeSymbol" placeholder="请选择币种" v-model="coin" >
         <!--   <el-option :value="1" label="AMC/USDT"></el-option>  -->
           
            <el-option :value="1" label="DOE/USDT"></el-option>
             <el-option :value="2" label="MOR/USDT"></el-option>
            <!--    <el-option :value="3" label="XNB/USDT"></el-option>  -->
            <!--    <el-option :value="4" label="AMC/USDT"></el-option>  -->
        
        </el-select>

        <el-select name="" @change="changePeriod" placeholder="请选择周期" v-model="period" >
            <el-option :value="1" label="1分钟"></el-option>
            {{--            <el-option :value="5" label="5分钟"></el-option>--}}
            {{--            <el-option :value="15" label="15分钟"></el-option>--}}
            <el-option :value="30" label="30分钟"></el-option>
            {{--            <el-option :value="60" label="1小时"></el-option>--}}
        </el-select>

        开：<el-input class="s-input" type="text" v-model="open" @change="setLine" placeholder="开盘价"></el-input>
        收：<el-input class="s-input" type="text" v-model="close" @change="setLine" placeholder="收盘价"></el-input>
        低：<el-input class="s-input" type="text" v-model="low" @change="setLine" placeholder="最低价"></el-input>
        高：<el-input class="s-input" type="text" v-model="high" @change="setLine" placeholder="最高价"></el-input>
        最小市值：<el-input class="s-input" type="text" v-model="min_amount" @change="setLine" placeholder="最小市值"></el-input>
        最大市值：<el-input class="s-input" type="text" v-model="max_amount" @change="setLine" placeholder="最大市值"></el-input>

        {{--        <el-button type="primary" plain @click="getKlineData">获取K线</el-button>--}}
        <el-button type="success" plain @click="saveLine" :loading="saveLoading">保存</el-button>
        <el-button type="info" plain @click="generateKline" :loading="generateLoading">生成K线</el-button>

    </div>
    <div class="chart1" id="chart1" v-loading="loading1"></div>
    <div class="chart2" id="chart2" v-loading="loading2"></div>
</div>
<script>
    Date.prototype.Format = function (fmt) { //author: meizz
        var o = {
            "M+": this.getMonth() + 1,                      //月份
            "d+": this.getDate(),                           //日
            "h+": this.getHours(),                          //小时
            "m+": this.getMinutes(),                        //分
            "s+": this.getSeconds(),                        //秒
            "q+": Math.floor((this.getMonth() + 3) / 3),    //季度
            "S": this.getMilliseconds()                     //毫秒
        };
        if (/(y+)/.test(fmt))
            fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
        for (var k in o)
            if (new RegExp("(" + k + ")").test(fmt))
                fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
        return fmt;
    }
    axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

    /**
     * Next we will register the CSRF Token as a common header with Axios so that
     * all outgoing HTTP requests automatically have it attached. This is just
     * a simple convenience so we don"t have to attach every token manually.
     */

    window.csrf_token = document.head.querySelector('meta[name="csrf-token"]');

    if (csrf_token) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf_token.content;
    } else {
        console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
    }

    axios.defaults.baseURL = '/admin';

    function axiosRequest(method, url, params){
        return new Promise((resolve, reject) => {
            axios({
                method: method,
                url: url,
                data: method === "POST" || method === "PUT" || method==="PATCH"? params : null,
                params: method === "GET" || method === "DELETE" ? params : null,
                withCredentials: true,
                cache:false
            }).then(function (result) {
                resolve(result.data);
            }).catch(function (error) {
                console.log(error);
            });
        });
    }
    new Vue({
        el: '#app',
        data: {
            loading1:false,
            loading2:false,
            saveLoading:false,
            generateLoading:false,
            pointData: [],
            klineFormData: [],
            klineMockXData:[],
            klineMockYData:[],
            myChart: undefined,
            klineDom: undefined,
            pricePencent: 0.1,

            day: new Date(),
            coin: 1,
            period: 30,
            open: '',
            close: '',
            low: '',
            high: '',
            min_amount: '',
            max_amount: ''
        },
        mounted() {
            this.getKlineConfig();
        },
        methods: {
            //时间格式化
            formatTime(dt){
                var year = dt.getFullYear(); //年
                var month = dt.getMonth() +1; //月
                var date = dt.getDate(); //日
                month = month < 10 ? "0" + month : month;
                date  = date <10 ? "0" + date : date;
                var str = year + "-" + month + "-" + date + " 00:00:00";
                return str;
            },
            changeDate() {
                console.log(this.day)
                this.getKlineConfig();
            },
            changePeriod(){
                this.getKlineConfig();
            },
            changeSymbol(){
                this.getKlineConfig();
            },
            getKlineConfig(){
                var date = this.formatTime(this.day)
                console.log({date})
                axiosRequest('GET','/getKlineConfig',{"datetime":date,"period":this.period,"coin":this.coin}).then(response=>{
                    console.log(response);
                    if(response.config){
                        this.open = response.config.open;
                        this.close = response.config.close;
                        this.low = response.config.low;
                        this.high = response.config.high;
                        this.min_amount = response.config.min_amount;
                        this.max_amount = response.config.max_amount;
                    }else{
                        this.open = '';
                        this.close = '';
                        this.low = '';
                        this.high = '';
                        this.min_amount = '';
                        this.max_amount = '';
                    }

                    this.max = response.max.toFixed(5);
                    this.min = response.min.toFixed(5);
                    this.pointData = response.lists;
                    this.klineFormData = response.klineFormData;
                    this.klineMockXData = response.dateKline// 时间线
                    this.klineMockYData = response.klineData// 对应数据
                    this.initLine();
                    this.initKline();
                    this.setLine();
                });
            },

            initLine() {
                this.myChart = window.echarts.init(document.getElementById('chart1'));
                let onPointDragging = this.onPointDragging;
                let showTooltip = this.showTooltip;
                let hideTooltip = this.hideTooltip;
                let updatePosition = this.updatePosition;
                let renderDraw = this.renderDraw;

                setTimeout(function () {
                    renderDraw()
                }, 10)
                window.addEventListener('resize', updatePosition);
                this.myChart.on('dataZoom', updatePosition);
            },
            onPointDragging(dataIndex, pos) {
                let data = this.pointData;
                // console.log({data})
                var position = this.myChart.convertFromPixel('grid', pos)
                data[dataIndex] = [data[dataIndex][0], position[1]];
                this.klineMockYData[dataIndex][1] = this.pointData[dataIndex][1];
                let nextIndex = dataIndex+1;
                if(typeof this.klineMockYData[nextIndex] === 'object'){
                    this.klineMockYData[nextIndex][0] = this.pointData[dataIndex][1];
                }

                // update klineFormData
                this.klineFormData[dataIndex].close = this.pointData[dataIndex][1];
                if(this.klineFormData[nextIndex]){
                    this.klineFormData[nextIndex].open = this.pointData[dataIndex][1];
                }

                console.log({"kfd":this.klineFormData})

                // Update data
                this.myChart.setOption({
                    series: [{
                        id: 'chart1',
                        data: data
                    }]
                });
                this.klineDom.setOption({
                    series: [{
                        type: 'k',
                        data: this.klineMockYData
                    }]
                });
            },
            updatePosition() {
                var self = this
                this.myChart.setOption({
                    graphic: this.pointData.map(function (item, dataIndex) {
                        return {
                            position: self.myChart.convertToPixel('grid', item)
                        };
                    })
                });
            },
            showTooltip(dataIndex) {
                this.myChart.dispatchAction({
                    type: 'showTip',
                    seriesIndex: 0,
                    dataIndex: dataIndex
                });
            },
            hideTooltip(dataIndex) {
                this.myChart.dispatchAction({
                    type: 'hideTip'
                });
            },
            renderDraw() {
                let self = this;
                let onPointDragging = this.onPointDragging;
                let showTooltip = this.showTooltip;
                let hideTooltip = this.hideTooltip;
                let updatePosition = this.updatePosition;
                let renderDraw = this.renderDraw;
                this.myChart.setOption({
                    graphic: this.pointData.map(function (item, dataIndex) {
                        return {
                            type: 'circle',
                            position: self.myChart.convertToPixel('grid', item),
                            shape: {
                                cx: 0,
                                cy: 0,
                                r: 5
                            },
                            invisible: true,
                            draggable: true,
                            ondrag: function (dx, dy) {
                                onPointDragging(dataIndex, [this.x, this.y]);
                            },
                            onmousemove: function () {
                                showTooltip(dataIndex);
                            },
                            onmouseout: function () {
                                hideTooltip(dataIndex);
                            },
                            ondragend: function () {
                                updatePosition()
                            },
                            z: 100
                        };
                    })
                });
            },

            generateKline(){
                this.generateLoading = true;
                var datetime = this.formatTime(this.day)
                var config = {
                    datetime: datetime,
                    open: this.open,
                    close: this.close,
                    low: this.low,
                    high: this.high,
                    min_amount: this.min_amount,
                    max_amount: this.max_amount
                }
                axiosRequest('GET','/generateKline',config).then(response=>{
                    console.log(response);
                    this.generateLoading = false;
                    this.max = response.max.toFixed(5);
                    this.min = response.min.toFixed(5);
                    this.pointData = response.lists;
                    this.klineFormData = response.klineFormData;
                    this.klineMockXData = response.dateKline// 时间线
                    this.klineMockYData = response.klineData// 对应数据
                    this.initLine();
                    this.initKline();
                    this.setLine();
                });
            },

            // 保存趋势线
            saveLine(){
                this.saveLoading = true;
                var datetime = this.formatTime(this.day)
                var data = {
                    "coin":this.coin,
                    "period":this.period,
                    datetime: datetime,
                    open: this.open,
                    close: this.close,
                    low: this.low,
                    high: this.high,
                    min_amount: this.min_amount,
                    max_amount: this.max_amount,
                    klineFormData: this.klineFormData
                }
                axiosRequest('POST','/kline-save',data).then(response=>{
                    this.saveLoading = false;
                    console.log(response)
                });
            },
            // 初始化走势线
            setLine() {
                /*let price = (this.max * 1 + this.min * 1) / 2;
                // 一天的长度
                let len = (86400000 / (this.period * 60 * 1000)).toFixed(8) * 1;
                let cuuretDay = new Date(this.day).getTime()
                // 生成默认数据
                let attr = new Array(len).fill('').map((item, idx) => [cuuretDay + (idx + 1) * this.period * 60 * 1000, price]);
                this.pointData = attr;*/
                var option;
                option = {
                    tooltip: {
                        triggerOn: 'none',
                        formatter: function (params) {
                            return '时间: ' + new Date(params.data[0]).Format('yyyy-MM-dd hh:mm:ss') + '<br>收盘: ' + params.data[1].toFixed(5);
                        }
                    },
                    dataZoom: [{
                        type: 'inside'
                    }],
                    grid: {
                        top: '4%',
                        bottom: '4%',
                        left: '4%',
                        right: '4%',
                    },
                    xAxis: {
                        type: 'time',
                        axisLine: { onZero: false }
                    },
                    yAxis: {
                        min: this.min,
                        max: this.max,
                        type: 'value',
                        axisLine: { onZero: false }
                    },
                    series: [
                        {
                            id: 'chart1',
                            type: 'line',
                            smooth: true,
                            symbolSize: 5,
                            data: this.pointData
                        }
                    ]
                };
                this.myChart.setOption(option, true);
                setTimeout(() => {
                    this.renderDraw();
                    this.initializeKLine();
                }, 30)

            },
            // -----预览K线----
            initKline() {
                this.klineDom = echarts.init(document.getElementById('chart2'));
                var that = this;
                this.klineDom.setOption({
                    backgroundColor: '#EDEDED',
                    xAxis: {
                        data: []
                    },
                    yAxis: {
                        max: this.max,
                        min: this.min,
                    },
                    series: [{
                        type: 'k',
                        data: [],
                    }],
                    grid: {
                        left: 30,
                        right: 20,
                        top: 10,
                        bottom: 30
                    },
                    dataZoom: [
                        {
                            type: 'inside',
                            xAxisIndex: [0, 1],
                            start: 10,
                            end: 100
                        },
                        {
                            show: true,
                            xAxisIndex: [0, 1],
                            type: 'slider',
                            bottom: 10,
                            start: 10,
                            end: 100
                        }
                    ],
                });
            },
            initializeKLine() {
                this.klineDom.setOption({
                    xAxis: {
                        data: this.klineMockXData
                    },
                    series: [{
                        type: 'k',
                        data: this.klineMockYData
                    }]
                });
            },
        },
    })

</script>
</body>

</html>
