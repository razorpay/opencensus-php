import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { titleCase } from 'rzp/utils/rzp-utils';
import AsyncButton from 'react-async-button';
import moment from 'moment';
import ajax from 'merchant/utils/ajax';
import { generateReport } from 'merchant/modules/reports';
import { fetchAccounts } from 'merchant/modules/marketplace/accounts';
import * as NotificationsActions from 'rzp/modules/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';

let now = moment();
let currentMonth = now.month();
let currentYear = now.year();
let currentDate = now.date();

function numberOfDays(month, year) {
  return moment(year + ' ' + month, 'YYYY M').daysInMonth();
}

const selector = formValueSelector('generateReports');

@connect(
  state => {
    return {
      mode: state.session.mode,
      user: state.session.user,
      entity: selector(state, 'entity'),
      type: selector(state, 'type'),
      month: selector(state, 'month'),
      year: selector(state, 'year'),
    };
  },
  { generateReport, fetchAccounts, ...NotificationsActions }
)
@reduxForm({
  form: 'generateReports',
  initialValues: {
    entity: 'payment',
    type: 'monthly',
    month: currentMonth,
    year: currentYear,
    day: currentDate,
  },
})
export default class ReportsContainer extends Component {
  componentWillMount() {
    this.props.fetchAccounts().then(data => {});
  }
  prepareGenerateReport = values => {
    let { entity, type, month, year, day } = values;

    let data = {
      month,
      year,
    };

    if (entity === 'invoice') {
      return Promise.resolve(
        window.open(
          `/${this.props.mode}/reports/invoice?year=${year}&month=${month}`,
          '_blank'
        )
      );
    }

    if (type === 'daily') {
      data.day = day;
    }

    var ajaxParams = {
      url: '/reports/' + entity,
      data: data,
    };

    if (entity === 'broking') {
      ajaxParams.headers = {
        Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      };
    }

    return this.props
      .generateReport(ajaxParams)
      .then(data => {
        this.props.showNotification({
          type: 'success',
          message: 'Your report will download shortly',
        });

        if (entity === 'broking') {
          var blob = new Blob([data], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          });
          return saveAs(blob, 'broking_report.xlsx');
        }

        location.href = data.data.url;
      })
      .catch(e => {
        this.props.showNotification({
          type: 'error',
          message: 'No data found for given time range',
        });
      });
  };

  render() {
    let { entity, type, mode, month, year, user, handleSubmit } = this.props;
    let isMarketplace = user.tags.indexOf('Marketplace') !== -1;

    console.log(user);
    return (
      <tabbed-container>
        <header>
          <NavLink to="/reports">Download Reports</NavLink>
        </header>
        <div class="content-wrapper">
          {user.tags.indexOf('New_Report_UI') === -1
            ? // OLD UI
              <div class="row">
                <div class="col-sm-3">
                  <div class="form-group">
                    <Field
                      name="entity"
                      component="select"
                      class="form-control"
                    >
                      <option value="payment">Payment</option>
                      <option value="refund">Refund</option>
                      <option value="order">Order</option>
                      <option value="settlement">Settlement</option>
                      <option value="transaction">Combined</option>
                      {user.tags.indexOf('Broking_Report') === -1 ||
                        <option value="broking">Broking Report</option>}
                      // DSP Report is only for DSP Blackrock Merchant. Should not be enabled for any other merchants
                      {user.tags.indexOf('Dsp_Report') === -1 ||
                        <option value="dsp_report">
                          DSP Transaction Report
                        </option>}
                      <option value="invoice">Monthly Invoice</option>
                      {user.tags.indexOf('Marketplace') === -1 ||
                        <optgroup label="Route">
                          <option value="transfer">Transfer</option>
                          <option value="reversal">Reversal</option>
                        </optgroup>}
                    </Field>
                  </div>
                </div>

                {entity === 'invoice' ||
                  <div class="col-sm-2">
                    <div class="form-group">
                      <Field
                        name="type"
                        class="form-control"
                        component="select"
                      >
                        <option value="daily">Daily</option>
                        <option value="monthly">Monthly</option>
                      </Field>
                    </div>
                  </div>}

                <div class="col-sm-2">
                  <div class="form-group">
                    <Field name="year" class="form-control" component="select">
                      <option value="2017">2017</option>
                      <option value="2016">2016</option>
                      <option value="2015">2015</option>
                    </Field>
                  </div>
                </div>

                <div class="col-sm-3">
                  <div class="form-group">
                    <Field name="month" class="form-control" component="select">
                      {moment.months().map((name, index) => {
                        return (
                          <option value={index + 1} key={index}>
                            {name}
                          </option>
                        );
                      })}
                    </Field>
                  </div>
                </div>

                {type === 'daily' &&
                  <div class="col-sm-2">
                    <div class="form-group">
                      <Field name="day" class="form-control" component="select">
                        {Array.from(
                          Array(numberOfDays(month, year)),
                          (undef, index) => {
                            return (
                              <option value={index + 1} key={index}>
                                {index + 1}
                              </option>
                            );
                          }
                        )}
                      </Field>
                    </div>
                  </div>}
              </div>
            : // NEW UI
              <div class="panel panel-default panel-form col-sm-8 col-sm-offset-2">
                <div class="row report-wrapper">
                  <div class="col-md-4 report-list-panel">
                    <div class="title">SELECT REPORT TYPE</div>
                    <Field
                      id="combined"
                      name="entity"
                      value="combined"
                      component="input"
                      type="radio"
                      class="report-type form-control"
                    />
                    <label for="combined">Combined Report</label>
                    <Field
                      id="payment"
                      name="entity"
                      value="payment"
                      component="input"
                      type="radio"
                      class="report-type form-control"
                    />
                    <label for="payment">Payment</label>
                    <Field
                      id="refund"
                      name="entity"
                      value="refund"
                      component="input"
                      type="radio"
                      class="report-type form-control"
                    />
                    <label for="refund">Refund</label>
                  </div>
                  <div class="col-md-8 report-generate-panel">
                    <div class="title">
                      {isMarketplace ? 'SELECT ' : ''}ACCOUNT
                    </div>

                    {isMarketplace === false
                      ? <div class="account">
                          <strong>{user.name || user.user.name}</strong>
                          {' '}
                          -
                          {' '}
                          {user.email || user.user.email}
                        </div>
                      : <Dropdown>
                          <DropdownTrigger class="dropdown-toggle">
                            {user.name || user.user.name} <span class="caret" />
                          </DropdownTrigger>
                          <DropdownContent>
                            <ul class="dropdown-menu">
                              <li><a href="#">Test</a></li>
                              <li class="divider" />
                              <li><a href="#">Logout</a></li>
                            </ul>
                          </DropdownContent>
                        </Dropdown>}
                  </div>
                </div>
              </div>}

          <hr />

          <AsyncButton
            class="btn btn-primary"
            onClick={handleSubmit(this.prepareGenerateReport)}
            text="Download Report"
            pendingText="Generating..."
          />

          <footer>
            Combined reports will include transactions on the given date, as well as payments
            settled on that given date.
          </footer>
        </div>
      </tabbed-container>
    );
  }
}
