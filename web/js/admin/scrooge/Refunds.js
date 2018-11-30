import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { PageTable } from 'ui/Table';
import Form, { serialize } from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import Field, { FromField, ToField, SelectField, SelectMode } from 'ui/Field';
import MultiSelectField from 'ui/MultiSelectField';
import Collection from 'model/collection';
import { adminPost, adminFetch } from 'common/fetch';
import { formatDate, getFormattedAmount, getSearchParams } from 'common/util';
import { toJS } from 'mobx';
import ReviewNotesDetails from '../merchants/entity/merchantActivationForms/ReviewNotesDetails';

export default class Refunds extends Component {
  constructor(props) {
    super(props);

    this.state = {
      gateways: [],
      methods: [],
    };

    this.collection = new Collection({
      data: {
        url: `${this.props.match.params.mode || 'live'}/scrooge/refunds`,
        data: {},
      },
      extraFields: {
        mode: this.props.match.params.mode || 'live',
      },
      fetchFn: data => adminPost({ ...data }).then(d => d.data || []),
    });

    this.onSubmit = this.onSubmit.bind(this);
    this.resetForm = this.resetForm.bind(this);
  }

  componentWillMount() {
    adminFetch(
      `${this.props.match.params.mode || 'live'}/admin/entities/all`
    ).then(data => {
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
      this.setState({
        gateways: gateways,
      });

      for (let methodValue in methodValues) {
        methods.push({
          name: methodValues[methodValue],
          value: methodValue,
        });
      }
      this.setState({
        methods: methods,
      });
    });
  }

  onSubmit = filters => {
    filters = parseFilters(filters);

    if (filters) {
      this.collection.data.data.query = {};
      this.collection.data.url = `${filters.mode}/scrooge/refunds`;

      if (filters['refund-ids']) {
        this.collection.data.data.query.id = filters['refund-ids']
          .split(',')
          .map(e => e.trim());
      }

      if (filters['payment-ids']) {
        this.collection.data.data.query.payment_id = filters['payment-ids']
          .split(',')
          .map(e => e.trim());
      }

      if (filters['gateways']) {
        this.collection.data.data.query.gateway = filters['gateways']
          .split(',')
          .map(e => e.trim());
      }

      if (filters['methods']) {
        this.collection.data.data.query.method = filters['methods']
          .split(',')
          .map(e => e.trim());
      }

      if (filters['statuses']) {
        this.collection.data.data.query.status = filters['statuses']
          .split(',')
          .map(e => e.trim());
      }

      let attemptsRange = {};
      if (this.collection.data.data.query.hasOwnProperty('attempts')) {
        attemptsRange = this.collection.data.data.query.attempts;
      }

      if (filters['attempts-gte']) {
        attemptsRange.gte = filters['attempts-gte'];
        this.collection.data.data.query.attempts = attemptsRange;
      }

      if (filters['attempts-lte']) {
        attemptsRange.lte = filters['attempts-lte'];
        this.collection.data.data.query.attempts = attemptsRange;
      }

      // Generate refunds date filters
      let refundsDateRange = {};
      if (this.collection.data.data.query.hasOwnProperty('created_at')) {
        refundsDateRange = this.collection.data.data.query.created_at;
      }

      if (filters['refunds-from']) {
        refundsDateRange.gte = filters['refunds-from'];
        this.collection.data.data.query.created_at = refundsDateRange;
      }

      if (filters['refunds-to']) {
        refundsDateRange.lt = filters['refunds-to'];
        this.collection.data.data.query.created_at = refundsDateRange;
      }

      // Generate payments date filters
      let paymentsDateRange = {};
      if (
        this.collection.data.data.query.hasOwnProperty('payment_created_at')
      ) {
        paymentsDateRange = this.collection.data.data.query.payment_created_at;
      }

      if (filters['payments-from']) {
        paymentsDateRange.gte = filters['payments-from'];
        this.collection.data.data.query.payment_created_at = paymentsDateRange;
      }

      if (filters['payments-to']) {
        paymentsDateRange.lt = filters['payments-to'];
        this.collection.data.data.query.payment_created_at = paymentsDateRange;
      }

      if (filters['reconciled'] && filters['reconciled'] !== 'all') {
        if (filters['reconciled'] === 'yes') {
          this.collection.data.data.query.reconciled_at = {
            gt: 0,
          };
        } else if (filters['reconciled'] === 'no') {
          this.collection.data.data.query.reconciled_at = 0;
        }
      }
    }

    return this.collection.fetch();
  };

  resetForm = () => {
    window.location.reload();
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
        <div className="box">
          <header>Refunds</header>
          <Form name="refunds-search" id="refunds-search-form" class="filters">
            <SelectMode
              name="mode"
              onChange={this.onModeChange}
              id="refunds-mode"
              defaultValue={this.collection.extraFields.mode}
            />

            <MultiSelectField
              class="statuses-select"
              label="Status(es)"
              name="statuses"
              options={statuses}
              trackBy="value"
              keys={['name']}
            />

            <Field
              label="Refund Id(s)"
              name="refund-ids"
              placeholder="comma separated"
            />

            <Field
              label="Payment Id(s)"
              name="payment-ids"
              placeholder="comma separated"
            />

            <FromField
              label="Refund Date From"
              format="X"
              allowToday={true}
              name="refunds-from"
            />

            <ToField
              label="Refund Date To"
              format="X"
              allowToday={true}
              name="refunds-to"
            />

            <Field
              label="Attempts >="
              name="attempts-gte"
              component="input"
              min={0}
              max={100}
              type="number"
            />

            <Field
              label="Attempts <="
              name="attempts-lte"
              component="input"
              min={0}
              max={100}
              type="number"
            />

            <MultiSelectField
              class="gateways-select"
              label="Gateway(s)"
              name="gateways"
              options={this.state.gateways}
              trackBy="value"
              keys={['name']}
            />

            <MultiSelectField
              class="methods-select"
              label="Method(s)"
              name="methods"
              options={this.state.methods}
              trackBy="value"
              keys={['name']}
            />

            <FromField
              label="Payment Date From"
              format="X"
              allowToday={true}
              name="payments-from"
            />

            <ToField
              label="Payment Date To"
              format="X"
              allowToday={true}
              name="payments-to"
            />

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
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}

const refundLink = item =>
  item.id && (
    <Link class="link" to={`/scrooge/refund/${item.id}`} target="_blank">
      {item.id}
    </Link>
  );

const paymentLink = item =>
  item.payment_id && (
    <Link
      class="link"
      to={`/entity/payment/live/pay_${item.payment_id}`}
      target="_blank"
    >
      {item.payment_id}
    </Link>
  );

const parseFilters = filters =>
  filters &&
  Object.keys(filters).reduce((prev, next) => {
    let dotSplit = next.split('.');
    if (dotSplit.length > 1) {
      let nestedFilter =
        (filters[dotSplit[0]] && JSON.parse(filters[dotSplit[0]])) || {};
      nestedFilter[dotSplit[1]] = filters[next];
      prev[dotSplit[0]] = JSON.stringify(nestedFilter);
    } else {
      prev[next] = filters[next];
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

const fields = [
  ['Refund ID', refundLink],
  ['Payment ID', paymentLink],
  ['Merchant ID', item => item.merchant_id],
  ['Refund Amount', item => item.amount],
  ['Payment Amount', item => item.payment_amount],
  ['Currency', item => item.currency],
  ['Status', item => item.status],
  ['Attempts', item => item.attempts],
  ['Refund Created At', item => formatDate(item.created_at)],
];
