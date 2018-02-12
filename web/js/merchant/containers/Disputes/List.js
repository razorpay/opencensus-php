import React, { Component } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';

import DisputeListFilter from 'merchant/components/Disputes/DisputeListFilter';
import { daysLeftInExpiry } from 'merchant/utils/disputes';
import { fetchDisputes as fetchAll } from 'rzp/modules/collection';
import DataTable from 'rzp/ui/Table/DataTable';
import { titleCase } from 'common/util';
import { getTime } from 'rzp/ui/item';
import ListContainer from '../ListContainer';
import {
  disputeId,
  paymentId,
  amount,
  status,
  createdAt as createdAtProperty,
} from 'rzp/ui/item/pair';

const type = {
  title: 'Type',
  value: (item = {}) => titleCase(item.phase),
};

const respondIn = {
  title: 'Respond In',
  value: item =>
    item.status === 'open' ? daysLeftInExpiry(item.expires_on) : '--',
};

const resolvedOn = {
  title: 'Resolved On',
  value: getTime('resolved_on'),
};

const createdAt = {
  title: createdAtProperty.title,
  value: getTime('created_at', 'll'),
};

@connect(state => state.disputes, { fetchAll })
export default class Dispute extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <DisputeListFilter
          form="DisputeListFilter"
          type="link"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Disputes"
          columns={[
            disputeId,
            paymentId,
            amount,
            type,
            respondIn,
            createdAt,
            status,
          ]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
