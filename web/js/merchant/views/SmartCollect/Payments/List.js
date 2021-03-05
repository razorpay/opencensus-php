import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { RZPFeatures } from 'merchant/helpers/data';

import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

import { paymentId, amount, email, contact, createdAt, status } from 'common/ui/item/pair';

import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import PaymentsListFilter from './Filter';

import { fetchSmartCollectPayments as fetchAll } from 'merchant/reducers/collection';

@connect((state) => ({ ...state.scPayments, user: state.session.user }), { fetchAll })
@RTracking(() => window.rzpQ.component('VAPaymentsListContainer'))
export default class VAPaymentsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Smart Collect',
      eventAction: 'Go To - VA Payments',
    });

    this.track('loaded');
  }

  track = (event, options) => {
    this.props.tracking.trackEvent(
      window.rzpQ.smartCollect().interaction(`smartcollect.payments.${event}`, options),
    );
  };

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - VA Payments',
        eventAction: 'Search - Payments',
        eventLabel: label,
      });
    }

    this.track('search.submit');
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - VA Payments',
      eventAction: 'Clear Search Params - Payments',
    });

    this.track('search.clear');
  };

  onSearchEleBlur = (name) => (e) => {
    this.track(`search.${name}`, { value: e.target.value });
  };

  onErrorCloseClick = () => {
    this.track('search.error_close', {
      response: this.state.status.message[0],
    });
  };

  onSearchSubmit = (...args) => {
    this.search(...args)
      .then(() => {
        this.track('search.success');
      })
      .catch((error) => {
        this.track('search.fail', {
          response: error.errors[0],
        });
      });
  };

  trackPaymentIdCol = () => {
    this.track('list.payment_id');
  };

  get paymentIdCol() {
    return {
      ...paymentId,
      value: (...args) => <div onClick={this.trackPaymentIdCol}>{paymentId.value(...args)}</div>,
    };
  }

  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton
              feature={RZPFeatures.VA}
              onClick={() => this.track('tour')}
              onSuccess={() => this.track('tour.yes')}
              onAbort={() => this.track('tour.no')}
            />

            <DocsLink
              url="https://razorpay.com/docs/smart-collect/"
              onClick={() => {
                this.track('docs');
              }}
            />
          </div>
        </HeaderAction>

        <PaymentsListFilter
          form="paymentListFilter"
          count={this.state.count}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
          onSubmit={this.onSearchSubmit}
          onEleBlur={this.onSearchEleBlur}
          showAdvancedFilters={this.props.user.isSmartCollectAdvancedSearchFeaturesEnabled}
        />

        <PaymentsTable
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
          paginate={(params, type) => {
            this.track(`list.${type}`, {
              page: params.skip % params.count,
            });

            this.paginate(params);
          }}
          onErrorCloseClick={this.onErrorCloseClick}
          paymentColumns={[this.paymentIdCol, amount, email, contact, createdAt, status]}
        />
      </div>
    );
  }
}
