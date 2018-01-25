import React, { Component } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';

import DisputeListFilter from 'merchant/components/Disputes/DisputeListFilter';
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
  createdAt,
} from 'rzp/ui/item/pair';

const type = {
  title: 'Type',
  value: (item = {}) => titleCase(item.phase),
};

const respondIn = {
  title: 'Respond In',
  value: item => {
    const currentDateStamp = new Date().getTime() / 1000;
    if (item.expires_on > currentDateStamp) {
      // calculating days from milliseconds
      const noOfDaysRemaining =
        (item.expires_on - currentDateStamp) / 60 / 60 / 60;
      return noOfDaysRemaining > 0
        ? `${Math.ceil(noOfDaysRemaining)} Day(s)`
        : 'Today';
    }
    return '--';
  },
};

const resolvedOn = {
  title: 'Resolved On',
  value: getTime('resolved_on'),
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
            resolvedOn,
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
