import React, { Component } from 'react'
import Header from 'rzp/ui/Header'
import { connect } from 'react-redux'
import { fetchAggregrations, fetchAnalytics } from 'merchant/modules/home'
import moment from 'moment'
import DateRangePickerField from 'rzp/ui/Forms/DateRangePickerField'
import { createLineData, makeLineData, timeScale } from 'rzp/utils/chart'
import { Line } from 'react-chartjs-2'
import Amount from 'rzp/ui/Amount'
import Spinner from 'rzp/ui/Spinner'

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

function formatFromNow(unixSeconds) {
  return moment(unixSeconds * 1e3).fromNow()
}

@connect(
  (state) => {
    return {
      mode: state.session.mode,
      analytics: state.home.analytics,
      aggregations: state.home.aggregations
    }
  },
  {
    fetchAggregrations,
    fetchAnalytics
  }
)
export default class HomeContainer extends Component {
  // currently focused daterange input field
  focusedDate = null;

  state = {

    /* date/intervel controls */
    from: moment().endOf('day').subtract(30, 'days'),
    to: moment().endOf('day'),

    /* not in use */
    interval: 0,
  }

  constructor(props) {
    super(props)
  }

  componentWillMount() {
    this.props.fetchAggregrations();
    this.props.fetchAnalytics(this.state);
  }

  getContent() {
    let aggregations = this.props.aggregations;
    let graph_data = this.props.analytics;
    let isLive = this.props.mode === 'live'

    return (aggregations &&
      <div className='wrapper-md'>
        <div className='row'>
          <div className='col-md-12 col-lg-6'>
            <div className='row row-sm text-center'>
              <HomeInfoCard
                content={aggregations.entity_totals.data.settlement.successful_txn_count}
                title='Total Settlements'
              />
              <HomeInfoCard
                content={aggregations.recent_payments.data.count ? formatFromNow(aggregations.recent_payments.data.items[0].created_at) : 'Never'}
                title='Last Transaction'
              />
              <HomeInfoCard
                bg='info'
                content={aggregations.entity_totals.data.payment.successful_txn_count}
                title='Total Payments'
              />
              <HomeInfoCard
                bg='primary'
                content={aggregations.entity_totals.data.refund.successful_txn_count}
                title='Total Refunds'
              />
              <HomeInfoCard
                amount
                content={aggregations.entity_totals.data.payment.total_amount}
                title='Total Volume'
              />
              <HomeInfoCard
                amount
                content={aggregations.current_balance.data.balance}
                title='Current Balance'
              />
            </div>
          </div>
          <div className='col-md-12 col-lg-6'>
            <div className='panel wrapper'>
              <h4 className='font-thin m-t-none m-b text-muted'>Successful Transactions</h4>
              <div style={{height: '244px', textAlign: 'center', lineHeight: '244px'}}>
                {
                  graph_data && 
                  <Line
                    options={timeScale}
                    data={createLineData(graph_data.data.filter((d)=> {
                      if (isLive) {
                        return !d.mode;
                      }
                      return d.mode;
                    }), 'count', 'Successful Transactions')}
                  /> ||
                  <Spinner />
                }
              </div>
            </div>
          </div>
        </div>
        <div className='panel hbox hbox-auto-xs no-border'>
          <div className='col wrapper'>
            <h4 className='font-thin m-t-none m-b text-muted'>Transaction Volume</h4>
            <div style={{height: '300px', textAlign: 'center', lineHeight: '300px'}}>
              {
                graph_data &&
                <Line
                  options={timeScale}
                  data={createLineData(graph_data.data.filter((d)=> {
                    if (isLive) {
                      return !d.mode;
                    }
                    d.amount = d.amount / 100;
                    return d.mode;
                  }), 'amount', 'Transaction Volume')}
                /> ||
                <Spinner />
              }
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
              data={aggregations.recent_payments.data}
            />
            <RecentEntityTable
              entity='refund'
              data={aggregations.recent_refunds.data}
            />
            <RecentEntityTable
              entity='settlement'
              data={aggregations.recent_settlements.data}
            />
          </div>
        </div>
      </div>
    )
  }

  render() {
    let {
      from,
      to
    } = this.state;

    let {
      analytics,
      aggregations,
      fetchAnalytics
    } = this.props;

    let content = this.getContent();
    let header = (
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
                this.props.fetchAnalytics(this.state);
              }
            }}
            onFocusChange={(focused)=> {this.focusedDate = focused}}
            isOutsideRange={day=> moment().isBefore(day)}
            initialVisibleMonth={_=> from}
          />
        </div>
        <div>
          <small className='text-muted'>
            Welcome to Razorpay.
          </small>
          {/*<a className='start-tour-link'>Start Tour</a>*/}
        </div>
      </Header>
    )

    return (
      <div class='react-root'>
        {header}
        {content}
      </div>
    )
  }

  methodBreakup() {
    const data = this.props.aggregations.payment_breakup.data;
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
                  <Amount value={item.amount} />
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