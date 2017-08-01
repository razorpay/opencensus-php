import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { titleCase } from 'rzp/utils/rzp-utils';
import AsyncButton from 'react-async-button';
import moment from 'moment';
import { generateReport } from 'merchant/modules/reports';
import { fetchAccounts } from 'merchant/modules/marketplace/accounts';
import * as NotificationsActions from 'rzp/modules/notifications';
import ReduxDatetime from 'rzp/ui/ReduxDatetime';
import { PowerSelect, TypeAhead } from 'react-power-select';

function validYear(current) {
  return current.year() >= 2015 && current.year() <= 2017;
}

const selector = formValueSelector('generateReports');

@connect(
  state => {
    return {
      mode: state.session.mode,
      user: state.session.user,
      accounts: state.accounts.accounts,
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
  state = {};

  componentWillMount() {
    this.props.fetchAccounts();
    this.isMobileDevice = window.outerWidth < 992; // 992 is col-md bootstrap (for adaptive design)

    this.prepareEntityOptions();
  }

  prepareEntityOptions() {
    const { user } = this.props;

    console.log(user.tags);

    this.entityOptions = [
      {
        value: 'transaction',
        id: 'combined',
        label: 'Combined Report',
      },
      {
        value: 'payment',
        id: 'payment',
        label: 'Payments',
      },
      {
        value: 'refund',
        id: 'refund',
        label: 'Refunds',
      },
      {
        value: 'settlement',
        id: 'settlement',
        label: 'Settlements',
      },
      {
        value: 'invoice',
        id: 'invoice',
        label: 'Monthly Invoice',
      },
    ];

    if (user.tags.indexOf('Broking_Report') !== -1) {
      this.entityOptions.push({
        value: 'broking',
        id: 'broking',
        label: 'Broking Reports', //
      });
    }

    // DSP Report is only for DSP Blackrock Merchant. Should not be enabled for any other merchants
    if (user.tags.indexOf('Dsp_Report') !== -1) {
      this.entityOptions.push({
        value: 'dsp_report',
        id: 'dsp_report',
        label: 'DSP Transaction Report',
      });
    }

    if (user.tags.indexOf('Rpp_report') !== -1) {
      this.entityOptions.push({
        value: 'rpp_report',
        id: 'rpp_report',
        label: 'e-Mitra Report',
      });
    }

    if (user.tags.indexOf('Payment_Link_Report') !== -1) {
      this.entityOptions.push({
        value: 'payment_link',
        id: 'payment_link',
        label: 'Payment Link',
      });
    }

    if (user.tags.indexOf('Marketplace') !== -1) {
      this.entityOptions.push({
        value: 'transfer',
        id: 'transfer',
        label: 'Transfers',
      });

      this.entityOptions.push({
        value: 'reversal',
        id: 'reversal',
        label: 'Reversals',
      });
    }
  }

  prepareGenerateReport = values => {
    let { entity, type, date } = values;
    const account_id = this.state.merchantSelected.id;

    let data = {
      month: date.month() + 1, // Jan is 0 in moment library
      year: date.year(),
    };

    if (entity === 'invoice') {
      return Promise.resolve(
        window.open(
          `/${this.props.mode}/reports/invoice?year=${data.year}&month=${month}`,
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
      data.account_id = 'acc_' + account_id; // It will be handled at api level later
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
    let { entity, type, mode, user, date, handleSubmit } = this.props;
    let isMarketplace = user.tags.indexOf('Marketplace') !== -1;

    isMarketplace = true;

    let merchantAccounts = this.props.accounts
      ? Object.assign([], this.props.accounts)
      : [];

    merchantAccounts.push({
      name: user.name,
      id: user.current,
      email: user.email,
      tag: 'My Account',
      tagIcon: 'icon-account',
    });

    return (
      <tabbed-container>
        <header>
          <NavLink to="/reports">Download Reports</NavLink>
        </header>
        <div class="report-wrapper col-lg-8 col-sm-10 col-xs-11">
          {/*Report Type Selection*/}
          <div
            class={`col-lg-4 col-md-4 col-sm-12 col-xs-12 report-list-panel report-list-panel${this.isMobileDevice ? '--mobile' : '--desktop'}`}
          >
            <div class="title">SELECT REPORT TYPE</div>
            {this.isMobileDevice
              ? <PowerSelect
                  options={this.entityOptions}
                  searchEnabled={false}
                  selected={this.state.entity}
                  onChange={({ option }) => {
                    this.setState({ entity: option }); // only for powerselect view otherwise not consumed elsewhere
                    this.props.change('entity', option.value); // programmatically set redux-form 'entity' otherwise, powerselect closes before redux-form is updated
                  }}
                  optionComponent={({ option }) => (
                    <div class="reports-entity-options">
                      <Field
                        name="entity"
                        value={option.value}
                        id={option.id}
                        component="input"
                        type="radio"
                        class="report-type form-control"
                      />
                      <label for={option.id}>{option.label}</label>
                    </div>
                  )}
                  selectedOptionComponent={({ option }) => (
                    <div>{option.label}</div>
                  )}
                />
              : <div>
                  {this.entityOptions.map((option, index) => (
                    <div class="reports-entity-options" key={index}>
                      <Field
                        name="entity"
                        value={option.value}
                        id={option.id}
                        component="input"
                        type="radio"
                        class="report-type form-control"
                      />
                      <label for={option.id}>{option.label}</label>
                    </div>
                  ))}
                </div>}
          </div>
          {/*Report Generate Panel*/}
          <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12 report-generate-panel">
            <div class="form-element">
              <div class="title">
                {isMarketplace &&
                  ['transaction', 'payment', 'refund', 'settlement'].indexOf(
                    this.props.entity
                  ) > -1
                  ? 'SELECT '
                  : ''}
                ACCOUNT
              </div>
              {isMarketplace &&
                ['transaction', 'payment', 'refund', 'settlement'].indexOf(
                  this.props.entity
                ) > -1
                ? <div class="custom-select" style={{ position: 'relative' }}>
                    <i class="icon icon-search custom-icon" />
                    <div
                      class="typeAheadSkin"
                      ref={typeAheadSkin => {
                        this.typeAheadSkin = typeAheadSkin;
                      }}
                    >
                      {this.state.merchantSelected
                        ? <div>
                            <b style={{ marginRight: '5' }}>
                              {this.state.merchantSelected.name}
                            </b>
                            <span>- {this.state.merchantSelected.id}</span>
                            <span
                              class={`${this.isMobileDevice ? this.state.merchantSelected.tagIcon + ' icon' : ''} custom-tag`}
                            >
                              {this.isMobileDevice
                                ? ''
                                : this.state.merchantSelected.tag}
                            </span>
                          </div>
                        : null}
                    </div>

                    <TypeAhead
                      options={merchantAccounts}
                      placeholder="Search for merchant Name/Email/Merchant ID"
                      optionLabelPath="name"
                      onClick={() => {
                        this.typeAheadSkin.classList.add('hide');
                      }}
                      onBlur={() => {
                        this.typeAheadSkin.classList.remove('hide');
                      }}
                      searchIndices={['name', 'id', 'email']}
                      selected={this.state.merchantSelected}
                      optionComponent={({ option }) => (
                        <div style={{ padding: 5 }}>
                          <b style={{ marginRight: '5' }}>{option.name}</b>
                          <span>- {option.id}</span>
                          <span
                            class={`${this.isMobileDevice ? option.tagIcon + ' icon' : ''} custom-tag`}
                          >
                            {this.isMobileDevice ? '' : option.tag}
                          </span>
                        </div>
                      )}
                      selectedOptionComponent={({ option }) => (
                        <div>
                          <b style={{ marginRight: '5' }}>{option.name}</b>
                          <span>- {option.id}</span>
                        </div>
                      )}
                      onChange={({ option }) => {
                        if (option) {
                          this.setState({ merchantSelected: option });
                        }
                      }}
                    />
                  </div>
                : <div class="account">
                    <strong>{user.name || user.user.name}</strong>
                    {' '}
                    -
                    {' '}
                    {user.email || user.user.email}
                  </div>}
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
                      component={ReduxDatetime}
                      dateFormat="MMM, YYYY"
                      closeOnSelect={true}
                      isValidDate={validYear}
                      placeholder="Select Year-Month"
                      timeFormat={false}
                    />
                  </div>
                </div>}

              {type === 'daily' &&
                entity !== 'invoice' &&
                <div class="col-sm-4">
                  <div class="form-group">
                    <Field
                      name="date"
                      dateFormat="DD MMM, YYYY"
                      closeOnSelect={true}
                      component={ReduxDatetime}
                      placeholder="Select Date-Month-Year"
                      isValidDate={validYear}
                      timeFormat={false}
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
