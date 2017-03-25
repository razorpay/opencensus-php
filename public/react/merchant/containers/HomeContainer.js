import React, { Component } from 'react'
import ajax from 'merchant/utils/ajax'
import Header from 'rzp/ui/Header'
import store from 'merchant/store'
import moment from 'moment'
import DateRangePickerField from 'rzp/ui/Forms/DateRangePickerField'
import { createLineData, makeLineData, timeScale } from 'rzp/utils/chart'
import { Line } from 'react-chartjs-2';

const intervals = [
  {
    value: 'day',
    label: 'Daily'
  },
  {
    value: 'week',
    label: 'Weekly'
  },
  {
    value: 'month',
    label: 'Monthly'
  },
  {
    value: 'year',
    label: 'Yearly'
  }
]

const colors = [
  'primary',
  'success',
  'info',
  'warn',
  'danger'
]

const colorClass = {
  captured: colors[1],
  authorized: colors[2],
  refunded: colors[3],
  failed: colors[4]
}

/* TODO move to utils */
function capitalize(string) {
  return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

/* TODO move to utils */
function formatAmount(amount) {
  return '₹' + (amount/100).toFixed(2).replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,').replace('.00', '');
}

function formatFromNow(unixSeconds) {
  return moment(unixSeconds * 1e3).fromNow()
}

export default class HomeContainer extends Component {
  // currently focused daterange input field
  focusedDate = null;

  state = {
    loading: true,

    /* date/intervel controls */
    from: moment().endOf('day').subtract(30, 'days'),
    to: moment().endOf('day'),
    interval: 0,

    /* top information cards */
    entity_totals: null,
    payment_breakup: null,
    current_balance: null,

    /* recent entity list */
    recent_payments: null,
    recent_refunds: null,
    recent_settlements: null,

    graph_data: null
  }

  constructor(props) {
    super(props)
  }

  componentWillMount() {
    this.fetchAggregrations()
  }

  render() {
    let {
      from,
      to,
      loading,
      entity_totals,
      recent_payments,
      recent_refunds,
      recent_settlements,
      current_balance,
      graph_data
    } = this.state;

    var currentMode = store.getState().session.mode;
    return (
      <div>
        <Header
          title='Dashboard'
          showMode={false}
        >
          <div style={{float: 'right'}}>
            <DateRangePickerField
              startDate={from}
              endDate={to}
              onDatesChange={({ startDate, endDate })=> {
                this.setState({
                  from: startDate,
                  to: endDate
                })
                if (!this.focusedDate && startDate && endDate) {
                  this.fetchAggregrations();
                }
              }}
              onFocusChange={(focused)=> {this.focusedDate = focused}}
              isOutsideRange={day=> moment().isBefore(day)}
              initialVisibleMonth={()=> {return this.state.from}}
            />
          </div>
          <div>
            <small className='text-muted'>
              Welcome to Razorpay.
            </small>
            <a className='start-tour-link'>Start Tour</a>
          </div>
        </Header>
        {!loading &&
          <div className='wrapper-md'>
            <div className='row'>
              <div className='col-md-12 col-lg-6'>
                <div className='row row-sm text-center'>
                  <HomeInfoCard
                    content={entity_totals.data.settlement.successful_txn_count}
                    title='Total Settlements'
                  />
                  <HomeInfoCard
                    content={recent_payments.data.count ? formatFromNow(recent_payments.data.items[0].created_at) : 'Never'}
                    title='Last Transaction'
                  />
                  <HomeInfoCard
                    bg='info'
                    content={entity_totals.data.payment.successful_txn_count}
                    title='Total Payments'
                  />
                  <HomeInfoCard
                    bg='primary'
                    content={entity_totals.data.refund.successful_txn_count}
                    title='Total Refunds'
                  />
                  <HomeInfoCard
                    content={'₹' + entity_totals.data.payment.total_amount/100}
                    title='Total Volume'
                  />
                  <HomeInfoCard
                    content={'₹' + current_balance.data.balance/100}
                    title='Current Balance'
                  />
                </div>
              </div>
              <div className='col-md-12 col-lg-6'>
                <div className='panel wrapper'>
                  <h4 className='font-thin m-t-none m-b text-muted'>Successful Transactions</h4>
                  <div style={{height: '244px'}}>
                    <Line
                      options={timeScale}
                      data={createLineData(graph_data.data.filter((d)=> {
                        if (currentMode === 'live') {
                          return !d.mode;
                        }
                        return d.mode;
                      }), 'count', 'Successful Transactions')}
                    />
                  </div>
                </div>
              </div>
            </div>
            <div className='panel hbox hbox-auto-xs no-border'>
              <div className='col wrapper'>
                <h4 className='font-thin m-t-none m-b text-muted'>Transaction Volume</h4>
                <div style={{height: '300px'}}>
                  <Line
                    options={timeScale}
                    data={createLineData(graph_data.data.filter((d)=> {
                      if (currentMode === 'live') {
                        return !d.mode;
                      }
                      d.amount = d.amount / 100;
                      return d.mode;
                    }), 'amount', 'Transaction Volume')}
                  />
                </div>
              </div>
              <div className='col wrapper-lg w-lg bg-light dk r-r'>
                <h4 className='font-thin m-t-none m-b'>Transaction Types</h4>
                {this.methodBreakup().map((methodData, index)=> {
                  return <div key={index}>
                  {methodData ?
                  <div>
                    <div className='text-center-folded'>
                      <span className='pull-right'>{methodData.value}</span>
                      <span>{capitalize(methodData.title)}</span>
                    </div>
                    <div className='progress-xs m-t-sm bg-white progress'>
                      <div className={'progress-bar progress-bar-' + methodData.bg} role='progressbar' style={{width: methodData.value}}></div>
                    </div>
                  </div>
                  : 'No Data'
                  }
                </div>})}
              </div>
            </div>
            <div className='panel wrapper'>
              <div className='row'>
                <RecentEntityTable
                  entity='payment'
                  data={recent_payments.data}
                />
                <RecentEntityTable
                  entity='refund'
                  data={recent_refunds.data}
                />
                <RecentEntityTable
                  entity='settlement'
                  data={recent_settlements.data}
                />
              </div>
            </div>
          </div>
        }
      </div>
    )
  }

  fetchAggregrations() {
    Promise.all([
      this.state.entity_totals || ajax('/analytics/aggregations'),
      this.state.payment_breakup || ajax('/analytics/payment/aggregations'),
      this.state.current_balance || ajax('/user/generic', {
        appendModeInQueryParam: true,
        data: {
          route_name: 'balance_fetch'
        }
      }),
      this.state.recent_payments || ajax('/user/generic', {
        appendModeInQueryParam: true,
        data: {
          route_name: 'payment_fetch_multiple'
        }
      }),
      this.state.recent_refunds || ajax('/user/generic', {
        appendModeInQueryParam: true,
        data: {
          route_name: 'refund_fetch_multiple'
        }
      }),
      this.state.recent_settlements || ajax('/user/generic', {
        appendModeInQueryParam: true,
        data: {
          route_name: 'setl_fetch_multiple'
        }
      }),
      ajax('/analytics/transactions', {
        data: {
          type: intervals[this.state.interval].value,
          from: this.state.from.unix(),
          to: this.state.to.unix()
        }
      })
    ]).then((values) => {
      this.setState({
        loading: false,
        entity_totals: values[0],
        payment_breakup: values[1],
        current_balance: values[2],
        recent_payments: values[3],
        recent_refunds: values[4],
        recent_settlements: values[5],
        graph_data: values[6]
      })
    })
  }

  methodBreakup() {
    const data = this.state.payment_breakup.data;
    const methods = [
      'CARD',
      'EMI',
      'NETBANKING',
      'WALLET',
      'UPI'
    ].filter(method=> data[method])

    const total = methods.reduce((prev, cur)=> {return prev + data[cur]}, 0)

    if (total) {
      return methods.map((method, index)=> {
        return {
          bg: colors[index],
          title: capitalize(method),
          value: (100*data[method]/total).toFixed(1).replace('.0', '') + '%'
        }
      })
    }
    return [null]
  }
}

class HomeInfoCard extends Component {
  render() {
    let {
      bg,
      content,
      title
    } = this.props;

    let panelClass = 'panel padder-v item';
    let textClass = 'font-thin h1';

    if (bg) {
      panelClass += ` bg-${bg}`;
      textClass += ` text-white`;
    }

    return <div className='col-xxs-12 col-xs-6 col-sm-6 col-md-4 col-lg-6'>
      <div className={panelClass}>
        <div className={textClass}>{content}</div>
        <span className='text-muted text-xs'>{title}</span>
      </div>
    </div>
  }
}

class RecentEntityTable extends Component {
  render() {
    let {
      entity,
      data
    } = this.props;

    return <div className='col-md-4 b-r b-light no-border-xs'>
      <a data-tip='See All Payments' className='text-muted pull-right text-lg' href='#/app/payments/list'><i className='icon-arrow-right'></i></a>
      <h4 className='font-thin m-t-none m-b-md text-muted'>Recent {capitalize(entity)}s</h4>
        {data.count ? data.items.slice(0, 5).map((item, index)=> {
          return <div className='m-b m-l row' key={index}>
              <a href='#/app/payments/pay_7VBun74YJV4Sx8'>
                <div
                  className={'col-xs-4 col-md-3 label text-base bg-' + (colorClass[item.status] || 'light')}
                  data-tip={capitalize(item.status)} data-place='right'>
                  {formatAmount(item.amount)}
                </div>
                <div className='col-xs-8 col-md-9'>
                  <code className='hidden-xs'>{item.id}</code>
                  <span className='pull-right'>{formatFromNow(item.created_at)}</span>
                </div>
              </a>
            </div>
          })
          : <div className='m-b m-l row'>No Recent Payments</div>
        }
    </div>
  }
}