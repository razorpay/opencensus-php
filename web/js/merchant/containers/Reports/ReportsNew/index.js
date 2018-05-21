import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { saveAs } from 'file-saver';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import moment from 'moment';

import { titleCase } from 'rzp/utils/rzp-utils';
import { prefixEntityValue } from 'common/data';
import ReduxDatetime from 'rzp/ui/ReduxDatetime';
import * as NotificationsActions from 'rzp/modules/notifications';
import AccountsList from 'rzp/ui/AccountsList/index.js';
import { openModal, closeModal } from 'rzp/modules/modals';
import store from 'merchant/store';

import ModalHeader from 'rzp/ui/ModalHeader';
import { fetchAccountsApi } from 'merchant/modules/marketplace/accounts';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import {
  getConfigs,
  generateReport,
  emailReportV2,
  generateReportV2,
  addReportToList,
  updateReportInList,
  removeReportFromList,
} from 'merchant/modules/reports';
import SelectConfig from 'merchant/components/Reports/ReportsNew/SelectConfig';
import EmailReport from 'merchant/components/Reports/ReportsNew/EmailReport';
import ReportLoader from 'merchant/components/Reports/ReportsNew/ReportLoader';

import {
  getCustomConfig,
  marketplaceConfigTypes,
  rzpConfigOrder,
} from './data';
import { trackDownload } from './ga';

const validYear = current => {
  return current._d.getTime() <= Date.now() && current.year() >= 2015;
};

const selector = formValueSelector('generateReports');

const reportWrapperClasses = 'report-wrapper col-lg-8 col-sm-10 col-xs-11',
  reportPanelClasses =
    'col-lg-8 col-md-8 col-sm-12 col-xs-12' + ' report-generate-panel';

