import { connect } from 'react-redux';
import EntityTable from 'merchant/components/EntityTable';
import ListContainer from 'merchant/containers/ListContainer';
import RefundsListFilter from 'merchant/views/Transactions/Refunds/components/RefundsListFilter';
import { fetchRefunds as fetchAll } from 'merchant/reducers/collection';
import { refundId, paymentId, amount, createdAt, status } from 'common/ui/item/pair';
import { getKeysSeparatedByPipe, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { withRouter } from 'react-router-dom';
import { openModal } from 'merchant_common/reducers/modals';
import { analyticsTrack } from 'common/utils/analytics';
import { bindActionCreators } from 'redux';
import PaymentOptimizerProvider from 'merchant/views/Transactions/Payments/components/PaymentOptimizerProvider';

class RefundsListContainer extends ListContainer {
  componentDidMount() {
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
    if (window.rzpAnalytics) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Refunds',
        eventAction: 'Clear Search Params - Refunds',
      });
    }
  };

  render() {
    const columns = [refundId, paymentId, amount, createdAt];
    columns.push(status);

    const { user, terminalProviders } = this.props;

    if (user?.isSingleReconEnabled && user?.isOptimizerEnabled) {
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
      <div class="content-wrapper">
        <RefundsListFilter
          form="refundListFilter"
          count={this.state.count}
          onSubmit={(args) => {
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

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(RefundsListContainer));
