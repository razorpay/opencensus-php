import React, { Component } from 'react'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'
import Header from 'rzp/ui/Header'
import moment from 'moment'
import ajax from 'merchant/utils/ajax'
import * as NotificationsActions from 'rzp/modules/notifications'

let now = moment();
let currentMonth = now.month();
let currentYear = now.year();
let currentDate = now.date();

function numberOfDays(month, year) {
  return moment(year + ' ' + month, 'YYYY M').daysInMonth();
}

@connect(
  (state) => {
    return {
      mode: state.session.mode,
      user: state.session.user
    }
  },
  {...NotificationsActions}
)
export default class ReportsContainer extends Component {

  state = {
    entity: 'payment',
    type: 'monthly',
    month: currentMonth,
    year: currentYear,
    day: currentDate
  }

  onChange = (r)=> {
    this.setState({
      [r.target.name]: r.target.value
    })
  }

  render() {
    let {
      mode,
      user
    } = this.props;

    let {
      entity,
      type,
      month,
      year,
      day
    } = this.state;

    return (
      <div class='react-root'>
        <Header title='Download Reports' />
        <div class='content-wrapper'>
          <div class="panel panel-default panel-form col-sm-6 col-sm-offset-3">
            <div class="panel-heading m-t m-b">
              Download Report - {mode} Mode
            </div>
            <div class="text-center m-t m-b">
              <div class="row">
                <div class="m-b col-sm-3">
                  <select name="entity" class="form-control" value={entity} onChange={this.onChange}>
                    <option value="payment">Payment</option>
                    <option value="refund">Refund</option>
                    <option value="order">Order</option>
                    <option value="settlement">Settlement</option>
                    {user.tags.indexOf('Marketplace') === -1 || (
                      <optgroup>
                        <option value="transfer">Transfer</option>
                        <option value="reversal">Reversal</option>
                      </optgroup>
                    )}
                    <option value="transaction">Combined</option>
                    {user.tags.indexOf('Broking_Report') === -1 || (
                      <option value="broking">Broking Report</option>
                    )}
                    <option value="invoice">Monthly Invoice</option>
                  </select>
                </div>

                {type === 'invoice' || (
                  <div class="m-b col-sm-2">
                    <select name="type" class="form-control" value={type} onChange={this.onChange}>
                      <option value="daily">Daily</option>
                      <option value="monthly">Monthly</option>
                    </select>
                  </div>
                )}

                <div class="m-b col-sm-2">
                  <select name="year" class="form-control" value={year} onChange={this.onChange}>
                    <option value="2017">2017</option>
                    <option value="2016">2016</option>
                    <option value="2015">2015</option>
                  </select>
                </div>

                <div class="m-b col-sm-2">
                  <select name="month" class="form-control" value={month} onChange={this.onChange}>
                    {moment.months().map((name, index)=> {
                      return <option value={index+1} key={index}>{name}</option>
                    })}
                  </select>
                </div>

                {type === 'daily' && (
                  <div class="m-b col-sm-2">
                    <select name="day" class="form-control" value={day} onChange={this.onChange}>
                      {Array.from(Array(numberOfDays(month, year)), ((undef, index)=> {
                        return <option value={index+1} key={index}>{index+1}</option>
                      }))}
                    </select>
                  </div>
                )}

              </div>
              <button class="btn btn-primary btn-rounded" onClick={this.generateReport}>Download Report</button>
            </div>
            <footer class="panel-footer">
              <div class="text-center m-t m-b">
                <div class="row">
                  <div class="col-sm-12 text-center">
                    Combined reports will include transactions on the given date, as well as payments
                    settled on that given date.
                  </div>
                </div>
              </div>
            </footer>
          </div>
        </div>
      </div>
    )
  }

  generateReport = ()=> {
    let {
      entity,
      type,
      month,
      year,
      day
    } = this.state;

    let data = {
      month,
      year
    }

    if (entity === 'invoice') {
      return window.open(`/${this.props.mode}/reports/invoice?year=${year}&month=${month}`, '_blank');
    }

    if (entity === 'daily') {
      data.day = day;
    }

    var ajaxParams = {
      url: '/reports/' + entity,
      data: data,
    }

    if (entity === 'broking') {
      ajaxParams.xhrFields = {
        responseType: 'arraybuffer'
      }
      ajaxParams.headers = {
        Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      }
    }

    var request = ajax(ajaxParams)
      .then((data)=> {
        this.props.showNotification({
          type: 'success',
          message: 'Your report will download shortly'
        })
        location.href = data.data.url;
      })
      .catch((e)=> {
        this.props.showNotification({
          type: 'danger',
          message: 'No data found for given time range'
        })
      })
  }
}
