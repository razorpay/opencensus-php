import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
// import RTracking from 'react-tracking';

import { RZPFeatures } from 'merchant/helpers/data';
import { buttonTitle, itemName, unitsSold, createdAt } from 'common/ui/item/pair';

import Amount from 'common/ui/Amount';
import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';

import ListFilter from './ListFilter';
import GetCodeModal from '../components/GetCodeModal'; // SuccessModal

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { handleProductQuickGuide } from 'merchant/reducers/onboarding';
import { fetchSubscriptionButtonsList as fetchAll } from 'merchant/reducers/subscriptionButtons/list';
import { setIsPaymentButtonCodeUsed } from '../../utils';
import track from './track';

const getActions = (openGetCodeModal) => ({
  title: 'Actions',
  columnClass: 'action-col',
  value: (item) => (
    <a class="get-code-btn" onClick={() => openGetCodeModal(item)}>
      GET BUTTON CODE
    </a>
  ),
});

const totalSales = {
  title: 'Total Sales',
  value: (item) => <Amount value={item.total_amount_paid} currency={item.currency} />,
};

export const status = {
  columnClass: 'status-col',
  title: 'Status',
  value: (item) => <PaymentPagesStatusLabel status={item.status} />,
};

@withRouter
@connect(
  (state) => ({
    ...state.subscription_buttons,
    ...state.session,
  }),
  { fetchAll, openModal, closeModal, handleProductQuickGuide },
)
// @RTracking(() => window.rzpQ.component('PaymentButtonsList'))
export default class PaymentButtonsList extends ListContainer {
  state = {
    isSubscriptionButtonOpen: false,
  };

  componentDidMount() {
    // track.lj.init({
    //   track: this.props.tracking.trackEvent,
    // });
  }

  openGetCodeModal = (subscriptionButtonEntity) => {
    // track.lj.trackGetCode(subscriptionButtonEntity.id);

    this.props.openModal({
      size: 'medium',
      className: 'GetCodeModal',
      component: (
        <GetCodeModal
          title="Copy Button Code"
          paymentButton={subscriptionButtonEntity}
          closeModal={() => {
            this.props.closeModal();

            // track.lj.trackGetCodeModalClosed(subscriptionButtonEntity.id);
          }}
          // onClickCopy={() => track.lj.trackCopyCode(subscriptionButtonEntity.id)}
          // onCodeCopy={() => track.lj.trackCodeCopy(subscriptionButtonEntity.id)}
          // onClickSeeDocumentation={() => track.lj.trackOpenDocs(subscriptionButtonEntity.id)}
        />
      ),
    });
  };

  handleProductQuickGuide = () => {
    this.props.handleProductQuickGuide({
      feature: RZPFeatures.PB,
      showOnboarding: true,
      isQuickGuideOpen: true,
      isTour: true,
    });

    this.resetCopyPasteCodeStatus();
  };

  resetCopyPasteCodeStatus = () => {
    setIsPaymentButtonCodeUsed(
      {
        mid: this.props.user.current,
        mode: this.props.mode,
      },
      false,
    );
  };

  openSubscriptionButtonsNewPage = () => {
    // track.lj.trackCreateEnter();

    this.setState(
      {
        isSubscriptionButtonOpen: true,
      },
      () => {
        this.props.history.push('/subscription_buttons/new');
      },
    );
  };

  render() {
    const { user } = this.props;
    const isRoleAllowedEdit = user.isAllowedEdit('subscription_buttons');

    return (
      <div class="PaymentButtons--ListingPage content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton feature={RZPFeatures.PB} onSuccess={this.resetCopyPasteCodeStatus} />

            <DocsLink url="https://razorpay.com/docs/subscription-button/" />

            {isRoleAllowedEdit && user.isSubscriptionButtonEnabledByRazorX && (
              <span class="btn btn-primary" onClick={this.openSubscriptionButtonsNewPage}>
                <i class="i i-plus" />
                <span>Create Subscription Button</span>
              </span>
            )}
          </div>
        </HeaderAction>

        <ListFilter
          form="paymentButtonListFilter"
          count={this.state.count}
          // onClearAnalytics={track.lj.trackSearchClear}
          onSubmit={this.search}
        />

        <DataTable
          title="Payment Buttons"
          columns={[
            buttonTitle,
            totalSales,
            itemName,
            unitsSold,
            createdAt,
            status,
            getActions(this.openGetCodeModal),
          ]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={(params, type) => {
            // track.lj.trackPaginate(params, type);

            this.paginate(params, type);
          }}
          {...this.props}
          EmptyComponent={EmptyComponent}
          // onErrorCloseClick={() => {track.lj.trackErrorCloseClick(this.state.status.message);}}
        />
      </div>
    );
  }
}

const EmptyComponent = () => (
  <div class="PaymentButton-empty-list">
    <img src="/dist/css/assets/payment_button/empty-list.svg" />

    <div class="description">
      <h4>It’s Lonely Here!</h4>
      <div>Create a Payment Button to get Started</div>
      <br />
      Not sure where to start? See our getting{' '}
      <a target="_blank" href="https://razorpay.com/docs/payment-button/">
        started guide <i class="i i-external-link" />
      </a>
    </div>
  </div>
);
