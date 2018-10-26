import React, { Component } from 'react';
import { PageTable } from 'ui/Table';
import Collection from 'model/collection';
import { adminPost } from 'common/fetch';
import { Link } from 'react-router-dom';

function refundLink(item, when) {
  let count = item.aging[when].count;
  return (
    count && (
      <Link
        class="link"
        to={`/scrooge/refunds?gateway=${item.gateway}&method=${
          item.method
        }&from=${item.aging[when].from}&to=${item.aging[when].to}`}
      >
        {count} Refund
        {count === 1 ? '' : 's'}
      </Link>
    )
  );
}

export default class RefundsList extends Component {
  collection = new Collection({
    data: {
      url: 'live/scrooge/reports',
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
          },
        },
      }).then(d => d.data),
  });

  render() {
    const collection = this.collection;
    return (
      <div class="list-container">
        <div class="box">
          <header>Failed Refunds</header>
        </div>
        <PageTable model={this.collection} fields={fields} />
      </div>
    );
  }
}

const fields = [
  ['Gateway', item => item.gateway],
  ['Method', item => item.method],
  ['Today', item => refundLink(item, 'today')],
  ['Yesterday', item => refundLink(item, 'yesterday')],
  ['Last 7 Days', item => refundLink(item, 'last_7days')],
  ['This Month', item => refundLink(item, 'current_month')],
  ['Last Month', item => refundLink(item, 'last_month')],
];
