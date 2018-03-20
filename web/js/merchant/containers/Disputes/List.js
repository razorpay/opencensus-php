import React, { Component } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';

import DisputeListFilter from 'merchant/components/Disputes/DisputeListFilter';
import { daysLeftInExpiry } from 'merchant/utils/disputes';
import { fetchDisputes as fetchAll } from 'rzp/modules/collection';
import DataTable from 'rzp/ui/Table/DataTable';
import Banner from 'rzp/ui/Banner';
import HeaderAction from 'rzp/ui/HeaderAction';
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
    item.status === 'open' ? daysLeftInExpiry(item.respond_by) : '--',
};

const resolvedOn = {
  title: 'Resolved On',
  value: getTime('resolved_on'),
};

const createdAt = {
  title: createdAtProperty.title,
  value: getTime('created_at', 'll'),
};

@connect(state => ({ mode: state.session.mode, ...state.disputes }), {
  fetchAll,
})
export default class Dispute extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <a
            class="btn btn-link"
            href="https://razorpay.com/docs/disputes/"
            target="_blank"
          >
            Guide to Dispute
          </a>
        </HeaderAction>
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

        <div class="row">
          <div class="col-md-10 col-md-offset-1 col-sm-12 text-center">
            <p>
              A dispute is a situation that arises when your customer or the
              issuing bank questions the validity of payment. It could arise due
              to reasons such as unauthorised charges, failure to deliver
              promised merchandise, excessive charges and so on.
            </p>
          </div>
        </div>
      </div>
    );
  }
}
