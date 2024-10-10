import { Box, Heading } from '@razorpay/blade/components';
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { SelfServeActionPages } from 'common/constant/enums';
import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';

import DataTable from 'common/ui/Table/DataTable';
import { getTime } from 'common/ui/item';
import {
  amount,
  createdAt as createdAtProperty,
  disputeId,
  paymentId,
  status,
} from 'common/ui/item/pair';
import { analyticsTrack } from 'common/utils/analytics';
import { daysFromToday, getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import { getCustomURL } from 'merchant/components/DocsLink';
import EmptyList from 'merchant/components/EmptyList';
import ShowWhen from 'merchant/components/ShowWhen';
import ListContainer from 'merchant/containers/ListContainer';
import { isOrgFeatureExist } from 'merchant/models/User';
import { fetchDisputes as fetchAll } from 'merchant/reducers/collection';
import {
  selfServerTrack,
  selfServeTrackResult,
} from 'merchant/views/Transactions/v1/AnalyticsTrack';
import { makeIdLink as disputeMakeIdLink } from 'merchant/views/Transactions/v1/Disputes/Utils';
import DisputeListFilter from 'merchant/views/Transactions/v1/Disputes/components/DisputeListFilter';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';
import { DISPUTES_NO_DATA_FOUND_TEXT } from 'merchant/views/Transactions/v2/Disputes/constants';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

const _paymentId = (splitz) => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(
        item,
        SelfServeActionPages.TransactionsDisputes,
        'disputes-table',
        splitz,
      );
      return <div>{intermediateElement}</div>;
    },
  };
};

const _disputeId = (splitz) => {
  return {
    title: disputeId.title,
    value: (item) => {
      const intermediateElement = disputeMakeIdLink('dispute')(
        item,
        SelfServeActionPages.TransactionsDisputes,
        'disputes-table',
        splitz,
      );
      return <div>{intermediateElement}</div>;
    },
  };
};
const daysLeftInExpiry = (expiresOn) => {
  const daysLeft = daysFromToday(expiresOn);
  if (daysLeft < 0) {
    return <span className="text-muted">Passed</span>;
  } else if (daysLeft === 0) {
    return <strong className="text-danger">Today</strong>;
  } else if (daysLeft === 1) {
    return <strong className="text-danger">Tomorrow</strong>;
  } else {
    return <strong className="text-danger">{getTime('expiresOn', 'll')({ expiresOn })}</strong>;
  }
};

const EmptyComponent = () => {
  return <EmptyList description={<div>{DISPUTES_NO_DATA_FOUND_TEXT}</div>} />;
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
    const { user, splitz } = this.props;
    const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;
    return (
      <div className="content-wrapper">
        <Box
          display="flex"
          justifyContent="space-between"
          alignItems="center"
          marginBottom="spacing.7"
        >
          <Heading size="medium">Disputes</Heading>
          <ShowWhen
            additionalCondition={(user) =>
              user?.isOrgAllowedFunctionality('external_links') &&
              !isOrgFeatureExist('hide_razorpay_text_link')
            }
          >
            <a
              className="btn btn-link"
              href={getCustomURL('https://razorpay.com/docs/payments/disputes/')}
              target="_blank"
              rel="noopener noreferrer"
            >
              Guide to Dispute
            </a>
          </ShowWhen>
        </Box>
        <DisputeListFilter
          form="DisputeListFilter"
          type="link"
          count={this.state.count}
          onSubmit={(args) => {
            selfServerTrack({ type: 'dispute', actionType: 'search', splitz });
            analyticsTrack({
              objectName: 'disputes search',
              actionName: 'clicked',
              screen: 'transactions',
              properties: {
                ...args,
                location: 'disputes',
                version,
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.search(args).then(() => {
              if (args.status || args.phase) {
                selfServeTrackResult({ type: 'dispute', actionType: 'filter', splitz });
              }
              selfServeTrackResult({ type: 'dispute', actionType: 'search', splitz });
            });
          }}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
          user={user}
        />

        <DataTable
          title="Disputes"
          columns={[
            _disputeId(splitz),
            _paymentId(splitz),
            amount,
            type,
            respondBy,
            createdAt,
            status,
          ]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          EmptyComponent={EmptyComponent}
          onCellClick={selfServerTrack}
          {...this.props}
        />

        <div className="row">
          <div className="col-md-10 col-md-offset-1 col-sm-12 text-center">
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
  return { mode: state.session.mode, user: state.session.user, ...state.disputes };
};

export default withSplitzService(
  connect(mapStateToProps, (dispatch) => bindActionCreators({ fetchAll }, dispatch))(
    withRouter(Dispute),
  ),
);
