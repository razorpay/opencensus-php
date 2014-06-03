$(document).ready(function()
{
    rzpd = {};

    rzpd.views = {
        toggleLivemode: function() {
            $('#livemode .button-wrap').toggleClass("button-active");
            $('.button-desc').toggleClass('active-desc');
        },

        changeGraphInterval: function(el) {
            $('.btn-group .btn').removeClass('active');
            el.addClass("active");
        },

        setChart: function() {
            var setChart = function(ChartDiv, ChartType, ChartTitle, ChartData, ChartOptions) {
                if ($scope.intervalValue == 'day') {
                    intv = null;
                }
                if ($scope.intervalValue == 'week') {
                    intv = 24 * 3600 * 7 * 1000;
                }
                if ($scope.intervalValue == 'month') {
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
                            color : '#292929',
                            fontSize : '14px',
                            fontFamily : '"OpenSans", Helvetica, Arial, sans-serif',
                            textShadow : '0 1px 1px #FFFFFF',
                            letterSpacing : '1px'

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
                                fontFamily : '"OpenSans", Helvetica, Arial, sans-serif',
                                fontWeight : 'bold'

                            }
                        }

                    },
                    yAxis : {
                        lineColor : '#292929',
                        style : {
                            fontFamily : '"OpenSans", Helvetica, Arial, sans-serif',
                            fontWeight : 'bold'
                        }
                    },
                    plotOptions : {
                        area : {
                            fillOpacity : 0.1,
                            lineWidth : 3,
                            shadow : false
                        }
                    },
                    colors : ['#f05000','#bb630e'],
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
            };
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
        }
    };

    rzpd.hooks = {

        toggleLivemode: function() {
            rzpd.views.toggleLivemode();
        },

        changeGraphInterval: function() {
            rzpd.views.changeGraphInterval($(this));
        }

    };

    $('#livemode .button-wrap').click(rzpd.hooks.toggleLivemode);
    $('.btn-group .btn').click(rzpd.hooks.changeGraphInterval);

    if (document.getElementById("datepicker-group"))
        rzpd.views.renderDatepickers();
});