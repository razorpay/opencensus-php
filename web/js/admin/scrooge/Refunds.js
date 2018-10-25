import React, { Component } from 'react';
import { PageTable } from 'ui/Table';
import Collection from 'model/collection';
import { adminPost } from 'common/fetch';
import { Link } from 'react-router-dom';
import ToggleEntityRow from 'ui/ToggleEntityRow';
import { formatDate, getFormattedAmount, getSearchParams } from 'common/util';

export default class RefundsList extends Component {
  params = getSearchParams();

  collection = new Collection({
    data: {
      url: 'live/scrooge/refunds',
    },
    fetchFn: data =>
      adminPost({
        ...data,
        data: {
          query: {
            attempts: {
              gt: 0,
            },
            status: ['file_init'],
            gateway: this.params.gateway,
            method: this.params.method,
            created_at: {
              gt: this.params.from,
              lt: this.params.to,
            },
          },
        },
      }).then(d => d.data),
  });

  render() {
    const collection = this.collection;
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Failed Refunds
            <Link
              to="/scrooge/reports"
              class="link"
              style={{ float: 'right', fontSize: 14, marginTop: 10 }}
            >
              Return to Reports
            </Link>
          </header>
        </div>
        <PageTable model={this.collection} fields={fields} href={showRefund} />
      </div>
    );
  }
}

const fields = [
  ['Refund ID', item => item.id],
  ['Payment ID', item => item.payment_id],
  ['Merchant ID', item => item.merchant_id],
  ['Refund Amount', item => item.amount],
  ['Payment Amount', item => item.payment_amount],
  ['Currency', item => item.currency],
  ['Status', item => item.status],
  ['Attempts', item => item.attempts],
  ['Refund Created At', item => formatDate(item.created_at)],
];

const showRefund = item => `/scrooge/refund/${item.id}${location.search}`;
