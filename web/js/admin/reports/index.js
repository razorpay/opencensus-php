import React, { Component } from 'react';
import { saveAs } from 'file-saver';

import { notifyError, notifySuccess } from 'common/modal';

import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import Form from 'ui/Form';
import { RadioField, SelectField, DateField } from 'ui/Field';
import fetch, { adminFetch } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import { getCustomConfig, generateReportV2 } from './helper';

import { getOrgId } from 'admin/user';

const defaultDate = moment().subtract(1, 'day');
const defaultMonth = moment().add(-1, 'month');

const paymentMethods = {
  'Debit Card': 'debit_card',
  'Credit Card': 'credit_card',
  UPI: 'upi',
  Wallet: 'wallet',
  'Net Banking': 'netbanking',
};

// TODO: Backend should send filters based on report. For now hard coding filters based on report name
const AllowedAdminReportsForPaymentFilter = [
  'Top 50 Merchants by transactions count',
  'Failed Transactions',
  'Top 50 Merchants by transactions volume',
];

export default class GenerateReports extends Component {
  constructor(props) {
    super(props);

    this.state = {
      configs: this.getPresetConfigs(),
      merchantAccounts: [],
      dateType: 'daily',
    };
  }

  isOrg = () => {
    if (this.props.merchantId) return false;
    else return true;
  };

  getPresetConfigs = () => {
    if (this.isOrg()) {
      return [];
    }

    let configs = [getCustomConfig('monthlyInvoice')];

    let { tags } = this.props.props.merchant.details;

    tags = tags.map(tag => tag.toLowerCase());

    // populate custom configs
    if (tags.indexOf('broking_report') !== -1) {
      configs.push(getCustomConfig('broking'));
    }

    // DSP Report is only for DSP Blackrock Merchant. Should not be enabled for any other merchants
    if (tags.indexOf('dsp_report') !== -1) {
      configs.push(getCustomConfig('dsp_report'));
    }

    if (tags.indexOf('rpp_report') !== -1) {
      configs.push(getCustomConfig('rpp_report'));
    }

    return configs;
  };

  componentWillMount() {
    let fetchURL = this.isOrg()
      ? `live/admin-reporting/configs`
      : `live_${this.props.merchantId}/reporting/configs`;

    this.setState({
      isLoading: true,
    });

    adminFetch({
      url: fetchURL,
      ...this.getHeaders(),
    }).then(configsResp => {
      this.prepareConfigs(configsResp);
    });
  }

  getHeaders = () => {
    return (
      this.isOrg() && {
        headers: {
          'X-Report-Type': 'admin',
          'X-Consumer': getOrgId(),
        },
      }
    );
  };

  prepareConfigs(configsResp) {
    let hasConfigs =
      configsResp && configsResp.items && configsResp.items.length > 0;
    let configs = [];

    if (hasConfigs) {
      configsResp.items.forEach(configItem => {
        const { name, id, type, ...restConfig } = configItem;

        //TODO: use api item instead of creating new item with new prop name
        const config = {
          label: name,
          id: id,
          type: type,
          ...restConfig,
        };

        configs.push(config);
      });
    }

    let finalConfigs = configs.concat(this.state.configs);

    this.setState({
      configs: finalConfigs,
      isLoading: false,
      ...(finalConfigs.length > 0 && { selectedConfig: finalConfigs[0].id }),
    });
  }

  getConfigLabel(configId) {
    let label = null;
    label = this.state.configs.find(item => item.id === configId).label;

    return label;
  }

