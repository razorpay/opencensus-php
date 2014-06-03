$(document).ready(function()
{
    rzpd = {};

    rzpd.colors = {
        blue: '#29B7D6',
        grey: '#999999'
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
            // if ($scope.intervalValue == 'day') {
            //     intv = null;
            // }
            // if ($scope.intervalValue == 'week') {
            //     intv = 24 * 3600 * 7 * 1000;
            // }
            // if ($scope.intervalValue == 'month') {
            //     intv = 24 * 3600 * 30 * 1000;
            // }

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
                    lineColor : '#292929',
                    labels : {
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

        renderDatepickers: function()
        {
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

        plotTransactionsChart: function(data, intv)
        {
            rzpd.views.setChart('transactions-line-chart', 'area', 'Transactions', [{'data': data}], {
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
                    // formatter : function() {
                    //     return '' + $.datepicker.formatDate('dd.mm.yy', new Date(this.x)) + ': ' + this.y + ' ' + $scope.activeCurrencySymbol;
                    // }
                    pointFormat: '<b>₹{point.y:,.0f}</b>',
                    dateTimeLabelFormats: {
                        day : '%b %Y'
                    }
                }
            });
        }
    };

    rzpd.hooks = {

        toggleLivemode: function() {
            rzpd.views.toggleLivemode();
        },

        changeGraphInterval: function() {
            rzpd.views.changeGraphInterval($(this));
        },

        plotTransactionsChart: function() {
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
                        dat = new Date(date[0], parseInt(date[1] - 1), date[2]);

                        chartData.push([dat.getTime(),parseInt(s.amount)/100]);
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
                    rzpd.views.plotTransactionsChart(chartData, intv);
                }
            });
        }

    };

    /* DASHBOARD event listeners */

    $('#livemode .button-wrap').click(rzpd.hooks.toggleLivemode);
    
    $('.btn-group .btn').click(rzpd.hooks.changeGraphInterval);

    if (document.getElementById("datepicker-group"))
        rzpd.views.renderDatepickers();

    if (document.getElementById("transactions-line-chart"))
        rzpd.hooks.plotTransactionsChart();
});