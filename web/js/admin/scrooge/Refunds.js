import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { PageTable } from 'ui/Table';
import Form, { serialize } from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import Field, { FromField, ToField, SelectField, SelectMode } from 'ui/Field';
import MultiSelectField from 'ui/MultiSelectField';
import Collection from 'model/collection';
import { adminPost, adminFetch } from 'common/fetch';
import {
  formatDate,
  getFormattedAmount,
  getSearchParams,
  intersect,
} from 'common/util';

export default class Refunds extends Component {
  constructor(props) {
    super(props);

    this.mode = this.props.match.params.mode || 'live';

    this.fields = [
      ['Refund ID', item => refundLink(item, this.mode)],
      ['Payment ID', item => paymentLink(item, this.mode)],
      ['Merchant ID', item => item.merchant_id],
      ['Status', item => item.status],
      ['Gateway', item => item.gateway],
      ['Method', item => item.method],
      ['Attempts', item => item.attempts],
      ['Refund Amount', item => showAmount(item.currency, item.amount)],
      [
        'Payment Amount',
        item => showAmount(item.currency, item.payment_amount),
      ],
      ['Refund Created At', item => formatDate(item.created_at)],
      ['Payment Created At', item => formatDate(item.payment_created_at)],
    ];

    this.params = this.getQueryParams();
    this.multiSelectInitialValues = {
      status: statuses,
    };
    this.defaultValues = {};

    this.state = {
      isLoading: true,
    };

    adminFetch(`${this.mode}/admin/entities/all`)
      .then(data => {
        if (!data) {
          return;
        }

        let gatewayValues = data.fields.gateway.values;
        let methodValues = data.fields.method.values;
        let gateways = [];
        let methods = [];

        gatewayValues.forEach(gateway => {
          gateways.push({
            name: gateway,
            value: gateway,
          });
        });

        this.multiSelectInitialValues.gateway = gateways;

        for (let methodValue in methodValues) {
          methods.push({
            name: methodValues[methodValue],
            value: methodValue,
          });
        }

        this.multiSelectInitialValues.method = methods;
      })
      .then(d => {
        let params = this.params;

        let filterQueryData = {};

        for (let key in params) {
          if (this.multiSelectInitialValues.hasOwnProperty(key)) {
            let commonValues = intersect(
              this.multiSelectInitialValues[key].map(x => x.value),
              params[key]
            );
            this.defaultValues[key] = this.multiSelectInitialValues[key].filter(
              x => commonValues.indexOf(x.value) > -1
            );
            filterQueryData[key] = this.defaultValues[key].map(x => x.value);
          } else {
            this.defaultValues[key] = params[key];
            filterQueryData[key] = this.defaultValues[key];
          }
        }

        this.collection = new Collection({
          data: {
            url: `${this.mode}/scrooge/refunds`,
            data: {
              query: filterQueryData,
            },
          },
          extraFields: {
            mode: this.mode,
          },
          fetchFn: data =>
            adminPost({ ...data }).then(d => {
              this.setState({
                isLoading: false,
              });
              return d.data || [];
            }),
        });
      });

    this.onSubmit = this.onSubmit.bind(this);
    this.resetForm = this.resetForm.bind(this);
  }

  getQueryParams = () => {
    let rawParams = getSearchParams();

    for (var key in rawParams) {
      rawParams[key] = JSON.parse(rawParams[key]);
    }

    return rawParams;
  };

  onRefundModeChange = e => {
    this.collection.extraFields.mode = this.mode = e.target.value;
  };

  onSubmit = filters => {
    filters = parseFilters(filters);

    if (filters !== null) {
      this.collection.data.url = `${filters.mode}/scrooge/refunds`;
      delete filters['mode'];
      this.collection.data.data.query = filters;
    }

    return this.collection.fetch();
  };

  resetForm = () => {
    window.location = window.location.pathname;
  };