  prepareGenerateReport = (body, shouldEmail) => {
    let {
      selectedConfig,
      dateType,
      date,
      invoiceDate,
      reportType,
      paymentMethod,
    } = body;
    let { calDate } = this.state;
    const mode = 'live';
    let template_overrides = {};

    const selectedConfigDetails = this.state.configs.find(
      configItem => configItem.id === selectedConfig
    );

    if (reportType !== this.getFileFormat(selectedConfigDetails)) {
      template_overrides.file_meta = {
        extension: reportType,
        delimiter: ',',
      };
    }

    if (paymentMethod) {
      let card_type = null;

      if (paymentMethod.indexOf('card') > -1) {
        card_type = paymentMethod.split('_')[0];
        paymentMethod = paymentMethod.split('_')[1];
      }
      template_overrides.raw_sql = {
        query_params: {
          payment_method: paymentMethod,
          ...(card_type && { card_type }),
        },
      };
    }

    /* Monthly Invoice */

    if (selectedConfig === 'monthlyInvoice') {
      if (!invoiceDate) {
        return notifyError('Please select valid month for invoice');
      }

      invoiceDate = invoiceDate.split('/');

      const year = invoiceDate[1],
        month = invoiceDate[0],
        invoiceUrl = `/admin/${mode}/reports/invoice/${
          this.props.merchantId
        }?year=${year}&month=${month}`;

      return Promise.resolve(window.open(invoiceUrl, '_blank'));
    }

    if (!calDate) {
      if (dateType === 'monthly') {
        calDate = defaultMonth;
      } else if (dateType === 'daily') {
        calDate = defaultDate;
      }
    }

    /* Polling - Non-custom Reports */

    if (selectedConfigDetails.type !== 'custom') {
      const timeFactor = dateType === 'daily' ? 'day' : 'month';
      const startTime = calDate
        .clone()
        .startOf(timeFactor)
        .unix();
      const endTime = calDate
        .clone()
        .endOf(timeFactor)
        .unix();

      notifySuccess(
        shouldEmail
          ? `Your report will be emailed to ${window.org.email} shortly`
          : 'Your report will download shortly'
      );

      const reqData = {
        config_id: selectedConfig,
        generated_by: this.props.merchantId || getOrgId(),
        start_time: startTime,
        end_time: endTime,
        ...(shouldEmail && { emails: [window.org.email] }),
        ...this.getHeaders(), // attach headers for heimdall accounts
        template_overrides,
      };

      return generateReportV2(reqData).then(data => {
        if (data.error) {
          return notifyError(data.error);
        }

        window.location = data.url;
      });
    }

    /* Hardcoded - custom reports */

    if (!date) {
      return notifyError('Invalid Dates selected');
    }

    const day = calDate.date(),
      month = calDate.month() + 1, // Jan is 0 in moment library
      year = calDate.year();

    // Displayed Format as MM/YYYY
    let data = {
      month,
      year,
    };

    // Displayed Format as DD/MM/YYYY
    if (dateType === 'daily') {
      data.day = day;
    }

    let ajaxUrl = `/admin/${mode}/reports/${selectedConfig}?merchant_id=${
      this.props.merchantId
    }`;

    const ajaxParams = {
      url: ajaxUrl,
      params: data,
    };

    if (selectedConfig === 'broking') {
      ajaxParams.headers = {
        Accept:
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      };
    }

    return fetch(ajaxParams)
      .then(data => {
        if (data) {
          notifySuccess('Your report will download shortly');

          if (selectedConfig === 'broking') {
            var blob = new Blob([data], {
              type:
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            return saveAs(blob, 'broking_report.xlsx');
          }

          setTimeout(() => window.open(data.url, '_blank'), 1000);
        }
      })
      .catch(e => {
        notifyError('No data found for given time range');
      });
  };

  emailReport = body => {
    return this.prepareGenerateReport(body, true);
  };

  getFileFormat = config => {
    return ((config.template || {}).file_meta || {}).extension || 'csv';
  };

  render() {
    const { selectedConfig, dateType, configs, isLoading } = this.state;
    const selectedConfigDetails =
      configs.find(config => config.id === selectedConfig) || {};

    const configReportType = this.getFileFormat(selectedConfigDetails);

    const loader = <PlaceholderLoader />;

    return (
      <div class="box report-container">
        {this.isOrg() && <header>Download Reports:</header>}
        {!configs.length && !isLoading ? (
          'No Report Configs found'
        ) : (
          <Form class="reports-form">
            {/*Report Type Selection*/}
            <aside class="reports-list-panel">
              <div class="title">SELECT REPORT TYPE</div>
              {isLoading ? (
                <div class="loading-group">
                  {loader}
                  {loader}
                  {loader}
                </div>
              ) : (
                <div>
                  {configs.map((option, index) => (
                    <div class="reports-entity-options" key={index}>
                      <RadioField
                        label={option.label}
                        name="selectedConfig"
                        value={option.id}
                        defaultValue={selectedConfig}
                        onClick={e => {
                          this.setState({ selectedConfig: e.target.value });
                        }}
                        class="report-type hide"
                      />
                    </div>
                  ))}
                </div>
              )}
            </aside>

            {/*Report Generate Panel*/}
            <main class="report-generate-panel">
              {
                <div class="panel-header">
                  {selectedConfig && this.getConfigLabel(selectedConfig)}
                </div>
              }

              <div class="form-element">
                <div class="title">PERIOD</div>
                {selectedConfig === 'monthlyInvoice' || (
                  <SelectField
                    label=""
                    fieldClass="m-r"
                    name="dateType"
                    defaultValue="daily"
                    onChange={e => this.setState({ dateType: e.target.value })}
                  >
                    <option value="daily">Daily</option>
                    <option value="monthly">Monthly</option>
                  </SelectField>
                )}

                {(dateType === 'monthly' ||
                  selectedConfig === 'monthlyInvoice') && (
                  <span>
                    <DateField
                      onChange={calDate => this.setState({ calDate })}
                      format="MM/YYYY"
                      name={
                        selectedConfig === 'monthlyInvoice'
                          ? 'invoiceDate'
                          : 'date'
                      }
                      placeholder="Select Month"
                      defaultValue={defaultMonth}
                      type="month"
                      allowToday={true}
                    />
                  </span>
                )}

                {dateType === 'daily' &&
                  selectedConfig !== 'monthlyInvoice' && (
                    <span>
                      <DateField
                        onChange={calDate => this.setState({ calDate })}
                        format="DD/MM/YYYY"
                        name={
                          selectedConfig === 'monthlyInvoice'
                            ? 'invoiceDate'
                            : 'date'
                        }
                        placeholder="Select Date"
                        defaultValue={defaultDate}
                        allowToday={true}
                      />
                    </span>
                  )}
              </div>
              {selectedConfig !== 'monthlyInvoice' &&
                selectedConfigDetails && (
                  <>
                    <div class="form-element">
                      <SelectField label="Select File Format" name="reportType">
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
                      </SelectField>
                    </div>
                    {selectedConfigDetails.report_type === 'admin' &&
                      AllowedAdminReportsForPaymentFilter.indexOf(
                        selectedConfigDetails.label
                      ) > -1 && (
                        <div class="form-element">
                          <SelectField
                            label="Select Payment Type"
                            name="paymentMethod"
                          >
                            {Object.keys(paymentMethods).map(
                              (method, index) => (
                                <option
                                  value={paymentMethods[method]}
                                  key={index}
                                >
                                  {method}
                                </option>
                              )
                            )}
                          </SelectField>
                        </div>
                      )}
                  </>
                )}
              <div class="form-element">
                {!isLoading && (
                  <>
                    <AsyncButton
                      text="Generate and Download Report"
                      class="btn"
                      pendingClass="small spinner"
                      onSubmit={this.prepareGenerateReport}
                    />
                    {this.isOrg() && (
                      <AsyncButton
                        text="Email Report"
                        class="btn btn-default m-l"
                        pendingClass="small spinner"
                        onSubmit={this.emailReport}
                      />
                    )}
                  </>
                )}

                {selectedConfig === 'transaction' && (
                  <footer style={{ marginTop: '16' }}>
                    Combined reports will include transactions on the given
                    date, as well as payments settled on that given date.
                  </footer>
                )}
              </div>
            </main>
          </Form>
        )}
      </div>
    );
  }
}
