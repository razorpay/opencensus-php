import React, { Component } from 'react';
import Table, { PageTable } from 'ui/Table';
import Collection from 'model/collection';
import { adminPost } from 'common/fetch';
import { Link } from 'react-router-dom';
import { openModal } from 'common/modal';
import { ModalContent } from 'component/Modal';
import { snakeToTitleCase } from 'common/util';
import { pluralize } from 'rzp/utils/rzp-utils';
import NavBar from 'admin/scrooge/NavBar';

function refundLink(item, when) {
  let count = 0;
  let query = {};

  if (typeof item.aging[when] !== 'undefined') {
    count = item.aging[when].count;

    query = {
      refunds: {
        status: ['file_init'],
        attempts: {
          gte: 1,
        },
        gateway: [item.gateway],
        method: [item.method],
        created_at: {
          gte: item.aging[when].from,
          lt: item.aging[when].to,
        },
      },
    };
  }

  let queryString = Object.keys(query)
    .map(key => key + '=' + JSON.stringify(query[key]))
    .join('&');

  return (
    count && (
      <Link class="link" to={`/scrooge/refunds?${queryString}`}>
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
  if (!item.hasOwnProperty(key)) {
    return <div>-</div>;
  }

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
  constructor(props) {
    super(props);

    this.mode = 'live';
    if (props.hasOwnProperty('match')) {
      this.mode = props.match.params.mode || 'live';
    }

    this.state = {
      collection: this.getCollectionData(false),
      loading: true,
      cacheAge: 0,
    };

    this.refreshReports = this.refreshReports.bind(this);
    this.getCollectionData = this.getCollectionData.bind(this);
  }

  refreshReports() {
    this.setState({
      loading: true,
      collection: this.getCollectionData(true),
    });
  }

  getCollectionData(refresh) {
    return new Collection({
      data: {
        url: `${this.mode}/scrooge/reports`,
      },
      fetchFn: data =>
        adminPost({
          ...data,
          data: {
            query: {
              refunds: {
                attempts: {
                  gt: 0,
                },
                status: ['file_init'],
              },
            },
            refresh_cache: refresh,
          },
        }).then(d => {
          let lastUpdatedAt = d.last_updated_at || 0;

          if (lastUpdatedAt > 0) {
            this.setState({
              loading: false,
              cacheAge: lastUpdatedAt - moment.utc().unix(),
            });
          }
          return d.data;
        }),
    });
  }

  render() {
    const { collection, cacheAge, loading } = this.state;

    return (
      <div class="list-container refund-reports">
        <NavBar active="reports" />
        <div class="box">
          <header class="clearfix">
            Failed Refunds
            {!loading ? (
              <div className="pull-right text-center">
                <button class="btn btn-default" onClick={this.refreshReports}>
                  Refresh
                </button>
                <br />
                <span class="cache_age">
                  Updated {moment.duration(cacheAge, 'seconds').humanize(true)}
                </span>
              </div>
            ) : (
              ''
            )}
          </header>
        </div>
        <PageTable model={collection} fields={fields} />
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
