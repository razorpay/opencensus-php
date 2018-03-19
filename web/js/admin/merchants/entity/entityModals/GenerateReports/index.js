import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';
import { saveAs } from 'file-saver';

import { notifyError, notifySuccess } from 'common/modal';

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

    const details = this.props.props.merchant.details;
    this.setState({
      isLoading: true,
    });

    this.prepareMid();

    adminFetch({
      url: `live_${details.id}/reporting/configs`,
    }).then(({ configResp }) => {
      if (configResp) {
        // this.prepareMid();
      }
    });
  }

  prepareMid() {
    var configResp = {
      success: true,
      data: {
        entity: 'collection',
        count: 7,
        items: [
          {
            id: 'config_xLTz2xSyPrbhyJ',
            merchant_id: '100000Razorpay',
            type: 'settlements',
            scheduled: false,
            name: 'Settlements',
            description: null,
            template: {
              formats: { date: 'd/m/Y H:i:s' },
              entities: {
                settlements: {
                  id: 'id',
                  tax: 'tax',
                  utr: 'utr',
                  fees: 'fees',
                  amount: 'amount',
                  status: 'status',
                  created_at: 'created_at',
                },
              },
              output_fields: [
                'id',
                'amount',
                'status',
                'fees',
                'tax',
                'utr',
                'created_at',
              ],
            },
            emails: null,
            created_by: '100000Razorpay',
            status: null,
            created_at: 1513699685,
            updated_at: 1513699685,
          },
          {
            id: 'config_ufkWO5A0Q98NRG',
            merchant_id: '100000Razorpay',
            type: 'payments',
            scheduled: false,
            name: 'Payments',
            description: null,
            template: {
              formats: { date: 'd/m/Y H:i:s' },
              entities: {
                cards: { type: 'card_type', network: 'card_network' },
                payments: {
                  id: 'id',
                  fee: 'fee',
                  tax: 'tax',
                  vpa: 'vpa',
                  bank: 'bank',
                  card: 'card',
                  email: 'email',
                  notes: 'notes',
                  amount: 'amount',
                  method: 'method',
                  status: 'status',
                  wallet: 'wallet',
                  card_id: 'card_id',
                  contact: 'contact',
                  captured: 'captured',
                  currency: 'currency',
                  order_id: 'order_id',
                  created_at: 'created_at',
                  error_code: 'error_code',
                  invoice_id: 'invoice_id',
                  description: 'description',
                  international: 'international',
                  refund_status: 'refund_status',
                  amount_refunded: 'amount_refunded',
                  error_description: 'error_description',
                  amount_transferred: 'amount_transferred',
                },
              },
              output_fields: [
                'id',
                'amount',
                'currency',
                'status',
                'order_id',
                'invoice_id',
                'international',
                'method',
                'amount_refunded',
                'amount_transferred',
                'refund_status',
                'captured',
                'description',
                'card_id',
                'card',
                'bank',
                'wallet',
                'vpa',
                'email',
                'contact',
                'notes',
                'fee',
                'tax',
                'error_code',
                'error_description',
                'created_at',
                'card_type',
                'card_network',
              ],
            },
            emails: null,
            created_by: '100000Razorpay',
            status: null,
            created_at: 1513699685,
            updated_at: 1513699685,
          },
          {
            id: 'config_rOd5Z5GjrDKLVq',
            merchant_id: '100000Razorpay',
            type: 'transfers',
            scheduled: false,
            name: 'Transfers',
            description: null,
            template: {
              formats: { date: 'd/m/Y H:i:s' },
              entities: {
                transfers: {
                  id: 'id',
                  tax: 'tax',
                  fees: 'fees',
                  notes: 'notes',
                  to_id: 'recipient',
                  amount: 'amount',
                  on_hold: 'on_hold',
                  currency: 'currency',
                  source_id: 'source',
                  created_at: 'created_at',
                  on_hold_until: 'on_hold_until',
                  amount_reversed: 'amount_reversed',
                  recipient_details: 'recipient_details',
                },
                settlements: {
                  id: 'recipient_settlement_id',
                  utr: 'settlement_utr',
                  status: 'settlement_status',
                  created_at: 'settlement_initiated_on',
                },
              },
              output_fields: [
                'id',
                'source',
                'recipient',
                'recipient_details',
                'amount',
                'currency',
                'amount_reversed',
                'notes',
                'fees',
                'on_hold',
                'on_hold_until',
                'created_at',
                'recipient_settlement_id',
                'settlement_initiated_on',
                'settlement_utr',
                'settlement_status',
                'tax',
              ],
            },
            emails: null,
            created_by: '100000Razorpay',
            status: null,
            created_at: 1513699685,
            updated_at: 1513699685,
          },
          {
            id: 'config_gXWRrRsKjK7WNa',
            merchant_id: '100000Razorpay',
            type: 'transactions',
            scheduled: false,
            name: 'Combined Report',
            description:
              'Combined reports will include transactions on the given date, as well as payments settled on that given date.',
            template: {
              formats: { date: 'd/m/Y H:i:s' },
              entities: {
                cards: {
                  type: 'card_type',
                  issuer: 'card_issuer',
                  network: 'card_network',
                },
                orders: { id: 'order_id', receipt: 'order_receipt' },
                refunds: { payment_id: 'payment_id' },
                disputes: { id: 'dispute_id' },
                payments: {
                  id: 'payment_id',
                  method: 'method',
                  description: 'description',
                },
                settlements: { utr: 'settlement_utr' },
                transactions: {
                  fee: 'fee',
                  tax: 'tax',
                  type: 'type',
                  debit: 'debit',
                  notes: 'notes',
                  amount: 'amount',
                  credit: 'credit',
                  on_hold: 'on_hold',
                  settled: 'settled',
                  currency: 'currency',
                  entity_id: 'entity_id',
                  created_at: 'created_at',
                  settled_at: 'settled_at',
                  settlement_id: 'settlement_id',
                },
              },
              output_fields: [
                'entity_id',
                'type',
                'debit',
                'credit',
                'amount',
                'currency',
                'fee',
                'tax',
                'on_hold',
                'settled',
                'created_at',
                'settled_at',
                'settlement_id',
                'description',
                'notes',
                'payment_id',
                'settlement_utr',
                'order_id',
                'order_receipt',
                'method',
                'card_network',
                'card_issuer',
                'card_type',
                'dispute_id',
              ],
            },
            emails: null,
            created_by: '100000Razorpay',
            status: null,
            created_at: 1513699685,
            updated_at: 1513699685,
          },
          {
            id: 'config_enAjdVaQx4oMYW',
            merchant_id: '100000Razorpay',
            type: 'orders',
            scheduled: false,
            name: 'Orders',
            description: null,
            template: {
              formats: { date: 'd/m/Y H:i:s' },
              entities: {
                orders: {
                  id: 'id',
                  notes: 'notes',
                  amount: 'amount',
                  status: 'status',
                  receipt: 'receipt',
                  attempts: 'attempts',
                  currency: 'currency',
                  offer_id: 'offer_id',
                  amount_due: 'amount_due',
                  created_at: 'created_at',
                  amount_paid: 'amount_paid',
                },
              },
              output_fields: [
                'id',
                'amount',
                'amount_paid',
                'amount_due',
                'currency',
                'receipt',
                'offer_id',
                'status',
                'attempts',
                'notes',
                'created_at',
              ],
            },
            emails: null,
            created_by: '100000Razorpay',
            status: null,
            created_at: 1513699685,
            updated_at: 1513699685,
          },
          {
            id: 'config_eD4naqB9RDjDCd',
            merchant_id: '100000Razorpay',
            type: 'refunds',
            scheduled: false,
            name: 'Refunds',
            description: null,
            template: {
              filters: {
                payments: {
                  refund_status: { op: 'IN', values: ['partial', 'full'] },
                },
              },
              formats: { date: 'd/m/Y H:i:s' },
              entities: {
                refunds: {
                  id: 'id',
                  notes: 'notes',
                  amount: 'amount',
                  receipt: 'receipt',
                  currency: 'currency',
                  created_at: 'created_at',
                  payment_id: 'payment_id',
                },
                payments: { email: 'email', contact: 'contact' },
              },
              output_fields: [
                'id',
                'amount',
                'currency',
                'payment_id',
                'notes',
                'receipt',
                'created_at',
                'contact',
                'email',
              ],
            },
            emails: null,
            created_by: '100000Razorpay',
            status: null,
            created_at: 1513699685,
            updated_at: 1513699685,
          },
          {
            id: 'config_PQsF1BR1Y03E0i',
            merchant_id: '100000Razorpay',
            type: 'reversals',
            scheduled: false,
            name: 'Reversals',
            description: null,
            template: {
              formats: { date: 'd/m/Y H:i:s' },
              entities: {
                reversals: {
                  id: 'id',
                  notes: 'notes',
                  amount: 'amount',
                  currency: 'currency',
                  created_at: 'created_at',
                  transfer_id: 'transfer_id',
                },
              },
              output_fields: [
                'id',
                'transfer_id',
                'amount',
                'currency',
                'notes',
                'created_at',
              ],
            },
            emails: null,
            created_by: '100000Razorpay',
            status: null,
            created_at: 1513699685,
            updated_at: 1513699685,
          },
        ],
      },
    };

    let hasConfigs =
      !!configResp.data.items && configResp.data.items.length > 0;
    let configs = [];
    let selectedConfig;

    if (hasConfigs) {
      configResp.data.items.forEach(configItem => {
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

      let finalConfigs = configs.concat(this.state.configs);
      this.setState({
        configs: finalConfigs,
        isLoading: false,
        selectedConfig: selectedConfig || finalConfigs[0].type,
      });
    }
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

  validateInvoiceMonthYear = current => {
    const isGSTDisabled =
      this.props.props.merchant.details.tags.indexOf('Gst_Invoice_Disabled') !==
      -1;

    const selectedDate = current.split('/');

    const selectedMonth = selectedDate[0],
      selectedYear = selectedDate[1];

    const currDate = new Date();

    if (
      selectedYear === currDate.getFullYear() &&
      selectedMonth > currDate.getMonth() - 1
    ) {
      return false;
    }

    // disable invoice download for july(6)  and august(7)
    // for the year of 2017
    const isValidMonth =
      isGSTDisabled && selectedYear === 2017
        ? selectedMonth !== 6 && selectedMonth !== 7
        : true;

    return validYear(current) && isValidMonth;
  };

  render() {
    const { selectedConfig, dateType, configs } = this.state;

    const details = this.props.props.merchant.details;

    return (
      <BaseModal header="Download Reports" customClass="reports-modal">
        <Form>
          {/*Report Type Selection*/}
          <aside class="reports-list-panel">
            <div class="title">SELECT REPORT TYPE</div>
            {
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
            }
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
