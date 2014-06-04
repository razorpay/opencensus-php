$(document).ready(function()
{
    rzpd = {};

    rzpd.colors = {
        blue: '#29B7D6',
        grey: '#999999'
    };

    rzpd.tabs = ['dashboard','payments','customers','transfers','recipients','plans','logs'];

    rzpd.config = {
        intervalValue: 'day'
    };

    rzpd.views = {
        toggleLivemode: function() {
            $('#livemode .button-wrap').toggleClass("button-active");
            $('.button-desc').toggleClass('active');
        },

        changeGraphInterval: function(el) {
            $('.btn-group .btn').removeClass('active');
            el.addClass("active");
        },

        setChart: function(ChartDiv, ChartType, ChartTitle, ChartData, ChartOptions) {
            if (rzpd.config.intervalValue == 'day') {
                intv = null;
            }
            if (rzpd.config.intervalValue == 'week') {
                intv = 24 * 3600 * 7 * 1000;
            }
            if (rzpd.config.intervalValue == 'month') {
                intv = 24 * 3600 * 30 * 1000;
            }

            settings = {
                chart : {
                    renderTo : ChartDiv,
                    type : ChartType,
                    height : '250',
                    spacingLeft : 0,
                    spacingRight: 20

                },
                title : {
                    text : ChartTitle,
                    align : 'left',
                    style : {
                        color : rzpd.colors.grey,
                        fontSize : '15px',
                        fontFamily : '"Lato", sans-serif'
                    }
                },
                xAxis : {
                    type : 'datetime',
                    tickInterval : intv,
                    endOnTick : true,
                    dateTimeLabelFormats : {
                        second : '%H:%M',
                        minute : '%H:%M',
                        hour : '%H:%M',
                        day : '%e. %b',
                        week : '%d.%m',
                        month : '%b',
                        year : '%Y'
                    },
                    lineColor : rzpd.colors.grey,
                    labels : {
                        staggerLines: staggerLinesVal,
                        style : {
                            fontFamily : '"Lato", sans-serif',
                            fontWeight : 'bold'

                        }
                    }
                },
                yAxis : {
                    lineColor : rzpd.colors.grey,
                    style : {
                        fontFamily : '"Lato", sans-serif',
                        fontWeight : 'bold'
                    }
                },
                plotOptions : {
                    area : {
                        fillOpacity : 0.1,
                        lineWidth : 2,
                        states : {
                            hover: {
                                lineWidth: 2
                            }
                        },
                        marker : {
                            radius : 3,
                            lineWidth: 2,
                            states: {
                                hover: {
                                    lineWidth: 2
                                }
                            }
                        }
                    }
                },
                colors : [rzpd.colors.blue],
                legend : {
                    enabled : false
                },
                credits : {
                    enabled : false
                },
                series : ChartData
            };


            settings = jQuery.extend(settings, ChartOptions);

            new Highcharts.Chart(settings);
        },

        renderDatepickers: function() {
            rzpd.views.date_start = new Pikaday({
                field: document.getElementById('date-start'),
                firstDay: 1,
                defaultDate: new Date(),
                setDefaultDate: true,
                yearRange: [2014,2020],
                onClose: function() {
                    console.log(this.getMoment().format('Do MMMM YYYY'));
                }
            });

            rzpd.views.date_end = new Pikaday({
                field: document.getElementById('date-end'),
                firstDay: 1,
                defaultDate: new Date(),
                setDefaultDate: true,
                yearRange: [2014,2020],
                onClose: function() {
                    console.log(this.getMoment().format('Do MMMM YYYY'));
                }
            });
        },

        plotTransactionsChart: function(data) {
            rzpd.views.setChart('transactions-line-chart', 'area', 'Transactions', [{'data': data}], {
                yAxis : {
                    title : {
                        text : ''
                    },
                    labels : {
                        formatter : function() {
                            return '₹' + this.value;
                        },
                        style : {
                            fontFamily : '"Lato", sans-serif',
                            fontWeight : 'bold'
                        }
                    },
                    gridLineColor : '#d9d9d9',
                    gridLineWidth : '1',
                    style : {
                        fontFamily : '"Lato", sans-serif',
                        fontWeight : 'bold'
                    }
                },
                tooltip : {
                    pointFormat: '<b>₹{point.y:,.0f}</b>',
                    dateTimeLabelFormats: {
                        second : '%H:%M',
                        minute : '%H:%M',
                        hour : '%H:%M',
                        day : '%e %b',
                        week : '%d.%m',
                        month : '%b',
                        year : '%Y'
                    }
                }
            });
        },

        showLoader: function() {
            $('#loader').removeClass('hidden');
        },

        hideLoader: function() {
            $('#loader').addClass('hidden');
        },

        showDiv: function(div) {
            $(div).removeClass('hidden');
        },

        hideDiv: function(div) {
            $(div).addClass('hidden');
        },

        hidePanels: function(div) {
            rzpd.views.hideDiv('.panel');
        },

        changePanel: function() {
            rzpd.views.hidePanels();
            rzpd.views.showLoader();
        }
    };

    rzpd.hooks = {

        toggleLivemode: function() {
            rzpd.views.toggleLivemode();
        },

        changeGraphInterval: function() {
            rzpd.views.changeGraphInterval($(this));
        },

        plotTransactionsChart: function(parentDiv) {
            $.ajax({
                url: './sample.json',
                type: 'GET',
                success: function(result){
                    var group = "day";
                    var chartData = [];
                    var chartDataTest = [];
                    $(result.data).each(function(i, s) {
                        date_hour = s.created_at.split(' ');
                        date = date_hour[0].split('-');
                        chartData.push([Date.UTC(date[0], parseInt(date[1] - 1), date[2]),parseInt(s.amount)/100]);
                    });

                    staggerLinesVal = 1;
                    if (group == 'day') {
                        intv = null;
                    }
                    if (group == 'week') {
                        intv = 24 * 3600 * 7 * 1000;
                        if( chartData.length > 14 ) {
                            staggerLinesVal = 2;
                            if( chartData.length > 28 ) {
                                intv = 48 * 3600 * 7 * 1000;
                            }
                        }
                    }
                    if (group == 'month') {
                        intv = 24 * 3600 * 30 * 1000;
                    }
                    if (group == 'year') {
                        intv = 24 * 3600 * 30 * 12 * 1000;
                    }

                    rzpd.config.intv = intv;

                    rzpd.views.hideLoader();
                    rzpd.views.showDiv(parentDiv);
                    rzpd.views.plotTransactionsChart(chartData);
                }
            });
        },

        renderDashboard: function() {
            rzpd.hooks.plotTransactionsChart('#dashboard');
        }

    };

    /* Event listeners */

    $('#livemode .button-wrap').click(rzpd.hooks.toggleLivemode);
    $('.btn-group .btn').click(rzpd.hooks.changeGraphInterval);
    if (document.getElementById("datepicker-group"))
        rzpd.views.renderDatepickers();

    /* Routing (using path.js) */

    Path.map("#!/").to(function(){
        rzpd.hooks.renderDashboard();
    }).enter(rzpd.views.changePanel);

    Path.map("#!/payments").to(function(){
        
    }).enter(rzpd.views.changePanel);

    Path.map("#!/customers").to(function(){
        
    }).enter(rzpd.views.changePanel);


    Path.root("#!/");

    Path.listen();
});