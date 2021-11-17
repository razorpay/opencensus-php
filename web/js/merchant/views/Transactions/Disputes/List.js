import React from 'react';
import { connect } from 'react-redux';
import DisputeListFilter from 'merchant/views/Transactions/Disputes/components/DisputeListFilter';
import { fetchDisputes as fetchAll } from 'merchant/reducers/collection';
import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';
import { titleCase, daysFromToday, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
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
import { analyticsTrack } from 'common/utils/analytics';
import { getCustomURL } from '../../../components/DocsLink';
import { bindActionCreators } from 'redux';
import EmptyList from 'merchant/components/EmptyList';

const daysLeftInExpiry = (expiresOn) => {
  const daysLeft = daysFromToday(expiresOn);
  if (daysLeft < 0) {
    return <span class="text-muted">Passed</span>;
  } else if (daysLeft === 0) {
    return <strong class="text-danger">Today</strong>;
  } else if (daysLeft === 1) {
    return <strong class="text-danger">Tomorrow</strong>;
  } else {
    return <strong class="text-danger">{getTime('expiresOn', 'll')({ expiresOn })}</strong>;
  }
};

const EmptyComponent = () => {
  return (
    <EmptyList description={<div>No disputes found for the selected duration and criteria!</div>} />
  );
};

const type = {
  title: 'Type',
  value: (item = {}) => titleCase(item.phase),
};

const respondBy = {
  title: 'Respond By',
  value: (item) => (item.status === 'open' ? daysLeftInExpiry(item.respond_by) : '--'),
};

const createdAt = {
  title: createdAtProperty.title,
  value: getTime('created_at', 'll'),
};
class Dispute extends ListContainer {
  render() {
    return (
      <div class="content-wrapper">
        {/* passing the new props to the HeaderAction component to support the m-web view */}
        <HeaderAction responsive>
          <ShowWhen
            additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
          >
            <a
              class="btn btn-link"
              href={getCustomURL('https://razorpay.com/docs/payments/disputes/')}
              target="_blank"
              rel="noopener noreferrer"
            >
              Guide to Dispute
            </a>
          </ShowWhen>
        </HeaderAction>
        <DisputeListFilter
          form="DisputeListFilter"
          type="link"
          count={this.state.count}
          onSubmit={(args) => {
            analyticsTrack({
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
          columns={[disputeId, paymentId, amount, type, respondBy, createdAt, status]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          EmptyComponent={EmptyComponent}
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

const mapStateToProps = (state) => {
  return { mode: state.session.mode, ...state.disputes };
};

export default connect(mapStateToProps, (dispatch) => bindActionCreators({ fetchAll }, dispatch))(
  Dispute,
);
