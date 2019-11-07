import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { saveAs } from 'file-saver';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import moment from 'moment';

import { titleCase } from 'rzp/utils/rzp-utils';
import scrollTo from 'rzp/utils/scrollTo';
import { prefixEntityValue } from 'common/data';
import ReduxDatetime from 'rzp/ui/ReduxDatetime';
import * as NotificationsActions from 'rzp/modules/notifications';
import AccountsList from 'rzp/ui/AccountsList/index.js';
import { openModal, closeModal } from 'rzp/modules/modals';
import debounce from 'rzp/utils/debounce';

import ModalHeader from 'rzp/ui/ModalHeader';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import SelectConfig from 'merchant_common/components/Reports/SelectConfig';
import ReportLoader from 'merchant_common/components/Reports/ReportLoader';
import EmailReportx from 'merchant_common/components/Reports/EmailReport';

// Please refactor everything in this file if you're working in it
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
    message: 'Your report request is being placed...',
  };

export default function Reports(store, opts) {
  const { data, modelActions, fetchAccountsApi, ga, isPartnerReport } = opts;
  const { getCustomConfig, marketplaceConfigTypes, rzpConfigOrder } = data;

  const {
    getConfigs,
    generateReport,
    emailReportV2,
    generateReportV2,
    addReportToList,
    updateReportInList,
    removeReportFromList,
    addPollInstance,
  } = modelActions;

  const {
    trackDownload,
    trackReportTabsClick,
    trackReportActions,
    trackTimeLapse,
  } = ga;

  const EmailReport = EmailReportx({
    emailReportV2: modelActions.emailReportV2,
    marketplaceConfigTypes,
    isPartnerReport,
    ga,
  });

  @connect(
    state => {
      return {
        mode: state.session.mode,
        user: state.session.user,
        currentReportList: state.reports.currentReportList,
        pollInstances: state.reports.pollInstances,
        type: selector(state, 'type'),
        date: selector(state, 'date'),
        invoiceDate: selector(state, 'invoiceDate'),
        reportType: selector(state, 'reportType'),
        dateRangeData: selector(
          state,
          'withTime',
          'startAt',
          'endAt',
          'startAtTime',
          'endAtTime'
        ),
        config: state.config,
      };
    },
    {
      ...NotificationsActions,
      openModal,
      closeModal,
      addReportToList,
      updateReportInList,
      removeReportFromList,
      addPollInstance,
    }
  )
  @reduxForm({
    form: 'generateReports',
    initialValues: {
      type: 'daily',
      date: moment(),
      startAt: moment().subtract('1', 'days'),
      endAt: moment(),
      startAtTime: moment().startOf('day'),
      endAtTime: moment(), // 24 hours
      // Merchant can not download invoice of current month
      invoiceDate: moment()
        .subtract(1, 'months')
        .startOf('month'),
    },
  })
  class ReportsContainer extends Component {
    constructor(props) {
      super(props);
      const { user, currentReportList, pollInstances } = props,
        tags = user.tags.map(tag => tag.toLowerCase()),
        configs = [],
        accounts = [],
        configRequest = getConfigs(isPartnerReport).catch(requestFailedFunc),
        promises = [configRequest];

      const monthlyInvoiceConfig = getCustomConfig('monthlyInvoice');
      if (
        monthlyInvoiceConfig &&
        user.isOrgAllowedFunctionality('monthlyInvoice')
      ) {
        configs.push(monthlyInvoiceConfig);
      }

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
        currentReportList,
        pollInstances,
        disableDownloadButton: false,
      };

      this.onConfigChange = ::this.onConfigChange;
      this.onAccountChange = ::this.onAccountChange;
      this.generateReport = debounce(::this.generateReport, 500);
      this.validateInvoiceMonthYear = ::this.validateInvoiceMonthYear;

      store.subscribe(() => {
        //update state when report list store changes
        this.setState({
          currentReportList: store.getState().reports.currentReportList,
          pollInstances: store.getState().reports.pollInstances,
        });
      });

      this.configsLableMap = {};
    }

    onConfigChange({ option }) {
      trackReportTabsClick(option.label);
      this.setState({ selectedConfig: option });
      this.setFileFormat(option);
      this.enableDownloadButton();
    }

    onAccountChange(account) {
      this.setState({ selectedAccount: account });
      this.enableDownloadButton();
    }

    setFileFormat = config => {
      if (config && config.type !== 'custom') {
        let fileFormat = this.getFileFormat(config._item);

        this.props.change('reportType', fileFormat);
      }
    };

    getFileFormat = config => {
      return ((config.template || {}).file_meta || {}).extension || 'csv';
    };

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
      this.isMobileDevice = window.innerWidth < 992;
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
              const resultantConfigs = [];

              configResp.data.items.forEach(configItem => {
                // Excluding RazorpayX Reports
                if (configItem.name && configItem.name.indexOf('RX') === 0) {
                  return;
                }

                const { type, description } = configItem,
                  config = {
                    label: configItem.name,
                    value: configItem.id,
                    referred_accounts: (configItem.template || {})
                      .referred_accounts,
                    type,
                    description,
                    _item: configItem,
                  };

                resultantConfigs.push(config);
              });

              configs = resultantConfigs.concat(configs);
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
            this.setFileFormat(configs[0]);
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

    updateStore = (data, shouldInitialize) => {
      let downloadTimeLapse = new Date().getTime() - data.created_at * 1000;

      if (shouldInitialize) {
        this.props.addReportToList(data);
        trackTimeLapse('Download Start', downloadTimeLapse);
      } else {
        this.props.updateReportInList(data);
      }
    };

    onPollStart = (reportId, pollInstance) => {
      this.props.addPollInstance(reportId, pollInstance);
      const reportProgressElement = document.querySelector(
        '#report-progress-' + reportId
      );

      if (reportProgressElement) {
        scrollTo({
          endPos: reportProgressElement.getBoundingClientRect().top,
          animation: 'ease-in-out',
        });
      }
    };

    disableDownloadButton = () => {
      this.setState({
        disableDownloadButton: true,
      });
    };

    enableDownloadButton = () => {
      if (this.state.disableDownloadButton) {
        this.setState({
          disableDownloadButton: false,
        });
      }
    };

    generateReport() {
      if (typeof window.hj === 'function') {
        window.hj('trigger', 'download_report');
        window.hj('tagRecording', ['download_report']);
      }

      this.disableDownloadButton();

      let selectedConfig = { ...this.state.selectedConfig };
      const { selectedAccount, currentReportList } = this.state,
        { date, type, invoiceDate, reportType, dateRangeData } = this.props,
        day = date.date(),
        month = date.month() + 1, // Jan is 0 in moment library
        year = date.year(),
        titleForTracking = `${titleCase(type)} ${selectedConfig.label} Report`,
        descForTracking = type === 'daily' ? `date` : `month`;

      //tracking vars for reports v2
      let reportActionTypeForTracking = 'Download Report';

      if (selectedConfig.value === 'monthlyInvoice') {
        const month = invoiceDate.month() + 1,
          year = invoiceDate.year();

        trackDownload(titleForTracking, `month`);

        trackReportActions(
          reportActionTypeForTracking,
          type,
          invoiceDate,
          titleForTracking
        );

        return window.open(
          `/${this.props.mode}/reports/invoice` +
            `?year=${year}` +
            `&month=${month}`,
          '_blank'
        );
      } else {
        trackDownload(titleForTracking, descForTracking);

        trackReportActions(
          reportActionTypeForTracking,
          type,
          date,
          titleForTracking
        );

        if (selectedConfig.type !== 'custom') {
          var startTime, endTime;
          switch (type) {
            case 'daily':
            case 'monthly': {
              const timeFactor = type === 'daily' ? 'day' : 'month';
              startTime = date
                .clone()
                .startOf(timeFactor)
                .unix();
              endTime = date
                .clone()
                .endOf(timeFactor)
                .unix();
              break;
            }

            case 'dateRange': {
              [startTime, endTime] = getFullUnixTimeStamps(dateRangeData);
              break;
            }
          }

          const { user } = this.props,
            selectedAccountId = (selectedConfig.type in marketplaceConfigTypes
              ? selectedAccount.id
              : this.defaultAccount.id
            ).replace('acc_', ''),
            isMerchantAccount = selectedAccountId === user.current,
            reqData = {
              config_id: selectedConfig._item.id,
              generated_by: user.user.id,
              start_time: startTime,
              end_time: endTime,
              // report file type option has to override default configs
              ...(reportType !== this.getFileFormat(selectedConfig._item) && {
                template_overrides: {
                  file_meta: {
                    extension: reportType,
                    delimiter: ',',
                  },
                },
              }),
            };

          this.props.showNotification(downloadStartedMessage);

          return generateReportV2(
            reqData,
            isMerchantAccount,
            this.updateStore,
            this.onPollStart,
            isPartnerReport
          ).then(data => {
            if (data.error) {
              if (typeof window.hj === 'function') {
                window.hj('tagRecording', [
                  'download_report_failed',
                  this.props.user.current,
                ]);
              }
              return this.props.showNotification({
                type: 'error',
                message: data.error,
              });
            }

            if (typeof window.hj === 'function') {
              window.hj('tagRecording', [
                'download_report_success',
                this.props.user.current,
              ]);
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

            if (typeof window.hj === 'function') {
              window.hj('tagRecording', [
                'download_report_success',
                this.props.user.current,
              ]);
            }

            location.href = data.data.url;
          })
          .catch(e => {
            if (typeof window.hj === 'function') {
              window.hj('tagRecording', [
                'download_report_failed',
                this.props.user.current,
              ]);
            }

            this.props.showNotification({
              type: 'error',
              message: 'No data found for given time range',
            });
          });
      }
    }

    openEmailReportModal = e => {
      let transactionReportEmail;

      if (this.props.config) {
        transactionReportEmail = this.props.config.transaction_report_email;
      }

      const { user, type, date, ga, dateRangeData } = this.props;
      const { selectedAccount, selectedConfig } = this.state;
      const reportId = e.target.dataset.reportid;

      let emailsMap = {};

      // save email priority based on following precedence
      // contact_email > transaction_report_email > account

      emailsMap[user.user.email] = 3; //email of logged in user
      emailsMap[user.email] = 3; //email of merchant (can be different when merchant is sub-merchant)

      if (transactionReportEmail) {
        transactionReportEmail.split(',').map(email => {
          emailsMap[email] = 2;
        });
      }

      if (user.contact_email) {
        emailsMap[user.contact_email] = 1;
      }

      this.props.openModal({
        size: 'small',
        component: (
          <EmailReport
            selectedType={type}
            selectedDate={date}
            dateRangeData={dateRangeData}
            getFullUnixTimeStamps={getFullUnixTimeStamps}
            reportId={reportId}
            emailsMap={emailsMap}
            onSend={this.generateReport}
            selectedAccount={selectedAccount}
            selectedConfig={selectedConfig}
            closeModal={this.props.closeModal}
            defaultAccount={this.defaultAccount}
            updateStore={this.updateStore}
            configsLableMap={this.configsLableMap}
          />
        ),
      });
    };

    //sort configs in the following order
    // 1. merchant custom report configs
    // 2. rzp owned report configs
    sortConfigs = configs => {
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
        //push `custom` types first
        if (config.type === 'custom') {
          rzpConfigs.push(config);
        }
      });

      rzpConfigs.sort((config1, config2) => {
        let index1 = rzpConfigOrder.indexOf(config1.label),
          index2 = rzpConfigOrder.indexOf(config2.label);

        return index1 - index2;
      });

      return [...merchantConfigs, ...rzpConfigs];
    };

    openCancelConfirmModal = reportId => {
      const { currentReportList } = this.state;
      const timeLapse = new Date().getTime();

      if (currentReportList[reportId]['status'] === 'created') {
        this.props.openModal({
          size: 'small',
          component: (
            <div>
              <ModalHeader title="Are you sure you want to stop the report download?" />
              <div class="modal-body report-cancel-download">
                {currentReportList[reportId]['emails'] && (
                  <p class="p-b">We will still email you this report.</p>
                )}
                <button class="btn btn-default" onClick={this.props.closeModal}>
                  No, don't
                </button>
                <button
                  class="btn btn-primary pull-right"
                  onClick={() => this.cancelReportDownload(reportId, timeLapse)}
                >
                  Yes, stop
                </button>
              </div>
            </div>
          ),
        });
      } else {
        this.cancelReportDownload(reportId);
      }
    };

    cancelReportDownload = (reportId, timeLapse) => {
      const { currentReportList, pollInstances } = this.state;

      timeLapse = new Date().getTime() - timeLapse;

      pollInstances[reportId].abort();

      this.props.closeModal();
      this.props.removeReportFromList(reportId);

      if (timeLapse) {
        trackTimeLapse('Click - Download Cancel', timeLapse);
      }
    };

    render() {
      const {
        isLoading,
        configs,
        accounts,
        selectedConfig,
        selectedAccount,
        currentReportList,
        disableDownloadButton,
      } = this.state;

      const { type, dateRangeData } = this.props;

      const entity = selectedConfig && selectedConfig.value;

      let content = null;

      Object.keys(currentReportList).forEach(reportId => {
        if (
          currentReportList[reportId]['config_id'] === selectedConfig.value &&
          currentReportList[reportId]['status'] === 'created'
        ) {
          isCurrentConfigSelected = true;
        }
      });

      if (isLoading) {
        content = (
          <div class={reportWrapperClasses}>
            {/*Report Type Selection*/}
            <SelectConfig isLoading={true} />
            {/*Report Generate Panel*/}
            <div class={reportPanelClasses} />
          </div>
        );
      } else {
        let configReportType = null;

        if (selectedConfig && selectedConfig.type !== 'custom') {
          configReportType = this.getFileFormat(selectedConfig._item);
        }

        const dateRangeError = getDateRangeError(dateRangeData);

        content = (
          <div class={reportWrapperClasses}>
            {/*Report Type Selection*/}
            <SelectConfig
              configs={configs}
              selectedConfig={selectedConfig}
              onConfigChange={this.onConfigChange}
              isMobileDevice={this.isMobileDevice}
            />
            {/*Report Generate Panel*/}
            <div class={reportPanelClasses}>
              {selectedConfig && (
                <>
                  {!this.isMobileDevice && (
                    <div class="form-heading">
                      {selectedConfig.label}
                      {selectedConfig.description && (
                        <small
                          class="help-block"
                          style={{ fontWeight: 'normal' }}
                        >
                          {selectedConfig.description}
                        </small>
                      )}
                    </div>
                  )}

                  {this.isMarketplaceEnabled &&
                  selectedConfig.type in marketplaceConfigTypes ? (
                    <div class="form-element">
                      <div class="title">SELECT ACCOUNT</div>
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
                    <div class="form-element">
                      <div class="title">ACCOUNT</div>
                      <div class="account">
                        <strong>{this.defaultAccount.name}</strong>
                      </div>
                    </div>
                  )}

                  <div class="form-element">
                    <div class="clearfix">
                      <div class="col-sm-3">
                        <div class="title">PERIOD</div>
                      </div>
                      {type === 'dateRange' &&
                        entity !== 'monthlyInvoice' && (
                          <>
                            <div class="col-sm-4 visible-sm visible-lg">
                              <div className="title">Start At</div>
                            </div>
                            <div class="col-sm- visible-lg visible-sm">
                              <div className="title">End At</div>
                            </div>
                          </>
                        )}
                    </div>
                    <div class="clearfix">
                      {entity === 'monthlyInvoice' || (
                        <div class="col-sm-3 col-xs-12">
                          <div
                            class="form-group form-control"
                            disabled={
                              isPartnerReport &&
                              selectedConfig.referred_accounts === 'all'
                            }
                          >
                            <Field
                              name="type"
                              class="fix-select"
                              component="select"
                              onChange={this.enableDownloadButton}
                            >
                              <option value="daily">Daily</option>
                              {!(
                                isPartnerReport &&
                                selectedConfig.referred_accounts === 'all'
                              ) && <option value="monthly">Monthly</option>}
                              <option value="dateRange">Custom</option>
                            </Field>
                          </div>
                          {type === 'dateRange' && (
                            <div class="form-group">
                              <div class="rzpCheckbox">
                                <Field
                                  name="withTime"
                                  id="with-time"
                                  component="input"
                                  type="checkbox"
                                  onChange={this.enableDownloadButton}
                                />
                                <label for="with-time" class="icon i-check">
                                  Specify time
                                </label>
                              </div>
                            </div>
                          )}
                        </div>
                      )}

                      {(type === 'monthly' || entity === 'monthlyInvoice') && (
                        <div class="col-sm-4 col-xs-12">
                          <div class="form-group">
                            <Field
                              name={
                                entity === 'monthlyInvoice'
                                  ? 'invoiceDate'
                                  : 'date'
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
                              onChange={this.enableDownloadButton}
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
                                onChange={this.enableDownloadButton}
                              />
                            </div>
                          </div>
                        )}

                      {type === 'dateRange' &&
                        entity !== 'monthlyInvoice' && (
                          <>
                            <div class="col-sm-4 col-xs-12">
                              <div class="form-group">
                                <Field
                                  name="startAt"
                                  dateFormat="DD MMM, YYYY"
                                  placeholder="Starts at"
                                  component={ReduxDatetime}
                                  timeFormat={false}
                                  isValidDate={validYear}
                                  closeOnSelect
                                  onChange={this.enableDownloadButton}
                                />
                                {dateRangeData.withTime && (
                                  <Field
                                    name="startAtTime"
                                    placeholder="Select Time"
                                    component={ReduxDatetime}
                                    closeOnSelect
                                    dateFormat={false}
                                    class="m-t"
                                    onChange={this.enableDownloadButton}
                                  />
                                )}
                              </div>
                            </div>
                            <div
                              class="col-sm-4 col-xs-12"
                              style={{ marginRight: '0' }}
                            >
                              <div class="form-group">
                                <Field
                                  name="endAt"
                                  dateFormat="DD MMM, YYYY"
                                  placeholder="Ends At"
                                  component={ReduxDatetime}
                                  timeFormat={false}
                                  isValidDate={isDateRangeEndAtValid(
                                    dateRangeData.startAt
                                  )}
                                  closeOnSelect
                                  onChange={this.enableDownloadButton}
                                />
                                {dateRangeData.withTime && (
                                  <Field
                                    name="endAtTime"
                                    component={ReduxDatetime}
                                    closeOnSelect
                                    dateFormat={false}
                                    class="m-t"
                                    onChange={this.enableDownloadButton}
                                  />
                                )}
                              </div>
                            </div>
                          </>
                        )}
                    </div>
                    <div class="clearfix">
                      <div className="col-sm-8 col-xs-12">
                        {type === 'dateRange' &&
                          dateRangeError && (
                            <small class="text-danger">{dateRangeError}</small>
                          )}
                      </div>
                    </div>
                  </div>

                  {/* File type for Reports */}
                  {selectedConfig.type !== 'custom' && (
                    <div class="form-element">
                      <div class="title">SELECT FILE FORMAT</div>
                      <div class="col-sm-3 col-xs-12">
                        <div class="form-group form-control">
                          <Field
                            name="reportType"
                            class="fix-select"
                            component="select"
                            onChange={this.enableDownloadButton}
                          >
                            {/* Add option on the fly for txt, tsv or other formats */}
                            {['csv', 'xlsx', 'xls'].indexOf(configReportType) <
                              0 && (
                              <option value={configReportType}>
                                {configReportType.toUpperCase()}
                              </option>
                            )}
                            <option value="csv">CSV</option>
                            <option value="xlsx">Excel (xlsx)</option>
                            <option value="xls">Old Excel (xls)</option>
                          </Field>
                        </div>
                      </div>
                    </div>
                  )}

                  <div class="form-element">
                    <button
                      class="btn btn-primary"
                      onClick={this.generateReport}
                      disabled={
                        disableDownloadButton ||
                        (type === 'dateRange' && !!dateRangeError)
                      }
                    >
                      Download Report
                    </button>
                    {selectedConfig.type !== 'custom' && (
                      <button
                        class="btn btn-default m-l"
                        onClick={this.openEmailReportModal}
                        disabled={type === 'dateRange' && !!dateRangeError}
                      >
                        Email Report
                      </button>
                    )}
                    <ReportLoader
                      reportList={currentReportList}
                      openEmailReportModal={this.openEmailReportModal}
                      configsLableMap={this.configsLableMap}
                      cancelDownload={this.openCancelConfirmModal}
                      selectedConfigId={selectedConfig.value}
                    />
                  </div>
                </>
              )}
            </div>
          </div>
        );
      }

      return (
        <div>
          <tabbed-container>
            <header>
              <NavLink to={isPartnerReport ? '/partners/reports' : '/reports'}>
                Download Reports
              </NavLink>
            </header>
            <TestModeBanner />
            <content>{content}</content>
          </tabbed-container>
        </div>
      );
    }
  }

  return ReportsContainer;
}

