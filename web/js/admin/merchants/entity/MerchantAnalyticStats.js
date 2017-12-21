import React, { Component } from 'react';
import { observer } from 'mobx-react';

import Amount from 'ui/Amount';

import fetch, { adminPost } from 'common/fetch';
import { openModal, confirm } from 'common/modal';
import { notifySuccess, notifyError } from 'common/modal';

import AsyncButton from 'ui/AsyncButton';
import { FromField, ToField } from 'ui/Field';
import Form from 'ui/Form';
import EntityRow from 'ui/EntityRow';
import Table from 'ui/Table';
import Duplex from 'ui/Duplex';

@observer
export default class MerchantAnalyticStats extends Component {
  state = {};
  fromDate = new Date(new Date().setDate(new Date().getDate() - 7));
  toDate = new Date();

  constructor(props) {
    super();
    this.merchantId = props.match.params.id;
  }

  componentWillMount() {
    this.fetchDetails();
  }

  fetchAllAggregations() {
    const query_params = {
      count: 10,
      duration_count: 1,
      page: this.merchantId,
      sort: 'total_amount',
      type: 'month',
    };

    fetch({
      url: '/admin/live/merchants/aggregations',
      params: query_params,
    }).then(data => {
      const aggregations = {
        data: data.data.data,
        stats: {
          count: data.data.data.length,
          countStart: data.data.from,
          countEnd: data.data.to,
        },
        allowPrev: data.data.prev_page_url !== null,
        allowNext: data.data.next_page_url !== null,
      };

      this.setState({
        aggregations,
      });
    });
  }

  fetchAllAggregationsForMerchant() {
    const query_params = {
      sort: 'total_amount',
      duration_count: 1,
      type: 'month',
    };

    fetch({
      url: `/admin/live/merchants/${this.merchantId}/aggregations`,
      params: query_params,
    }).then(data => {
      const aggregations = {
        data: data.data.data,
        stats: {
          count: 1,
          countStart: 1,
          countEnd: 1,
        },
      };

      this.setState({
        aggregations,
      });
    });
  }

  handleSearch = body => {
    if (!(body.from && body.to)) {
      notifyError('Please enter valid dates');

      return;
    }
    this.fromDate = new Date(body.from);
    this.toDate = new Date(body.to);

    this.fetchDetails();
  };

