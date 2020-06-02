import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';

import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import PaymentsListFilter from './Filter';

import { fetchSmartCollectPayments as fetchAll } from 'merchant/reducers/collection';

@connect(state => state.scPayments, { fetchAll })
export default class VirtualAccountsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Go To - VA Payments',
    });
  }

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - VA Payments',
        eventAction: 'Search - Payments',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - VA Payments',
      eventAction: 'Clear Search Params - Payments',
    });
  };

  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton feature={RZPFeatures.VA} />

            <DocsLink url="https://razorpay.com/docs/smart-collect/" />
          </div>
        </HeaderAction>

        <PaymentsListFilter
          form="paymentListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <PaymentsTable
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
