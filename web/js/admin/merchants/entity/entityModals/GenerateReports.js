import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import { Field, RadioField, SelectField, DateField } from 'ui/Field';
import fetch, { adminFetch } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import { PowerSelect, TypeAhead } from 'react-power-select';

// import ReduxDatetime from 'rzp/ui/ReduxDatetime';

// TODO: Restrict year selection from html dates also
function validYear(current) {
  const selectedDate = current.split('-'),
    selectedYear = selectedDate[0],
    selectedMonth = selectedDate[1],
    selectedDay = selectedDate[2];

  const today = new Date();

  // Year check
  let isBeforeToday = selectedYear <= today.getFullYear();

  // Month check
  if (isBeforeToday && selectedYear === today.getFullYear()) {
    isBeforeToday = selectedMonth <= today.getMonth() + 1;

    // Date check
    if (isBeforeToday && selectedMonth === today.getMonth() + 1) {
      isBeforeToday = selectedDay < today.getDate();
    }
  }

  return selectedYear >= 2015 && isBeforeToday;
}

export default class GenerateReports extends Component {
  state = {
    merchantAccounts: [],
    type: 'daily',
    entity: 'payment',
  };

  componentWillMount() {
    this.linkedAccountOptions = [
      'transaction',
      'payment',
      'refund',
      'settlement',
    ];

    const details = this.props.props.merchant.details;
    const isMarketplaceEnabled = details.tags.indexOf('Marketplace') !== -1;

    if (isMarketplaceEnabled && false) {
      // Feature to be used only when Merchant Dash
      adminFetch({}, '/live/accounts')
        .then(response => {
          if (response) {
            this.setState({
              merchantAccounts: this.state.merchantAccounts.concat(
                response.data.items
              ),
            });
          }
        })
        .catch(err => {
          notifyError(JSON.stringify(err.response));
        });
    }

    this.prepareEntityOptions();

    // Select default merchantAccount
    if (isMarketplaceEnabled && false) {
      // Feature to be used only when Merchant Dash
      let merchantAccounts = this.state.merchantAccounts.concat([]);
      merchantAccounts.push({
        name: details.name,
        id: details.current,
        email: details.email,
        tag: 'My Account',
      });
      this.setState({
        merchantAccounts: merchantAccounts,
        merchantSelected: merchantAccounts[0],
      });
    }
  }

  getEntityLabel(value) {
    let label = null;
    label = this.entityOptions.find(item => item.value === value).label;

    return label;
  }