  fetchDetails = () => {
    const from_timestamp = Math.round(this.fromDate.getTime() / 1000);
    const to_timestamp = Math.round(this.toDate.getTime() / 1000);

    if (from_timestamp > to_timestamp) {
      notifyError('From date cannot be after To date');
      return;
    }

    const requestData = {
      filters: {
        default: [
          {
            merchant_id: [this.merchantId],
            created_at: {
              gte: from_timestamp,
              lte: to_timestamp,
            },
          },
        ],
        filter_success_trans: [
          {
            merchant_id: [this.merchantId],
            created_at: {
              gte: from_timestamp,
              lte: to_timestamp,
            },
            status: ['captured', 'authorized'],
          },
        ],
      },
      aggregations: {
        total_payments: {
          agg_type: 'count',
          details: {
            index: 'payment',
            column: 'base_amount',
          },
        },
        total_settlements: {
          agg_type: 'count',
          details: {
            index: 'settlement',
            column: 'base_amount',
          },
        },
        total_refunds: {
          agg_type: 'count',
          details: {
            index: 'refund',
            column: 'base_amount',
          },
        },
        payments_volume: {
          agg_type: 'sum',
          details: {
            index: 'payment',
            column: 'base_amount',
          },
        },
        recent_balance: {
          agg_type: 'recent',
          details: {
            index: 'balance',
            column: 'base_amount',
          },
        },
        recent_payments: {
          agg_type: 'recent',
          details: {
            index: 'payment',
            column: 'base_amount',
            result_fields: ['id', 'status', 'created_at'],
          },
        },
        recent_refunds: {
          agg_type: 'recent',
          details: {
            index: 'refund',
            column: 'base_amount',
            result_fields: ['id', 'status', 'created_at'],
          },
        },
        recent_settlements: {
          agg_type: 'recent',
          details: {
            index: 'settlement',
            column: 'base_amount',
            result_fields: ['id', 'status', 'created_at'],
          },
        },
        recent_transactions: {
          agg_type: 'recent',
          details: {
            index: 'transaction',
            result_fields: ['created_at'],
          },
        },
        payment_method_bars: {
          agg_type: 'percent',
          details: {
            index: 'payments',
            column: 'base_amount',
            group_by: ['method'],
          },
        },
        transaction_histogram: {
          agg_type: 'sum',
          details: {
            index: 'transaction',
            column: 'base_amount',
            group_by: ['histogram_daily'],
          },
        },
        successful_transaction: {
          agg_type: 'count',
          filter_key: 'filter_success_trans',
          details: {
            index: 'transaction',
            column: 'base_amount',
            group_by: ['histogram_weekly'],
          },
        },
      },
    };

    adminPost({
      route_name: 'merchant_analytics',
      merchant_id: this.merchantId,
      body: requestData,
    })
      .then(response => {
        response = dummyResponse;
        if (response) {
          this.setState({ merchant_analytics: response });
          notifySuccess('Adjustment added successfully.');
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  render() {
    const { merchant_analytics } = this.state;

    return (
      <div class="entity-container">
        <header class="heading">
          Merchant: {this.merchantId} (Team Details)
        </header>

        <div class="box">
          <Form>
            <FromField
              label="From"
              format="YYYY-MM-DD"
              placeholder="YYYY-MM-DD"
              value={new Date(new Date().setDate(new Date().getDate() - 7))}
            />
            <ToField
              label="To"
              format="YYYY-MM-DD"
              placeholder="YYYY-MM-DD"
              value={new Date()}
            />

            <AsyncButton
              text="Fetch Stats"
              class="btn"
              pendingClass="small spinner"
              onSubmit={this.handleSearch}
            />
          </Form>
        </div>

        <div class="box">
          <div class="heading">Payment Details</div>
          <EntityRow label={'Property'} value={'Value'} />
          {!merchant_analytics ? (
            <div class="small spinner center" />
          ) : (
            <Duplex
              fields={_getPaymentDetailsFields()}
              model={merchant_analytics}
            />
          )}
        </div>

        <div class="box">
          <div class="heading">Payment Method Bars</div>
          {!merchant_analytics ? (
            <div class="small spinner center" />
          ) : (
            <Table
              items={merchant_analytics.payment_method_bars}
              fields={_getMethodsFields()}
            />
          )}
        </div>

        <div class="box">
          <div class="heading">Recent Payments</div>
          {!merchant_analytics ? (
            <div class="small spinner center" />
          ) : (
            <Table
              items={merchant_analytics.recent_payments}
              fields={_getGenericFields()}
            />
          )}
        </div>

        <div class="box">
          <div class="heading">Recent Refunds</div>
          {!merchant_analytics ? (
            <div class="small spinner center" />
          ) : (
            <Table
              items={merchant_analytics.recent_refunds}
              fields={_getGenericFields()}
            />
          )}
        </div>

        <div class="box">
          <div class="heading">Recent Settlements</div>
          {!merchant_analytics ? (
            <div class="small spinner center" />
          ) : (
            <Table
              items={merchant_analytics.recent_settlements}
              fields={_getGenericFields()}
            />
          )}
        </div>

        <div class="box">
          <div class="heading">Recent Transactions</div>
          {!merchant_analytics ? (
            <div class="small spinner center" />
          ) : (
            <Table
              items={merchant_analytics.recent_transactions}
              fields={_getGenericFields()}
            />
          )}
        </div>
      </div>
    );
  }
}

/* Resources */

function _getMethodsFields() {
  return [
    ['Method', item => item.method],
    ['Percent', item => item.percent],
    ['Doc Count', item => item.doc_count],
  ];
}

function _getGenericFields() {
  return [
    ['Id', item => item.id],
    ['Status', item => item.status],
    ['Created', item => item.created_at],
  ];
}

function _getPaymentDetailsFields() {
  return [
    item => [
      'Payments Volume',
      item.payments_volume[0] && (
        <Amount value={item.payments_volume[0].value} />
      ),
    ],
    item => [
      'Total Payments',
      item.total_payments[0] && <Amount value={item.total_payments[0].value} />,
    ],
    item => [
      'Total Refunds',
      item.total_refunds[0] && <Amount value={item.total_refunds[0].value} />,
    ],
    item => [
      'Total Settlements',
      item.total_settlements[0] && (
        <Amount value={item.total_settlements[0].value} />
      ),
    ],
    item => [
      'Total Balance',
      item.recent_balance[0] && <Amount value={item.recent_balance[0].value} />,
    ],
  ];
}
