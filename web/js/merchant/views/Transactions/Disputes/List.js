import React, { Component } from 'react';
import { connect } from 'react-redux';

import DisputeListFilter from 'merchant/views/Transactions/Disputes/components/DisputeListFilter';
import { daysLeftInExpiry } from 'merchant/views/Transactions/Disputes/components/Details';
import { fetchDisputes as fetchAll } from 'merchant/reducers/collection';
import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';
import { titleCase } from 'common/utils/rzp-utils';
import { getTime } from 'common/ui/item';
import ListContainer from 'merchant/containers/ListContainer';
import ShowWhen from 'merchant/components/ShowWhen';
import {
  disputeId,
  paymentId,
  amount,
  status,
  createdAt as createdAtProperty,
} from 'common/ui/item/pair';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const type = {
  title: 'Type',
  value: (item = {}) => titleCase(item.phase),
};

const respondIn = {
  title: 'Respond In',
  value: (item) => (item.status === 'open' ? daysLeftInExpiry(item.respond_by) : '--'),
};

const resolvedOn = {
  title: 'Resolved On',
  value: getTime('resolved_on'),
};

const createdAt = {
  title: createdAtProperty.title,
  value: getTime('created_at', 'll'),
};

@connect((state) => ({ mode: state.session.mode, ...state.disputes }), {
  fetchAll,
})
export default class Dispute extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <ShowWhen
            additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
          >
            <a class="btn btn-link" href="https://razorpay.com/docs/disputes/" target="_blank">
              Guide to Dispute
            </a>
          </ShowWhen>
        </HeaderAction>
        <DisputeListFilter
          form="DisputeListFilter"
          type="link"
          count={this.state.count}
          onSubmit={(args) => {
            analyticsService.track({
              objectName: 'disputes search',
              actionName: 'clicked',
              screen: 'transactions',
              properties: {
                ...args,
                location: 'disputes',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.search(args);
          }}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Disputes"
          columns={[disputeId, paymentId, amount, type, respondIn, createdAt, status]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />

        <div class="row">
          <div class="col-md-10 col-md-offset-1 col-sm-12 text-center">
            <p>
              A dispute is a situation that arises when your customer or the issuing bank questions
              the validity of payment. It could arise due to reasons such as unauthorised charges,
              failure to deliver promised merchandise, excessive charges and so on.
            </p>
          </div>
        </div>
      </div>
    );
  }
}