  prepareEntityOptions() {
    const { details } = this.props.props.merchant;

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
        value: 'order',
        id: 'order',
        label: 'Orders',
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

    if (details.tags.indexOf('Broking_Report') !== -1) {
      this.entityOptions.push({
        value: 'broking',
        id: 'broking',
        label: 'Broking Reports', //
      });
    }

    // DSP Report is only for DSP Blackrock Merchant. Should not be enabled for any other merchants
    if (details.tags.indexOf('Dsp_Report') !== -1) {
      this.entityOptions.push({
        value: 'dsp_report',
        id: 'dsp_report',
        label: 'DSP Transaction Report',
      });
    }

    if (details.tags.indexOf('Rpp_Report') !== -1) {
      this.entityOptions.push({
        value: 'rpp_report',
        id: 'rpp_report',
        label: 'e-Mitra Report',
      });
    }

    if (details.tags.indexOf('Payment_Link_Report') !== -1) {
      this.entityOptions.push({
        value: 'payment_link',
        id: 'payment_link',
        label: 'Payment Link',
      });
    }

    if (details.tags.indexOf('Marketplace') !== -1) {
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

  prepareGenerateReport = body => {
    console.log('BODY...', body);
    let { entity, type, date, invoiceDate } = body;

    if (date) {
      date = date.split('-');
    }
    if (invoiceDate) {
      invoiceDate = invoiceDate.split('-');
    }

    const mode = 'live';

    const details = this.props.props.merchant.details,
      isMarketplaceEnabled = details.tags.indexOf('Marketplace') !== -1;

    const account_id =
      isMarketplaceEnabled &&
      false && // Feature available only in Merchant Dash
      this.linkedAccountOptions.indexOf(entity) !== -1
        ? this.state.merchantSelected.id
        : details.current;

    if (entity === 'invoice') {
      /*
      let invoiceUrl__merchant_dash = `/${mode}/reports/invoice?year=${invoiceDate[0]}&month=${
        invoiceDate[1]
      }`;
*/

      let invoiceUrl = `/admin/${mode}/reports/invoice?year=${
        invoiceDate[0]
      }&month=${invoiceDate[1]}&merchant_id=${this.props.merchantId}`;
      return Promise.resolve(window.open(invoiceUrl, '_blank'));
    }

    let data = {
      month: Number(date[1]),
      year: date[0],
    };

    if (type === 'daily') {
      data.day = Number(date[2]);
    }

    // let ajaxUrl__merchant_dash = '/reports/' + entity;
    let ajaxUrl = `/admin/${mode}/reports/${entity}?merchant_id=${
      this.props.merchantId
    }`;

    const ajaxParams = {
      url: ajaxUrl,
      params: data,
    };

    if (isMarketplaceEnabled && account_id !== details.current) {
      data.account_id = 'acc_' + account_id; // It will be handled at api level later
    }

    if (entity === 'broking') {
      ajaxParams.headers = {
        Accept:
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      };
    }

    return fetch(ajaxParams)
      .then(data => {
        if (data) {
          notifySuccess('Your report will download shortly');

          if (entity === 'broking') {
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

    const selectedDate = current.split('-');

    const selectedMonth = selectedDate[1],
      selectedYear = selectedDate[0];

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
    const { entity, type } = this.state;

    const details = this.props.props.merchant.details;
    const isMarketplaceEnabled = details.tags.indexOf('Marketplace') !== -1;

    return (
      <BaseModal header="Download Reports" customClass="reports-modal">
        <Form>
          {/*Report Type Selection*/}
          <aside class="reports-list-panel">
            <div class="title">SELECT REPORT TYPE</div>
            {
              <div>
                {this.entityOptions.map((option, index) => (
                  <div class="reports-entity-options" key={index}>
                    <RadioField
                      label={option.label}
                      name="entity"
                      value={option.value}
                      defaultValue={this.state.entity}
                      onClick={e => {
                        this.setState({ entity: e.target.value });
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
                {entity && this.getEntityLabel(entity)}
              </div>
            }
            {/* Merchant Dash code only. Pass props to this component to identify between Merchant and Admin Dash code
              <div class="form-element">
                <div class="title">
                  {isMarketplaceEnabled &&
                  this.linkedAccountOptions.indexOf(entity) !== -1
                    ? 'SELECT '
                    : ''}
                  ACCOUNT
                </div>

                {isMarketplaceEnabled &&
                this.linkedAccountOptions.indexOf(
                  entity
                ) > -1 ? (
                  <div class="custom-select">
                    <i class="i i-search custom-icon" />
                    <div
                      class="typeAheadSkin"
                      ref={typeAheadSkin => {
                        this.typeAheadSkin = typeAheadSkin;
                      }}
                    >
                      {this.state.merchantSelected ? (
                        <div>
                          <b style={{ marginRight: '5px' }}>
                            {this.state.merchantSelected.name}
                          </b>
                          <span>- {this.state.merchantSelected.id}</span>
                          <span
                            class="custom-tag"
                          >{this.state.merchantSelected.tag}
                        </span>
                        </div>
                      ) : null}
                    </div>

                    <TypeAhead
                      options={this.state.merchantAccounts}
                      placeholder="Search for merchant Name/Email/Merchant ID"
                      optionLabelPath="name"
                      onClick={() => {
                        this.typeAheadSkin.classList.add('hide');
                      }}
                      searchIndices={['name', 'id', 'email']}
                      selected={this.state.merchantSelected}
                      showClear={false}
                      optionComponent={({ option }) => (
                        <div style={{ padding: 5 }}>
                          <b style={{ marginRight: '5px' }}>{option.name}</b>
                          <span>- {option.id}</span>
                          <span
                            class="custom-tag"
                          >
                          {option.tag}
                        </span>
                        </div>
                      )}
                      selectedOptionComponent={({ option }) => (
                        <div>
                          <b style={{ marginRight: '5px' }}>{option.name}</b>
                          <span>- {option.id}</span>
                        </div>
                      )}
                      onChange={({ option }) => {
                        if (option) {
                          this.setState({ merchantSelected: option });
                          this.typeAheadSkin.classList.remove('hide');
                        }
                      }}
                    />
                  </div>
                ) : (
                  <div class="account">
                    <strong>{details.name || details.user.name}</strong>
                  </div>
                )}

                {isMarketplaceEnabled &&
                this.linkedAccountOptions.indexOf(entity) !==
                -1 && (
                  <small class="help-block">
                    <i class="i i-info-circle" />
                    <span>
                      You can also select a linked account from the list
                    </span>
                  </small>
                )}
              </div>
           */}

            <div class="form-element">
              <div class="title">PERIOD</div>
              {entity === 'invoice' || (
                <SelectField
                  label=""
                  name="type"
                  defaultValue="daily"
                  onChange={e => this.setState({ type: e.target.value })}
                >
                  <option value="daily">Daily</option>
                  <option value="monthly">Monthly</option>
                </SelectField>
              )}

              {(type === 'monthly' || entity === 'invoice') && (
                <span>
                  <input
                    type="month"
                    name={entity === 'invoice' ? 'invoiceDate' : 'date'}
                    onChange={e => {
                      let validator;
                      // Check if date is valid
                      validator =
                        entity === 'invoice'
                          ? this.validateInvoiceMonthYear
                          : validYear;

                      if (!validator(e.target.value)) {
                        notifyError('Cannot select ' + e.target.value);
                      }
                    }}
                  />
                </span>
              )}

              {type === 'daily' &&
                entity !== 'invoice' && (
                  <span>
                    <input
                      type="date"
                      name={entity === 'invoice' ? 'invoiceDate' : 'date'}
                      onChange={e => {
                        // Check if date is valid
                        if (!validYear(e.target.value)) {
                          notifyError('Cannot select ' + e.target.value);
                        }
                      }}
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

              {entity === 'transaction' && (
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
