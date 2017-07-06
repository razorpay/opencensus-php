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
import Datetime from 'react-datetime';

function validYear(current) {
  return current.year() >= 2015 && current.year() <= 2017;
}

const selector = formValueSelector('generateReports');

@connect(
  state => {
    return {
      mode: state.session.mode,
      user: state.session.user,
      account: state.accounts,
      entity: selector(state, 'entity'),
      type: selector(state, 'type'),
      date: selector(state, 'date'),
    };
  },
  { generateReport, fetchAccounts, ...NotificationsActions }
)
@reduxForm({
  form: 'generateReports',
  initialValues: {
    entity: 'payment',
    type: 'daily',
    date: moment(),
  },
})
export default class ReportsContainer extends Component {
  componentWillMount() {
    this.props.fetchAccounts();
  }
  prepareGenerateReport = values => {
    let { entity, type, date, account_id } = values;

    let data = {
      month: date.month() + 1, // Jan is 0 in moment library
      year: date.year(),
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
      data.day = date.date();
    }

    var ajaxParams = {
      url: '/reports/' + entity,
      data: data,
    };

    if (account_id !== this.props.user.id) {
      data.account_id = account_id;
    }

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
    user.tags.push('Marketplace');

    let isMarketplace = user.tags.indexOf('Marketplace') !== -1;
    isMarketplace = true;

    // console.log('USER', user);
    return (
      <tabbed-container>
        <header>
          <NavLink to="/reports">Download Reports</NavLink>
        </header>
        <div class="report-wrapper col-lg-8 col-sm-10 col-xs-11">
          {/*Report Type Selection*/}
          <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 report-list-panel">
            <div class="title">SELECT REPORT TYPE</div>
            <Field
              name="entity"
              value="transaction"
              id="combined"
              component="input"
              type="radio"
              class="report-type form-control"
            />
            <label for="combined">Combined Report</label>

            <Field
              name="entity"
              value="payment"
              id="payment"
              component="input"
              type="radio"
              class="report-type form-control"
            />
            <label for="payment">Payments</label>

            <Field
              name="entity"
              value="refund"
              id="refund"
              component="input"
              type="radio"
              class="report-type form-control"
            />
            <label for="refund">Refunds</label>

            <Field
              name="entity"
              value="settlement"
              id="settlement"
              component="input"
              type="radio"
              class="report-type form-control"
            />
            <label for="settlement">Settlements</label>

            {user.tags.indexOf('Broking_Report') === -1 ||
              <div>
                <Field
                  name="entity"
                  value="broking"
                  id="broking"
                  component="input"
                  type="radio"
                  class="report-type form-control"
                />
                <label for="broking">Broking Reports</label>
              </div>}

            {/*DSP Report is only for DSP Blackrock Merchant. Should not be enabled for any other merchants*/}
            {user.tags.indexOf('Dsp_Report') === -1 ||
              <div>
                <Field
                  name="entity"
                  value="dsp_report"
                  id="dsp_report"
                  component="input"
                  type="radio"
                  class="report-type form-control"
                />
                <label for="dsp_report">DSP Transaction Report</label>
              </div>}
            <Field
              name="entity"
              value="invoice"
              id="invoice"
              component="input"
              type="radio"
              class="report-type form-control"
            />
            <label for="invoice">Monthly Invoice</label>

            {user.tags.indexOf('Marketplace') === -1 ||
              <div>
                <Field
                  name="entity"
                  value="transfer"
                  id="transfer"
                  component="input"
                  type="radio"
                  class="report-type form-control"
                />
                <label for="transfer">Transfers</label>

                <Field
                  name="entity"
                  value="reversal"
                  id="reversal"
                  component="input"
                  type="radio"
                  class="report-type form-control"
                />
                <label for="reversal">Reversals</label>
              </div>}
          </div>

          {/*Report Generate Panel*/}
          <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12 report-generate-panel">

            <div class="form-element">
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
                : <Field
                    component="select"
                    class="form-control"
                    name="account_id"
                  >
                    <option value={user.id || user.user.id} disabled selected>
                      Current Account
                    </option>
                    <option class="divider" disabled />
                    <option value="acc_89wltQH83y3z7s">
                      89wltQH83y3z7s
                    </option>
                  </Field>}
            </div>
            <div class="form-element">
              <div class="title">
                DATE RANGE
              </div>
              {entity === 'invoice' ||
                <div class="col-sm-3">
                  <div class="form-group">
                    <Field name="type" class="form-control" component="select">
                      <option value="daily">Daily</option>
                      <option value="monthly">Monthly</option>
                    </Field>
                  </div>
                </div>}

              {(type === 'monthly' || entity === 'invoice') &&
                <div class="col-sm-4">
                  <div class="form-group">
                    <Field
                      name="date"
                      component={props => (
                        <Datetime
                          dateFormat="MMM, YYYY"
                          defaultValue={props.input.value}
                          value={props.input.value}
                          onChange={value => props.input.onChange(value)}
                          inputProps={{
                            placeholder: 'Select Year-Month',
                          }}
                          isValidDate={validYear}
                          timeFormat={false}
                        />
                      )}
                      class="form-control"
                    />
                  </div>
                </div>}

              {type === 'daily' &&
                entity !== 'invoice' &&
                <div class="col-sm-4">
                  <div class="form-group">
                    <Field
                      name="date"
                      component={props => (
                        <Datetime
                          dateFormat="DD MMM, YYYY"
                          defaultValue={props.input.value}
                          value={props.input.value}
                          onChange={value => props.input.onChange(value)}
                          inputProps={{
                            placeholder: 'Select Date-Month-Year',
                          }}
                          isValidDate={validYear}
                          timeFormat={false}
                        />
                      )}
                      class="form-control"
                    />
                  </div>
                </div>}
            </div>

            <div class="form-element">
              <AsyncButton
                class="btn btn-primary"
                onClick={handleSubmit(this.prepareGenerateReport)}
                text="Generate and Download Report"
                pendingText="Generating..."
              />

              <footer>
                Combined reports will include transactions on the given date, as well as payments
                settled on that given date.
              </footer>
            </div>
          </div>
        </div>
      </tabbed-container>
    );
  }
}