function isDateRangeEndAtValid(startAt) {
  return current => {
    if (!startAt) return true;
    if (!validYear(current)) return false;
    const difference = current.diff(startAt, 'days');
    return difference >= 0 && difference < 7;
  };
}

function getDateRangeError(dateRangeData) {
  if (dateRangeData.endAt.diff(dateRangeData.startAt, 'days') > 7) {
    return 'Date range cannot exceed period of 7 days';
  } else {
    const [startAtStamp, endAtStamp] = getFullUnixTimeStamps(dateRangeData);
    if (endAtStamp < startAtStamp) {
      return "Start at date can't exceed end at date";
    }
  }
  return false;
}

function getFullUnixTimeStamps(data) {
  return [
    getFullStartTimeStamp(data.startAt, data.withTime && data.startAtTime),
    getFullEndTimeStamp(data.endAt, data.withTime && data.endAtTime),
  ];
}

function getFullStartTimeStamp(startAtMoment, startAtTimeMoment) {
  return (
    getDateUnix(startAtMoment) +
    (!!startAtTimeMoment ? getTimeUnix(startAtTimeMoment) : 0)
  );
}

function getFullEndTimeStamp(endAtMoment, endAtTimeMoment) {
  return (
    getDateUnix(endAtMoment) +
    (!!endAtTimeMoment ? getTimeUnix(endAtTimeMoment) : 86399)
  ); // end of day
}

function getDateUnix(dateMoment) {
  return dateMoment
    .clone()
    .startOf('day')
    .unix();
}

function getTimeUnix(timeMoment) {
  return timeMoment.diff(timeMoment.clone().startOf('day'), 'seconds');
}
