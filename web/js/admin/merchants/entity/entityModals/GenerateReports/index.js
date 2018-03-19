import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';
import { saveAs } from 'file-saver';

import { notifyError, notifySuccess } from 'common/modal';

import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import Form from 'ui/Form';
import { Field, RadioField, SelectField, DateField } from 'ui/Field';
import fetch, { adminFetch } from 'common/fetch';
import { prefixEntityValue } from 'common/data';
import AsyncButton from 'ui/AsyncButton';
import { PowerSelect, TypeAhead } from 'react-power-select';
import { getCustomConfig, generateReportV2 } from './helper';

const defaultDate = moment().subtract(1, 'day');
const defaultMonth = moment().add(-1, 'month');
const defaultSelectedConfig = 'payments';

export default class GenerateReports extends Component {
  constructor(props) {
    super(props);

    const { details } = props.props.merchant;

    let configs = [];
    // populate custom configs
    if (details.tags.indexOf('Broking_Report') !== -1) {
      configs.push(getCustomConfig('broking'));
    }

    // DSP Report is only for DSP Blackrock Merchant. Should not be enabled for any other merchants
    if (details.tags.indexOf('Dsp_Report') !== -1) {
      configs.push(getCustomConfig('dsp_report'));
    }

    if (details.tags.indexOf('Rpp_Report') !== -1) {
      configs.push(getCustomConfig('rpp_report'));
    }

    this.state = {
      configs,
      merchantAccounts: [],
      dateType: 'daily',
      configs: [getCustomConfig('monthlyInvoice')],
    };
  }

  componentWillMount() {
    this.linkedAccountOptions = [
      'transaction',
      'payment',
      'refund',
      'settlement',
    ];

    this.setState({
      isLoading: true,
    });

    adminFetch({
      url: `live_${this.props.merchantId}/reporting/configs`,
    }).then(configsResp => {
      this.prepareConfigs(configsResp);
    });
  }

  prepareConfigs(configsResp) {
    let hasConfigs =
      configsResp && configsResp.items && configsResp.items.length > 0;
    let configs = [];
    let selectedConfig;

    if (hasConfigs) {
      configsResp.items.forEach(configItem => {
        const config = {
          label: configItem.name,
          id: configItem.id,
          type: configItem.type,
        };

        configs.push(config);

        if (config.type === defaultSelectedConfig) {
          selectedConfig = config.type;
        }
      });
    }

    let finalConfigs = configs.concat(this.state.configs);
    this.setState({
      configs: finalConfigs,
      isLoading: false,
      selectedConfig: selectedConfig || finalConfigs[0].type,
    });
  }

  getConfigLabel(configType) {
    let label = null;
    label = this.state.configs.find(item => item.type === configType).label;

    return label;
  }

  prepareGenerateReport = body => {
    console.log('BODY...', body);
    let { selectedConfig, dateType, date, invoiceDate } = body;
    let { calDate } = this.state;
    const mode = 'live';

    /* Monthly Invoice */

    if (selectedConfig === 'invoice') {
      if (!invoiceDate) {
        return notifyError('Please select valid month for invoice');
      }

      invoiceDate = invoiceDate.split('/');

      const year = invoiceDate[1],
        month = invoiceDate[0],
        invoiceUrl = `/admin/${mode}/reports/invoice?year=${year}&month=${month}&merchant_id=${
          this.props.merchantId
        }`;

      return Promise.resolve(window.open(invoiceUrl, '_blank'));
    }

    const selectedConfigId = this.state.configs.find(
      configItem => configItem.type === selectedConfig
    ).id;

    if (!calDate) {
      if (dateType === 'monthly') {
        calDate = defaultMonth;
      } else if (dateType === 'daily') {
        calDate = defaultDate;
      }
    }

    /* Polling - Custom Reports */

    if (selectedConfigId === 'custom') {
      const startTime = calDate
        .clone()
        .startOf(timeFactor)
        .unix();
      const endTime = calDate
        .clone()
        .endOf(timeFactor)
        .unix();

      notifySuccess('Your report will download shortly');

      const reqData = {
        config_id: selectedConfigId,
        generated_by: this.props.merchantId,
        start_time: startTime,
        end_time: endTime,
      };

      return generateReportV2(reqData).then(data => {
        if (data.error) {
          return notifyError(data.error);
        }

        window.location = data.url;
      });
    }

    /* Hardcoded, Other Custom Reports */

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

  render() {
    const { selectedConfig, dateType, configs, isLoading } = this.state;

    const details = this.props.props.merchant.details;
    const loader = (
      <PlaceholderLoader
        style={{ height: '12px', width: '100%', margin: '12px 0' }}
      />
    );

    return (
      <BaseModal header="Download Reports" customClass="reports-modal">
        <Form>
          {/*Report Type Selection*/}
          <aside class="reports-list-panel">
            <div class="title">SELECT REPORT TYPE</div>
            {isLoading ? (
              <div style={{ margin: '0 24px' }}>
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
                      value={option.type}
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
                    onChange={calDate => this.setState(calDate)}
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
                      onChange={calDate => this.setState(calDate)}
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

            <div class="form-element">
              <AsyncButton
                text="Generate and Download Report"
                class="btn"
                pendingClass="small spinner"
                onSubmit={this.prepareGenerateReport}
              />

              {selectedConfig === 'transaction' && (
                <footer style={{ marginTop: '16' }}>
                  Combined reports will include transactions on the given date,
                  as well as payments settled on that given date.
                </footer>
              )}
            </div>
          </main>
        </Form>
      </BaseModal>
    );
  }
}
