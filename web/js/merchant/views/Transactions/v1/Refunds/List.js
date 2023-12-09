import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import { SelfServeActionPages } from 'common/constant/enums';
import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { refundId, paymentId, amount, createdAt, enchancedRefundStatus } from 'common/ui/item/pair';
import { analyticsTrack } from 'common/utils/analytics';
import { getKeysSeparatedByPipe, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import EntityTable from 'merchant/components/EntityTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchRefunds as fetchAll } from 'merchant/reducers/collection';
import {
  selfServerTrack,
  selfServeTrackResult,
} from 'merchant/views/Transactions/v1/AnalyticsTrack';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';
import PaymentOptimizerProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';
import { makeIdLink as refundMakeIdLink } from 'merchant/views/Transactions/v1/Refunds/Utils';
import RefundsListFilter from 'merchant/views/Transactions/v1/Refunds/components/RefundsListFilter';
import { openModal } from 'merchant_common/reducers/modals';

class RefundsListContainer extends ListContainer {
  componentDidMount() {
    /* istanbul ignore else */
    if (window.rzpAnalytics) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Refunds',
        eventAction: 'Go To - Refunds',
      });
    }
  }

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0 && window.rzpAnalytics) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Refunds',
        eventAction: 'Search - Refunds',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    /* istanbul ignore else */
    if (window.rzpAnalytics) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Refunds',
        eventAction: 'Clear Search Params - Refunds',
      });
    }
  };

  get _paymentId() {
    return {
      title: paymentId.title,
      value: (item) => {
        const intermediateElement = makeIdLink('payment')(
          item,
          'Transactions.Refunds',
          'refunds-table',
        );
        return <div>{intermediateElement}</div>;
      },
    };
  }

  get _refundId() {
    return {
      title: refundId.title,
      value: (item) => {
        const intermediateElement = refundMakeIdLink('refund')(
          item,
          SelfServeActionPages.TransactionsRefunds,
        );
        return <div>{intermediateElement}</div>;
      },
    };
  }

  refundGatewayData = (splitz) => {
    const { abExperiments } = splitz || { abExperiments: { refund_gateway_data: undefined } };
    if (!abExperiments?.refund_gateway_data) return false;
    return isExperimentEnabled(abExperiments.refund_gateway_data);
  };

  render() {
    const { user, terminalProviders, splitz } = this.props;
    const isOptimizerView = user?.isSingleReconEnabled && user?.isOptimizerEnabled;
    const isRefundGatewayDataEnabled = this.refundGatewayData(splitz);
    const showStatusInfo = isOptimizerView && isRefundGatewayDataEnabled;
    const columns = [
      this._refundId,
      this._paymentId,
      amount,
      createdAt,
      enchancedRefundStatus(showStatusInfo),
    ];

    if (isOptimizerView) {
      columns.splice(1, 0, {
        title: 'Payment Provider',
        value: (item) => (
          <PaymentOptimizerProvider
            terminal_id={item.optimizer_provider}
            settled_by={item.settled_by}
            terminalProviders={terminalProviders}
            hideExternalLink={true}
          />
        ),
      });
    }

    return (
      <div className="content-wrapper" data-testid="refunds-list">
        <RefundsListFilter
          form="refundListFilter"
          count={this.state.count}
          onSubmit={(args) => {
            selfServerTrack({ type: 'refund', actionType: 'search' });

            analyticsTrack({
              objectName: 'refunds search',
              actionName: 'clicked',
              screen: 'transactions',
              properties: {
                ...args,
                location: 'refunds',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.search(args)
              .then(() => {
                if (args.public_status) {
                  selfServeTrackResult({ type: 'refund', actionType: 'filter' });
                }
                selfServeTrackResult({ type: 'refund', actionType: 'search' });
                analyticsTrack({
                  objectName: 'refunds search',
                  actionName: 'status',
                  screen: 'transactions',
                  properties: {
                    id: args.id,
                    refundStatus: args.status,
                    emailFilled: Boolean(args.email),
                    notesFilled: Boolean(args.notes),
                    count: args.count,
                    status: 'success',
                    location: 'refunds',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              })
              .catch((e) => {
                analyticsTrack({
                  objectName: 'refunds search',
                  actionName: 'status',
                  screen: 'transactions',
                  properties: {
                    ...args,
                    status: 'failure',
                    failureReason: e.errors[0],
                    location: 'refunds',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              });
          }}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <EntityTable
          title="Refunds"
          columns={columns}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          onCellClick={selfServerTrack}
          customClass="refunds-v1-table"
          {...this.props}
        />
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  const { refunds, session, navigator } = state;
  return {
    ...refunds,
    user: session.user,
    terminalProviders: navigator.terminalProviders,
  };
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ fetchAll, openModal }, dispatch);

export default compose(
  withSplitzService,
  connect(mapStateToProps, mapDispatchToProps),
)(withRouter(RefundsListContainer));
