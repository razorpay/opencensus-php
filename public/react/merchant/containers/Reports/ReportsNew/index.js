import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import AsyncButton from 'react-async-button';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import moment from 'moment';

import ReduxDatetime from 'rzp/ui/ReduxDatetime';
import * as NotificationsActions from 'rzp/modules/notifications';
import AccountsList from 'rzp/ui/AccountsList';
import poll from 'rzp/utils/poll/longPoll';

import { fetchAccountsApi } from 'merchant/modules/marketplace/accounts';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import { getConfigs, createLog, getLog } from 'merchant/modules/reports';
import { getCustomConfig, marketplaceConfigTypes } from './data';
import SelectConfig from 'merchant/components/Reports/ReportsNew/SelectConfig';

const validYear = current => {
  return current._d.getTime() <= Date.now() && current.year() >= 2015;
};

const selector = formValueSelector('generateReports');

const requestFailedFunc = () => {
    return {
      success: false,
      errors: ['Failed to fetch data'],
    };
  },
  defaultSelectedConfigType = 'payments';

@connect(
  state => {
    return {
      mode: state.session.mode,
      user: state.session.user,
      type: selector(state, 'type'),
      date: selector(state, 'date'),
      invoiceDate: selector(state, 'invoiceDate'),
    };
  },
  { ...NotificationsActions }
)
@reduxForm({
  form: 'generateReports',
  initialValues: {
    type: 'daily',
    date: moment(),
    // Merchant can not download invoice of current month
    invoiceDate: moment()
      .subtract(1, 'months')
      .startOf('month'),
  },
})
export default class ReportsContainer extends Component {
  constructor(props) {
    super(props);

    const { user } = props,
      tags = user.tags,
      configs = [getCustomConfig('monthlyInvoice')],
      accounts = [],
      configRequest = getConfigs().catch(requestFailedFunc),
      promises = [configRequest];

    this.defaultAccount = {
      name: user.name || user.user.name,
      id: user.current,
      email: user.email,
      tag: 'My Account',
      tagIcon: 'icon-account',
    };

    // populate custom configs
    if (tags.indexOf('Broking_Report') !== -1) {
      configs.push(getCustomConfig('broking'));
    }

    if (tags.indexOf('Rpp_Report') !== -1) {
      configs.push(getCustomConfig('emitra'));
    }

    if (tags.indexOf('Dsp_Report') !== -1) {
      configs.push(getCustomConfig('dsp'));
    }

    // if markerplace is enabled, get and show linked accounts
    this.isMarketplaceEnabled = user.isMarketplaceEnabled;

    if (this.isMarketplaceEnabled) {
      const accountsRequest = fetchAccountsApi().catch(requestFailedFunc);

      promises.push(accountsRequest);

      accounts.push(this.defaultAccount);
    }

    this.requests = Promise.all(promises);

    this.state = {
      isLoading: true,
      configs,
      accounts,
      date: moment(),
      // Merchant can not download invoice of current month
      invoiceDate: moment()
        .subtract(1, 'months')
        .startOf('month'),
    };

    this.onConfigChange = ::this.onConfigChange;
    this.onAccountChange = ::this.onAccountChange;
    this.generateReport = ::this.generateReport;
    this.validateInvoiceMonthYear = ::this.validateInvoiceMonthYear;
  }

  onConfigChange({ option }) {
    this.setState({ selectedConfig: option });
  }

  onAccountChange(account) {
    this.setState({ selectedAccount: account });
  }

  validateInvoiceMonthYear(current) {
    const isGSTDisabled = this.props.user.isGSTDisabled;

    const currentMonth = current.month(),
      currentYear = current.year();

    const currDate = new Date();

    if (
      currentYear === currDate.getFullYear() &&
      currentMonth > currDate.getMonth() - 1
    ) {
      return false;
    }

    // disable invoice download for july(6)  and august(7)
    // for the year of 2017
    const isValidMonth =
      isGSTDisabled && current.year() === 2017
        ? currentMonth !== 6 && currentMonth !== 7
        : true;

    return validYear(current) && isValidMonth;
  }

  componentWillMount() {
    // 992 is col-md bootstrap (for adaptive design)
    this.isMobileDevice = window.outerWidth < 992;

    this.requests.then(resps => {
      const { 0: configResp, 1: accountsResp } = resps,
        { configs, accounts } = this.state;

      if (configResp.success) {
        let selectedConfig = null;

        if (this.isMarketplaceEnabled) {
          if (!accountsResp.success) {
            // TODO: handle error
            return;
          }

          accounts.splice(1, 0, ...accountsResp.data.items);
        }

        let hasConfigs =
          !!configResp.data.items && configResp.data.items.length > 0;

        if (hasConfigs) {
          configResp.data.items.forEach(configItem => {
            if (
              configItem.type in marketplaceConfigTypes &&
              !this.isMarketplaceEnabled
            ) {
              return;
            }

            const { type, description } = configItem,
              config = {
                label: configItem.name,
                value: configItem.id,
                type,
                description,
                _item: configItem,
              };

            configs.unshift(config);

            if (type === defaultSelectedConfigType) {
              selectedConfig = config;
            }
          });

          if (!selectedConfig) {
            selectedConfig = configs[0];
          }
        }

        this.setState({
          isLoading: false,
          configs,
          selectedConfig,
          accounts,
          selectedAccount: this.defaultAccount,
        });
      } else {
        // TODO: handle error
      }
    });
  }

