import React, { Component } from 'react';
import Table, { PageTable } from 'ui/Table';
import Collection from 'model/collection';
import { adminPost } from 'common/fetch';
import { Link } from 'react-router-dom';
import { openModal } from 'common/modal';
import { ModalContent } from 'component/Modal';
import { snakeToTitleCase } from 'common/util';
import { pluralize } from 'rzp/utils/rzp-utils';

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
        {count} {pluralize('Refund', count)}
      </Link>
    )
  );
}

const errorFields = [
  ['Name', item => item.error_name],
  ['Count', item => item.count],
];

function RefundErrorsModal(data) {
  let sortedErrors = data.errors.sort((a, b) => b.count - a.count);

  return (
    <ModalContent
      class="refund-errors-modal"
      header={snakeToTitleCase(data.title)}
    >
      <Table items={sortedErrors} fields={errorFields} />
    </ModalContent>
  );
}

function refundErrors(item, key) {
  let count = item[key].length;
  let viewAllClick = () => {
    openModal(<RefundErrorsModal errors={item[key]} title={key} />);
  };

  return (
    <div>
      {count > 0 ? (
        <a class="link" onClick={viewAllClick}>
          {count} {pluralize('Error', count)}
        </a>
      ) : (
        '-'
      )}
    </div>
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
      <div class="list-container refund-reports">
        <div className="box refunds-tabs-box">
          <ul className="tabs-nav">
            <li>
              <Link to={`/scrooge/refunds`}>Refunds</Link>
            </li>
            <li className={'selected'}>
              <Link to={`/scrooge/reports`}>Failed Reports</Link>
            </li>
          </ul>
        </div>
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
  ['Total', item => refundLink(item, 'total')],
  ['Today', item => refundLink(item, 'today')],
  ['Yesterday', item => refundLink(item, 'yesterday')],
  ['Last 7 Days', item => refundLink(item, 'last_7days')],
  ['This Month', item => refundLink(item, 'current_month')],
  ['Last Month', item => refundLink(item, 'last_month')],
  ['Internal Errors', item => refundErrors(item, 'internal_errors')],
  ['Gateway Errors', item => refundErrors(item, 'gateway_errors')],
];
