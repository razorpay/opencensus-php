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
            childDivs: ['horizontal-data-wrapper','transactions-line-chart-wrapper','transaction-count-line-chart-half']
        },
        transactions: {
            childDivs: ['transaction-count-line-chart-full', 'transaction-list-all']
        },
        refunds: {
            childDivs: ['refund-list']
        },
        settlements: {
            childDivs: ['settle-list']
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
                    tickInterval : rzpd.config.intv,
                    endOnTick : false,
                    dateTimeLabelFormats : {
                        second : '%H:%M',
                        minute : '%H:%M',
                        hour : '%H:%M',
                        day : '%e %b',
                        week : '%d/%m',
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

        plotTransactionsChart: function(data, div) {
            rzpd.views.setChart(div, 'area', 'Transactions', [{'data': data}], {
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
                        week : '%d/%m',
                        month : '%b %Y',
                        year : '%Y'
                    }
                }
            });
        },

        plotTransactionCountChart: function(data, div) {
            rzpd.views.setChart(div, 'area', 'Successful Transactions', [{'data': data}], {
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
                        week : '%d/%m',
                        month : '%b %Y',
                        year : '%Y'
                    }
                },
            });
        },

        renderTransactionsList: function(data, div) {
            var html = '';
            if (data.length === 0)
                html = "<div class='error'>Abe Koi Txn Karega Tab Dikhega Na.</div>";
            else
                for (var i in data)
                    html += '<li class="transaction-list-item"><a href="#!/transactions/'+data[i].transaction_id+'" class="grid"><div class="col-1-2"><span class="amount col-1-4">₹' + data[i].amount + '</span><span class="id col-9-12">' + data[i].transaction_id + '</span></div><span class="status col-1-4">' + data[i].status + '</span><span class="date col-1-4">' + moment(data[i].updated_at, 'X').format('DD-MM-YYYY HH:MM') + '</span></a></li>';
            $('#' + div).html(html);
        },

        renderTxnDetails: function(data) {
            var html = '';
            html += '<div class="col-1-4 key">ID</div><div class="col-9-12">' + data.id + '</div><div class="col-1-4 key">Amount</div><div class="col-9-12">₹' + data.amount + '</div><div class="col-1-4 key">Created At</div><div class="col-9-12">' + moment(data.created_at, 'X').format('MMMM Do YYYY, h:mm:ss a') + '</div><div class="col-1-4 key">Updated At</div><div class="col-9-12">' + moment(data.updated_at, 'X').format('MMMM Do YYYY, h:mm:ss a') + '</div><div class="col-1-4 key">Status</div><div class="col-9-12">' + data.status + '</div>';
            $('#transaction-details .details').html(html);
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
            $('#sidebar li').removeClass('active');
            $("[data-tab='" + tab + "']").addClass('active');
        },

        renderStats: function(data) {
            $('#total-txn-stat').html(data.txns);
            $('#success-stat').html(data.success);
            $('#total-amount-stat').html(data.amount);
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
                url: '/analytics/transactions',
                type: 'GET',
                data: {
                    type: group,
                    from: rzpd.config.from,
                    to: rzpd.config.to
                },
                success: function(result) {
                    rzpd.hooks.parseTransactionCountChart(parentDiv,result, group);

                    var chartData = [], intv;

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
                    var div = $('#' + parentDiv + ' div[data-type="txn-volume-line"]').attr('id');

                    rzpd.postAjaxCallStack.push({fn: 'plotTransactionsChart', data: chartData, div: div});
                    rzpd.hooks.updateSubpanel(parentDiv);
                }
            });
        },

        plotTransactionCountChart: function(parentDiv) {
            var group = 'day';

            $.ajax({
                url: '/analytics/transactions',
                type: 'GET',
                data: {
                    type: group,
                    from: parseInt(rzpd.config.defaultStartDates[group].getTime()/1000),
                    to: parseInt(new Date(moment().format('LL')).getTime()/1000)
                },
                success: function(result) {
                    rzpd.hooks.parseTransactionCountChart(parentDiv,result, group);
                }
            });
        },

        parseTransactionCountChart: function(parentDiv, result, group) {
            var chartData = [], intv;

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

            var div = $('#' + parentDiv + ' div[data-type="txn-count-line"]').attr('id');

            rzpd.postAjaxCallStack.push({fn: 'plotTransactionCountChart', data: chartData, div: div});
            rzpd.hooks.updateSubpanel(parentDiv);
        },

        resetPanels: function() {
            rzpd.views.hidePanels();
            rzpd.views.showLoader();
            rzpd.config.childDivLoadCount = 0;
            rzpd.postAjaxCallStack = [];
        },

        renderStats: function(parentDiv) {
            $.ajax({
                url: '/analytics/aggregations',
                success: function(result) {
                    var data = {
                        success: '0%',
                        amount: '₹0',
                        txns: 0
                    };
                    if (result.data !== null) {
                        data.success = parseInt(result.data.successful_txn_count * 100/result.data.txn_count) + '%';
                        data.amount = '₹' + result.data.total_amount;
                        data.txns = result.data.txn_count;
                    }
                    rzpd.views.renderStats(data);
                }
            });
            rzpd.hooks.updateSubpanel(parentDiv);
        },

        renderTransactionsList: function(parentDiv) {
            $.ajax({
                url: '/transactions',
                data: {
                    count: 10
                },
                success: function(result) {
                    var div = $('#' + parentDiv + ' ul[data-type="txn-list"]').attr('id');

                    rzpd.postAjaxCallStack.push({fn: 'renderTransactionsList', data: result, div: div});
                    rzpd.hooks.updateSubpanel(parentDiv);
                }
            });
        },

        renderRefundsList: function(parentDiv) {
            $.ajax({
                url: '/transactions',
                data: {
                    count: 10,
                    status: 'refunded'
                },
                success: function(result) {
                    var div = $('#' + parentDiv + ' ul[data-type="txn-list"]').attr('id');

                    rzpd.postAjaxCallStack.push({fn: 'renderTransactionsList', data: result, div: div});
                    rzpd.hooks.updateSubpanel(parentDiv);
                }
            });
        },

        renderSettlementsList: function(parentDiv) {
            $.ajax({
                url: '/transactions',
                data: {
                    count: 10,
                    status: 'settled'
                },
                success: function(result) {
                    var div = $('#' + parentDiv + ' ul[data-type="txn-list"]').attr('id');

                    rzpd.postAjaxCallStack.push({fn: 'renderTransactionsList', data: result, div: div});
                    rzpd.hooks.updateSubpanel(parentDiv);
                }
            });
        },

        fetchTxnDetails: function(txn_id) {
            $.ajax({
                url: '/transactions/' + txn_id,
                success: function(result) {
                    rzpd.views.hideLoader();
                    rzpd.views.showDiv('#transaction-one');
                    rzpd.views.renderTxnDetails(result);
                }
            });
        },

        updateSubpanel: function(subpanel) {
            if (rzpd.config.currentTab == subpanel)
                rzpd.config.childDivLoadCount += 1;

            if (rzpd.tabs[subpanel].childDivs.length == rzpd.config.childDivLoadCount) {
                rzpd.views.hideLoader();
                rzpd.views.showDiv('#' + subpanel);
                for (var i in rzpd.postAjaxCallStack) {
                    rzpd.views[rzpd.postAjaxCallStack[i].fn](rzpd.postAjaxCallStack[i].data, rzpd.postAjaxCallStack[i].div);
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
            rzpd.hooks.plotTransactionCountChart('transactions');
            rzpd.hooks.renderTransactionsList('transactions');
        },

        renderTransaction: function(txn_id) {
            rzpd.hooks.setTab('transactions');
            rzpd.hooks.fetchTxnDetails(txn_id);
        },

        renderRefunds: function() {
            rzpd.hooks.setTab('refunds');
            rzpd.hooks.renderRefundsList('refunds');
        },

        renderSettlements: function() {
            rzpd.hooks.setTab('settlements');
            rzpd.hooks.renderSettlementsList('settlements');
        },

        renderKeys: function() {
            rzpd.hooks.setTab('keys');
        },

        renderAccount: function() {
            rzpd.hooks.setTab('account');
        },

        renderActivation: function() {
            rzpd.hooks.setTab('activation');
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

        Path.map("#!/transactions/:txn_id").to(function(){
            rzpd.hooks.renderTransaction(this.params.txn_id);
        }).enter(rzpd.hooks.resetPanels);

        Path.map("#!/transactions").to(function(){
            rzpd.hooks.renderTransactions();
        }).enter(rzpd.hooks.resetPanels);

        Path.map("#!/refunds").to(function(){
            rzpd.hooks.renderRefunds();
        }).enter(rzpd.hooks.resetPanels);

        Path.map("#!/settlements").to(function(){
            rzpd.hooks.renderSettlements();
        }).enter(rzpd.hooks.resetPanels);

        Path.map("#!/keys").to(function(){
            rzpd.hooks.renderKeys();
        }).enter(rzpd.hooks.resetPanels);

        Path.map("#!/account").to(function(){
            rzpd.hooks.renderAccount();
        }).enter(rzpd.hooks.resetPanels);

        Path.map("#!/activation").to(function(){
            rzpd.hooks.renderActivation();
        }).enter(rzpd.hooks.resetPanels);

        Path.root("#!/");

        Path.listen();
    }

});