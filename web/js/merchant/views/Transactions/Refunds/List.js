import { connect } from 'react-redux';
import EntityTable from 'merchant/components/EntityTable';
import ListContainer from 'merchant/containers/ListContainer';
import RefundsListFilter from 'merchant/views/Transactions/Refunds/components/RefundsListFilter';
import { fetchRefunds as fetchAll } from 'merchant/reducers/collection';
import { refundId, paymentId, amount, createdAt, status } from 'common/ui/item/pair';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import EnableInstantRefundsModal from '../Payments/components/EnableInstantRefundsModal';
import { withRouter } from 'react-router-dom';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@withRouter
@connect((state) => state.refunds, { fetchAll, openModal })
export default class RefundsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Refunds',
      eventAction: 'Go To - Refunds',
    });
  }

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Refunds',
        eventAction: 'Search - Refunds',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Refunds',
      eventAction: 'Clear Search Params - Refunds',
    });
  };

  render() {
    const columns = [refundId, paymentId, amount, createdAt];
    columns.push(status);

    return (
      <div class="content-wrapper">
        <RefundsListFilter
          form="refundListFilter"
          count={this.state.count}
          onSubmit={(args) => {
            analyticsService.track({
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
                analyticsService.track({
                  objectName: 'refunds search',
                  actionName: 'status',
                  screen: 'transactions',
                  properties: {
                    ...args,
                    requestStatus: 'success',
                    location: 'refunds',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              })
              .catch((e) => {
                analyticsService.track({
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