const requestFailedFunc = () => {
    return {
      success: false,
      errors: ['Failed to fetch data'],
    };
  },
  downloadStartedMessage = {
    type: 'success',
    message: 'Your report will download shortly',
  };

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
  {
    ...NotificationsActions,
    openModal,
    closeModal,
    addReportToList,
    updateReportInList,
    removeReportFromList,
  }
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
      tags = user.tags.map(tag => tag.toLowerCase()),
      configs = [getCustomConfig('monthlyInvoice')],
      accounts = [],
      configRequest = getConfigs().catch(requestFailedFunc),
      promises = [configRequest];

    this.defaultAccount = {
      name: user.name || user.user.name,
      id: prefixEntityValue('account', user.current),
      email: user.email,
      tag: 'My Account',
      tagIcon: 'i-account',
    };

    // populate custom configs
    if (tags.indexOf('broking_report') !== -1) {
      configs.push(getCustomConfig('broking'));
    }

    if (tags.indexOf('rpp_report') !== -1) {
      configs.push(getCustomConfig('rpp_report'));
    }

    if (tags.indexOf('dsp_report') !== -1) {
      configs.push(getCustomConfig('dsp_report'));
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
      selectedConfig: configs[0],
      accounts,
      selectedAccount: accounts[0],
      date: moment(),
      // Merchant can not download invoice of current month
      invoiceDate: moment()
        .subtract(1, 'months')
        .startOf('month'),
      currentReportList: store.getState().reports.currentReportList,
    };

    this.onConfigChange = ::this.onConfigChange;
    this.onAccountChange = ::this.onAccountChange;
    this.generateReport = ::this.generateReport;
    this.validateInvoiceMonthYear = ::this.validateInvoiceMonthYear;

    store.subscribe(() => {
      //update state when report list store changes
      this.setState({
        currentReportList: store.getState().reports.currentReportList,
      });
    });

    this.configsLableMap = {};
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
    this.requests
      .then(resps => {
        const { 0: configResp, 1: accountsResp } = resps,
          { accounts } = this.state;

        let { configs } = this.state;

        if (configResp.success) {
          if (this.isMarketplaceEnabled) {
            if (!accountsResp.success) {
              this.props.showNotification({
                type: 'error',
                message: 'Unable to get your linked accounts',
              });
            } else {
              accounts.splice(1, 0, ...accountsResp.data.items);
            }
          }

          let hasConfigs =
            !!configResp.data.items && configResp.data.items.length > 0;

          if (hasConfigs) {
            configs = configResp.data.items
              .map(configItem => {
                const { type, description } = configItem,
                  config = {
                    label: configItem.name,
                    value: configItem.id,
                    type,
                    description,
                    _item: configItem,
                  };

                return config;
              })
              .concat(configs);
          }

          //sort configs
          configs = this.sortConfigs(configs);

          configs.map(
            config => (this.configsLableMap[config.value] = config.label)
          );

          this.setState({
            configs,
            selectedConfig: configs[0],
            accounts,
            selectedAccount: this.defaultAccount,
          });
        } else {
          return this.props.showNotification({
            type: 'error',
            message: 'Unable to get the Reports List',
          });
        }
      })
      .then(() => {
        this.setState({
          isLoading: false,
        });
      });
  }

  generateReport(_, emails = null, currentconfigId = null) {
    let selectedConfig = { ...this.state.selectedConfig };
    const { selectedAccount, currentReportList } = this.state,
      { date, type, invoiceDate } = this.props,
      day = date.date(),
      month = date.month() + 1, // Jan is 0 in moment library
      year = date.year(),
      titleForTracking = `${titleCase(type)} ${selectedConfig.label} Report`,
      descForTracking = type === 'daily' ? `date` : `month`;

    if (currentconfigId) {
      selectedConfig = currentReportList[currentconfigId];
    }

    if (selectedConfig.value === 'monthlyInvoice') {
      const month = invoiceDate.month() + 1,
        year = invoiceDate.year();

      trackDownload(titleForTracking, `month`);

      return window.open(
        `/${this.props.mode}/reports/invoice` +
          `?year=${year}` +
          `&month=${month}`,
        '_blank'
      );
    } else {
      trackDownload(titleForTracking, descForTracking);

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

        const { user } = this.props,
          selectedAccountId = (selectedConfig.type in marketplaceConfigTypes
            ? selectedAccount.id
            : this.defaultAccount.id
          ).replace('acc_', ''),
          isMerchantAccount = selectedAccountId === user.current,
          reqData = {
            config_id: currentconfigId || selectedConfig._item.id,
            generated_by: selectedAccountId,
            start_time: startTime,
            end_time: endTime,
            //add emails if selected
            ...(emails && { emails }),
          };

        if (emails) {
          return this.emailReport(reqData, isMerchantAccount);
        } else {
          this.props.showNotification(downloadStartedMessage);
        }

        return generateReportV2(
          reqData,
          isMerchantAccount,
          this.props.addReportToList
        ).then(data => {
          //return nothing if cancelled by user
          if (!this.state.currentReportList[reqData['config_id']]) {
            return;
          }
          if (data.error) {
            // this.props.removeReportFromList(selectedConfig.value);

            return this.props.showNotification({
              type: 'error',
              message: data.error,
            });
          }

          window.location = data.url;
        });
      }

      /*
       * Hardcoded - custom reports
       */

      const account_id = selectedAccount.id,
        entity = selectedConfig.value;

      let data = {
        month,
        year,
        //TODO: add emails if selected
        // ...(emails && { emails }),
      };

      if (type === 'daily') {
        data.day = day;
      }

      const ajaxParams = {
        url: '/reports/' + entity,
        data: data,
      };

      if (
        this.props.user.isMarketplaceEnabled &&
        account_id !== this.props.user.current
      ) {
        data.account_id = prefixEntityValue('account', account_id); // It will be handled at api level later
      }

      if (entity === 'broking') {
        ajaxParams.headers = {
          Accept:
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
      }

      return generateReport(ajaxParams)
        .payload.then(data => {
          this.props.showNotification(downloadStartedMessage);

          if (entity === 'broking') {
            var blob = new Blob([data], {
              type:
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
    }
  }

  emailToSentence = emails => {
    if (emails.length === 1) {
      return emails[0];
    } else {
      return `${emails[0]} and ${emails.length - 1} others`;
    }
  };

  emailReport = (params, isMerchantAccount) => {
    const { currentReportList } = this.state;
    let shouldUpdate = false,
      newParams = {};

    if (currentReportList[params.config_id]) {
      newParams = {
        ...currentReportList[params.config_id],
        emails: params.emails,
      };

      shouldUpdate = true;
    } else {
      newParams = params;
    }

    return emailReportV2(newParams, isMerchantAccount, shouldUpdate)
      .then(data => {
        if (data.error) {
          return this.props.showNotification({
            type: 'error',
            message: data.error,
          });
        }

        this.props.closeModal();
        if (shouldUpdate) {
          this.props.updateReportInList(data.data);
        }

        return this.props.showNotification({
          type: 'success',
          message: `Report will be emailed to ${this.emailToSentence(
            data.data.emails
          )} shortly`,
        });
      })
      .catch(err => console.log(err));
  };

  openEmailReportModal = e => {
    const { user } = this.props;
    const { accounts } = this.state;
    const configId = e.target.dataset.configid;

    let emails = [
      // first email will always be of owner
      user.contact_email,
      ...(user.transaction_report_email !== null &&
        user.transaction_report_email.split(',')),
      ...(accounts && accounts.map(acc => acc.email)),
    ];

    //unique items
    emails = [...new Set(emails)];

    //remove empty vals
    emails = emails.filter(email => email !== '');

    this.props.openModal({
      size: 'small',
      component: (
        <EmailReport
          closeModal={this.props.closeModal}
          emails={emails}
          onSend={this.generateReport}
          configId={configId}
        />
      ),
    });
  };

  //sort configs in the following order
  // 1. merchant custom report configs
  // 2. rzp owned report configs
  sortConfigs = configs => {
    const rzpId = '100000Razorpay';
    const merchantId = this.props.user.user.id;

    let merchantConfigs = [],
      rzpConfigs = [];

    configs.forEach(config => {
      if (config._item) {
        if (config._item.merchant_id === merchantId) {
          merchantConfigs.push(config);
        } else {
          rzpConfigs.push(config);
        }
      }
    });

    rzpConfigs.sort((config1, config2) => {
      let index1 = rzpConfigOrder.indexOf(config1.label),
        index2 = rzpConfigOrder.indexOf(config2.label);

      return index1 - index2;
    });

    return [...merchantConfigs, ...rzpConfigs];
  };

  openCancelConfirmModal = config_id => {
    this.props.openModal({
      size: 'small',
      component: (
        <div>
          <ModalHeader
            title="Are you sure you want to stop the report download?"
            onCloseClick={this.props.closeModal}
          />
          <div class="modal-body">
            <p>We will still email you this report.</p>
            <button
              class="btn btn-default m-all"
              style={{ padding: '6px 30px' }}
              onClick={this.props.closeModal}
            >
              No, don't
            </button>
            <button
              class="btn btn-primary m-all"
              style={{ padding: '6px 30px' }}
              onClick={() => this.cancelReportDownload(config_id)}
            >
              Yes, stop
            </button>
          </div>
        </div>
      ),
    });
  };

  cancelReportDownload = config_id => {
    this.props.closeModal();
    this.props.removeReportFromList(config_id);
  };

  render() {
    const {
      isLoading,
      hasConfigs,
      configs,
      accounts,
      selectedConfig,
      selectedAccount,
      currentReportList,
    } = this.state;

    const { type, date, invoiceDate } = this.props;

    const entity = selectedConfig && selectedConfig.value;

    let content = null;

    if (isLoading) {
      content = (
        <div className={reportWrapperClasses}>
          {/*Report Type Selection*/}
          <SelectConfig isLoading={true} />
          {/*Report Generate Panel*/}
          <div className={reportPanelClasses} />
        </div>
      );
    } else {
      content = (
        <div className={reportWrapperClasses}>
          {/*Report Type Selection*/}
          <SelectConfig
            configs={configs}
            selectedConfig={selectedConfig}
            onConfigChange={this.onConfigChange}
            isMobileDevice={this.isMobileDevice}
          />
          {/*Report Generate Panel*/}
          <div className={reportPanelClasses}>
            {!this.isMobileDevice && (
              <div class="form-heading">
                {selectedConfig.label}
                {selectedConfig.description && (
                  <small
                    className="help-block"
                    style={{ fontWeight: 'normal' }}
                  >
                    {selectedConfig.description}
                  </small>
                )}
              </div>
            )}

            {this.isMarketplaceEnabled &&
            selectedConfig.type in marketplaceConfigTypes ? (
              <div className="form-element">
                <div className="title">SELECT ACCOUNT</div>
                <AccountsList
                  accounts={accounts}
                  selectedAccount={selectedAccount}
                  onChange={this.onAccountChange}
                />
                <small class="help-block">
                  <i class="i i-info-circle" />
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
                        entity === 'monthlyInvoice'
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
              {!currentReportList[selectedConfig.value] ? (
                <Fragment>
                  <button class="btn btn-primary" onClick={this.generateReport}>
                    Download Report
                  </button>
                  <button
                    class="btn btn-default m-l"
                    onClick={this.openEmailReportModal}
                  >
                    Email Report
                  </button>
                </Fragment>
              ) : (
                <small class="help-block">This report is being generated</small>
              )}
              <ReportLoader
                reportList={currentReportList}
                openEmailReportModal={this.openEmailReportModal}
                configsLableMap={this.configsLableMap}
                cancelDownload={this.openCancelConfirmModal}
                selectedConfig={selectedConfig.value}
              />
            </div>
          </div>
        </div>
      );
    }

    return (
      <tabbed-container>
        <header>
          <NavLink to="/reports">Download Reports</NavLink>
        </header>
        <TestModeBanner />
        <content>{content}</content>
      </tabbed-container>
    );
  }
}