  generateReport() {
    const { selectedConfig, selectedAccount } = this.state,
      { date, type, invoiceDate } = this.props;

    if (selectedConfig.type !== 'custom') {
      const timeFactor = type === 'daily' ? 'day' : 'month',
        startTime = date
          .clone()
          .startOf(timeFactor)
          .unix(),
        endTime = date
          .clone()
          .endOf(timeFactor)
          .unix();

      return createLog({
        config_id: selectedConfig._item.id,
        generated_by: selectedAccount.id,
        start_time: startTime,
        end_time: endTime,
      })
        .then(resp => {
          if (!resp.success) {
            // TODO: handle error
            return;
          }

          const logId = resp.data.id;

          const logPoll = poll({
            fetchFunc: () => getLog(resp.data.id),
            validator: resp => resp.error || resp.data.file_id,
            minWaitTime: 2000,
          });

          logPoll.promise
            .then(resp => {
              if (resp.error) {
                // TODO: Handle error
                return;
              }

              console.log(resp);
            })
            .catch(() => {
              // TODO: Handle error
            });
        })
        .catch(() => {
          // TODO: Handle error
        });
    } else if (selectedConfig.value === 'monthlyInvoice') {
      return window.open(
        `/${this.props.mode}/reports/invoice` +
          `?year=${invoiceDate.year()}` +
          `&month=${invoiceDate.month() + 1}`,
        '_blank'
      );
    }
  }

  render() {
    const {
      isLoading,
      hasConfigs,
      configs,
      accounts,
      selectedConfig,
      selectedAccount,
    } = this.state;

    const { type, date, invoiceDate } = this.props;

    if (isLoading) {
      // TODO: need to put spinner
      return null;
    }

    if (configs.length === 0) {
      return <center>No downloadable reports found for your account.</center>;
    }

    const entity = selectedConfig.value;

    return (
      <tabbed-container>
        <header>
          <NavLink to="/reports">Download Reports</NavLink>
        </header>
        <TestModeBanner />
        <content>
          {/*Report Type Selection*/}
          <div class="report-wrapper col-lg-8 col-sm-10 col-xs-11">
            <SelectConfig
              configs={configs}
              selectedConfig={selectedConfig}
              onConfigChange={this.onConfigChange}
              isMobileDevice={this.isMobileDevice}
            />
            {/*Report Generate Panel*/}
            <div
              className={
                'col-lg-8 col-md-8 col-sm-12 col-xs-12' +
                ' report-generate-panel'
              }
            >
              {!this.isMobileDevice && (
                <div class="form-heading">{selectedConfig.label}</div>
              )}
              {selectedConfig.type in marketplaceConfigTypes ? (
                <div className="form-element">
                  <div className="title">SELECT ACCOUNT</div>
                  <AccountsList
                    accounts={accounts}
                    selectedAccount={selectedAccount}
                    onChange={this.onAccountChange}
                  />
                  <small class="help-block">
                    <i class="icon icon-info-circle" />
                    <span>
                      You can also select a linked account from the list
                    </span>
                  </small>
                </div>
              ) : (
                <div className="form-element">
                  <div className="title">ACCOUNT</div>
                  <div class="account">
                    <strong>{this.defaultAccount.name}</strong>
                  </div>
                </div>
              )}

              <div class="form-element">
                <div class="title">PERIOD</div>
                {entity === 'monthlyInvoice' || (
                  <div class="col-sm-3 col-xs-12">
                    <div class="form-group form-control">
                      <Field name="type" class="fix-select" component="select">
                        <option value="daily">Daily</option>
                        <option value="monthly">Monthly</option>
                      </Field>
                    </div>
                  </div>
                )}

                {(type === 'monthly' || entity === 'monthlyInvoice') && (
                  <div class="col-sm-4 col-xs-12">
                    <div class="form-group">
                      <Field
                        name={
                          entity === 'monthlyInvoice' ? 'invoiceDate' : 'date'
                        }
                        component={ReduxDatetime}
                        dateFormat="MMM, YYYY"
                        closeOnSelect={true}
                        isValidDate={
                          entity === 'invoice'
                            ? this.validateInvoiceMonthYear
                            : validYear
                        }
                        placeholder="Select Year-Month"
                        timeFormat={false}
                      />
                    </div>
                  </div>
                )}

                {type === 'daily' &&
                  entity !== 'monthlyInvoice' && (
                    <div class="col-sm-4 col-xs-12">
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
                    </div>
                  )}
              </div>

              <div class="form-element">
                <AsyncButton
                  class="btn btn-primary"
                  onClick={this.generateReport}
                  text="Generate and Download Report"
                  pendingText="Generating..."
                />

                {selectedConfig.description && (
                  <footer style={{ marginTop: '16px' }}>
                    {selectedConfig.description}
                  </footer>
                )}
              </div>
            </div>
          </div>
        </content>
      </tabbed-container>
    );
  }
}
