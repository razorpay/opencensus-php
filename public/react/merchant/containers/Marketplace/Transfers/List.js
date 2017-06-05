import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import TetherComponent from 'react-tether';
import Spinner from 'rzp/ui/Spinner';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';

import Table from 'rzp/ui/Table/Index';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchTransfers as fetchAll } from 'rzp/modules/collection';

import {
  transferId,
  transferSource,
  transferRecipient,
  amount,
  createdAt,
  status,
} from 'rzp/ui/Table/Column';

import rowClass from 'merchant/utils/activeRow';

const transferColumns = [
  transferId,
  transferSource,
  transferRecipient,
  amount,
  createdAt,
  status,
];

@connect(state => state.mpTransfers, { fetchAll })
export default class TransfersListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchAll(params);
  }

  render() {
    let { loading, items, error } = this.props;

    return (
      <div class="content-wrapper">

        {error && <Alert type="error" message={error} />}

        {loading
          ? <Spinner />
          : <Table
              rows={items}
              columns={transferColumns}
              rowClass={rowClass}
            />}
        {!loading &&
          items &&
          !items.length &&
          <h4 class="empty-table-message">No Transfers Found!</h4>}

        {items &&
          <Pager
            count={this.state.count}
            skip={this.state.skip}
            length={items.length}
            onClick={this.paginate}
          />}
      </div>
    );
  }
}