  render() {
    return (
      <div class="list-container entity-container">
        <div class="box refunds-tabs-box">
          <ul className="tabs-nav">
            <li className={'selected'}>
              <Link to={`/scrooge/refunds`}>Refunds</Link>
            </li>
            <li>
              <Link to={`/scrooge/reports`}>Failed Reports</Link>
            </li>
          </ul>
        </div>
        {this.state.isLoading ? (
          <div class="spinner center" />
        ) : (
          <div>
            <div className="box">
              <header>Refunds</header>
              <Form
                name="refunds-search"
                id="refunds-search-form"
                class="filters"
              >
                <SelectMode
                  name="mode"
                  onChange={this.onRefundModeChange}
                  id="refunds-mode"
                  defaultValue={this.mode}
                />

                {formFilters.map((filter, index) => {
                  if (filter.type === 'multi-entity') {
                    return (
                      <Field
                        key={index}
                        class={'multi-value'}
                        label={filter.name}
                        name={filter.formKey}
                        placeholder="comma separated"
                        defaultValue={
                          this.defaultValues[filter.formKey]
                            ? this.defaultValues[filter.formKey].join(',')
                            : ''
                        }
                      />
                    );
                  } else if (filter.type === 'multi-select') {
                    return (
                      <MultiSelectField
                        key={index}
                        class={filter.formKey + '-select multi-value'}
                        label={filter.name}
                        name={filter.formKey}
                        options={this.multiSelectInitialValues[filter.formKey]}
                        defaultValue={this.defaultValues[filter.formKey]}
                        trackBy="value"
                        keys={['name']}
                      />
                    );
                  } else if (filter.type === 'numeric-range') {
                    return (
                      <div key={index}>
                        <Field
                          label={filter.name + ' >='}
                          name={filter.formKey + '.gte'}
                          component="input"
                          min={0}
                          max={100}
                          type="number"
                          defaultValue={
                            this.defaultValues[filter.formKey] &&
                            this.defaultValues[filter.formKey].gte
                              ? this.defaultValues[filter.formKey].gte
                              : ''
                          }
                        />

                        <Field
                          label={filter.name + ' <='}
                          name={filter.formKey + '.lte'}
                          component="input"
                          min={0}
                          max={100}
                          type="number"
                          defaultValue={
                            this.defaultValues[filter.formKey] &&
                            this.defaultValues[filter.formKey].lte
                              ? this.defaultValues[filter.formKey].lte
                              : ''
                          }
                        />
                      </div>
                    );
                  } else if (filter.type === 'date-range') {
                    return (
                      <div key={index}>
                        <FromField
                          label={filter.name + ' From'}
                          format="X"
                          allowToday={true}
                          name={filter.formKey + '.gte'}
                          defaultValue={
                            this.defaultValues[filter.formKey] &&
                            this.defaultValues[filter.formKey].gte
                              ? moment
                                  .unix(this.defaultValues[filter.formKey].gte)
                                  .utc()
                              : null
                          }
                        />

                        <ToField
                          label={filter.name + ' To'}
                          format="X"
                          allowToday={true}
                          name={filter.formKey + '.lt'}
                          defaultValue={
                            this.defaultValues[filter.formKey] &&
                            this.defaultValues[filter.formKey].lt
                              ? moment
                                  .unix(this.defaultValues[filter.formKey].lt)
                                  .utc()
                              : null
                          }
                        />
                      </div>
                    );
                  }
                })}

                {/*
                // Take this live when recon API is live
                <SelectField
                  label="Reconciled"
                  name="reconciled"
                  defaultValue={"all"}
                >
                  <option value="all">All</option>
                  <option value="yes">Yes</option>
                  <option value="no">No</option>
                </SelectField>

                */}

                <div class="action-btns">
                  <AsyncButton
                    text="Go"
                    class="btn btn-go"
                    pendingClass="small spinner"
                    onSubmit={this.onSubmit}
                  />
                  <AsyncButton
                    class="btn btn-default"
                    onClick={this.resetForm}
                    text="Clear"
                  />
                  {/*<div
                    class="link"
                    onClick={this.downloadEntityCsv}
                  >
                    Download
                  </div>*/}
                </div>
              </Form>
            </div>
            <PageTable model={this.collection} fields={this.fields} />
          </div>
        )}
      </div>
    );
  }
}

const refundLink = (item, mode) =>
  item.id && (
    <Link
      class="link"
      to={`/scrooge/refund/${mode}/${item.id}`}
      target="_blank"
    >
      {item.id}
    </Link>
  );

const paymentLink = (item, mode) =>
  item.payment_id && (
    <Link
      class="link"
      to={`/entity/payment/${mode}/pay_${item.payment_id}`}
      target="_blank"
    >
      {item.payment_id}
    </Link>
  );

const showAmount = (currency, amount) => currency + ' ' + amount;

const parseFilters = filters =>
  filters &&
  Object.keys(filters).reduce((prev, next) => {
    let multiValue = false;
    let dotSplit = next.split('.');

    let filter = formFilters.find(f => f.formKey === next);

    if (
      filter &&
      (filter.type === 'multi-entity' || filter.type === 'multi-select')
    ) {
      multiValue = true;
    }

    if (dotSplit.length > 1) {
      let nestedFilter =
        (filters[dotSplit[0]] && JSON.parse(filters[dotSplit[0]])) || {};

      nestedFilter[dotSplit[1]] = filters[next];

      prev[dotSplit[0]] = Object.assign(prev[dotSplit[0]] || {}, nestedFilter);
    } else {
      prev[next] = filters[next];
      if (multiValue === true) {
        prev[next] = prev[next].split(',').map(e => e.trim());
      }
    }

    return prev;
  }, {});

const statuses = [
  { name: 'Init', value: 'init' },
  { name: 'File Init', value: 'file_init' },
  { name: 'Attempt Failed', value: 'attempt_failed' },
  { name: 'Processed', value: 'processed' },
  { name: 'On Hold', value: 'on_hold' },
];

// multi-entity, multi-select, select, entity, numeric-range, date-range
const formFilters = [
  {
    name: 'Refund ID(s)',
    formKey: 'id',
    type: 'multi-entity',
  },
  {
    name: 'Merchant ID(s)',
    formKey: 'merchant_id',
    type: 'multi-entity',
  },
  {
    name: 'Payment ID(s)',
    formKey: 'payment_id',
    type: 'multi-entity',
  },
  {
    name: 'Gateway(s)',
    formKey: 'gateway',
    type: 'multi-select',
  },
  {
    name: 'Method(s)',
    formKey: 'method',
    type: 'multi-select',
  },
  {
    name: 'Status(es)',
    formKey: 'status',
    type: 'multi-select',
  },
  {
    name: 'Attempts',
    formKey: 'attempts',
    type: 'numeric-range',
  },
  {
    name: 'Refund Date',
    formKey: 'created_at',
    type: 'date-range',
  },
  {
    name: 'Payment Date',
    formKey: 'payment_created_at',
    type: 'date-range',
  },
];
