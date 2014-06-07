$(document).ready(function()
{
    rzpd = {};

    rzpd.colors = {
        blue: '#29B7D6',
        grey: '#999999'
    };

    rzpd.postAjaxCallStack = [];

    rzpd.tabs = {
        dashboard: {
            childDivs: ['horizontal-data-wrapper','transactions-line-chart-wrapper','transaction-count-line-chart']
        }
    };

    rzpd.config = {
        timeScale: 'day',
        defaultDates: true,
        defaultStartDates: {
            'day': new Date(moment().subtract('months',1).format('LL')),
            'week': new Date(moment().subtract('months',1).format('LL')),
            'month': new Date(moment().subtract('months',5).format('LL')),
            'year': new Date(moment().subtract('years',5).format('LL')),
        },
        currentTab: 'dashboard',
        childDivLoadCount: 0
    };

    rzpd.config.from = parseInt(rzpd.config.defaultStartDates[rzpd.config.timeScale].getTime()/1000);
    rzpd.config.to = parseInt(new Date(moment().format('LL')).getTime()/1000);

    rzpd.views = {
        toggleLivemode: function() {
            $('#livemode .button-wrap').toggleClass("button-active");
            $('.button-desc').toggleClass('active');
        },

        changeGraphScale: function(el) {
            $('.btn-group .btn').removeClass('active');
            rzpd.config.timeScale = el.data('interval');
            el.addClass("active");
        },

        setChart: function(ChartDiv, ChartType, ChartTitle, ChartData, ChartOptions) {
            if (rzpd.config.timeScale == 'day') {
                intv = null;
            }
            if (rzpd.config.timeScale == 'week') {
                intv = 24 * 3600 * 7 * 1000;
            }
            if (rzpd.config.timeScale == 'month') {
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
                    endOnTick : false,
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
                format: 'DD-MM-YYYY',
                defaultDate: rzpd.config.defaultStartDates[rzpd.config.timeScale],
                setDefaultDate: true,
                yearRange: [2014,2020],
                onClose: function() {
                    rzpd.hooks.changeGraphInterval();
                }
            });

            rzpd.views.date_end = new Pikaday({
                field: document.getElementById('date-end'),
                firstDay: 1,
                format: 'DD-MM-YYYY',
                defaultDate: new Date(moment().format('LL')),
                setDefaultDate: true,
                yearRange: [2014,2020],
                onClose: function() {
                    rzpd.hooks.changeGraphInterval();
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
                        month : '%b %Y',
                        year : '%Y'
                    }
                }
            });
        },

        plotTransactionCountChart: function(data) {
            rzpd.views.setChart('transaction-count-line-chart', 'area', 'Successful Transactions', [{'data': data}], {
                yAxis : {
                    title : {
                        text : ''
                    },
                    labels : {
                        formatter : function() {
                            return this.value;
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
                    pointFormat: '<b>{point.y}</b>',
                    dateTimeLabelFormats: {
                        second : '%H:%M',
                        minute : '%H:%M',
                        hour : '%H:%M',
                        day : '%e %b',
                        week : '%d.%m',
                        month : '%b %Y',
                        year : '%Y'
                    }
                },
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

        highlightTabInSidebar: function(tab) {
            $("[data-tab='" + tab + "']").addClass('active').siblings('li').removeClass('active');
        }
    };

    rzpd.hooks = {

        toggleLivemode: function() {
            rzpd.views.toggleLivemode();
        },

        setDates: function() {
            if (rzpd.config.defaultDates === true)
                rzpd.views.date_start.setDate(rzpd.config.defaultStartDates[rzpd.config.timeScale]);

            rzpd.config.from = parseInt(rzpd.views.date_start.getDate().getTime()/1000);
            rzpd.config.to = parseInt(rzpd.views.date_end.getDate().getTime()/1000);
        },

        changeGraphScale: function() {
            rzpd.views.changeGraphScale($(this));
            rzpd.hooks.setDates();
            rzpd.hooks.resetPanels();
            rzpd.hooks.renderDashboard();
        },

        changeGraphInterval: function() {
            rzpd.config.defaultDates = false;
            rzpd.hooks.setDates();
            rzpd.hooks.resetPanels();
            rzpd.hooks.renderDashboard();
        },

        plotTransactionsChart: function(parentDiv) {
            var group = rzpd.config.timeScale;

            $.ajax({
                url: './transactions/analytics',
                type: 'GET',
                data: {
                    type: group,
                    from: rzpd.config.from,
                    to: rzpd.config.to
                },
                success: function(result) {
                    rzpd.hooks.plotTransactionCountChart(parentDiv,result);

                    var chartData = [];

                    $(result.data).each(function(i, s) {
                        chartData.push([s.created_at * 1000,parseInt(s.amount)]);
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

                    rzpd.postAjaxCallStack.push({fn: 'plotTransactionsChart', data: chartData});
                    rzpd.hooks.updateSubpanel(parentDiv);
                }
            });
        },

        plotTransactionCountChart: function(parentDiv, result) {
            var group = rzpd.config.timeScale;

            var chartData = [];
            $(result.data).each(function(i, s) {
                chartData.push([s.created_at * 1000, parseInt(s.count)]);
            });

            staggerLinesVal = 1;
            if (group == 'day') {
                intv = null;
            }
            if (group == 'week') {
                intv = 24 * 3600 * 7 * 1000;
                if( chartData.length > 8 ) {
                    staggerLinesVal = 2;
                    intv = 48 * 3600 * 7 * 1000;
                }
            }
            if (group == 'month') {
                intv = 24 * 3600 * 30 * 1000;
            }
            
            rzpd.config.intv = intv;

            rzpd.postAjaxCallStack.push({fn: 'plotTransactionCountChart', data: chartData});
            rzpd.hooks.updateSubpanel(parentDiv);
        },

        resetPanels: function() {
            rzpd.views.hidePanels();
            rzpd.views.showLoader();
            rzpd.config.childDivLoadCount = 0;
            rzpd.postAjaxCallStack = [];
        },

        renderStats: function(parentDiv) {
            rzpd.hooks.updateSubpanel(parentDiv);
        },

        updateSubpanel: function(subpanel) {
            if (rzpd.config.currentTab == subpanel)
                rzpd.config.childDivLoadCount += 1;

            if (rzpd.tabs[subpanel].childDivs.length == rzpd.config.childDivLoadCount) {
                rzpd.views.hideLoader();
                rzpd.views.showDiv('#' + subpanel);
                for (var i in rzpd.postAjaxCallStack) {
                    rzpd.views[rzpd.postAjaxCallStack[i].fn](rzpd.postAjaxCallStack[i].data);
                }
            }
        },

        setTab: function(tab) {
            tab = tab || 'dashboard';
            rzpd.config.currentTab = tab;
            rzpd.views.highlightTabInSidebar(tab);
        },

        renderDashboard: function() {
            rzpd.hooks.setTab('dashboard');
            rzpd.hooks.plotTransactionsChart('dashboard');
            rzpd.hooks.renderStats('dashboard');
        },

        renderTransactions: function() {
            rzpd.hooks.setTab('transactions');
        },

        renderLogs: function() {
            rzpd.hooks.setTab('logs');
        }

    };

    /* Event listeners */

    $('#livemode .button-wrap').click(rzpd.hooks.toggleLivemode);
    $('.btn-group .btn').click(rzpd.hooks.changeGraphScale);
    if (document.getElementById("datepicker-group"))
        rzpd.views.renderDatepickers();

    /* Routing (using path.js) */

    if (document.getElementById('dashboard-wrapper') !== null)
    {
        Path.map("#!/").to(function(){
            rzpd.hooks.renderDashboard();
        }).enter(rzpd.hooks.resetPanels);

        Path.map("#!/transactions").to(function(){
            rzpd.hooks.renderTransactions();
        }).enter(rzpd.hooks.resetPanels);

        Path.map("#!/logs").to(function(){
            rzpd.hooks.renderLogs();
        }).enter(rzpd.hooks.resetPanels);


        Path.root("#!/");

        Path.listen();
    }

});