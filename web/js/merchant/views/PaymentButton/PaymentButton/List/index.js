import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';

import { RZPFeatures } from 'merchant/helpers/data';
import { buttonTitle, itemName, unitsSold, createdAt } from 'common/ui/item/pair';

import Amount from 'common/ui/Amount';
import DataTable from 'common/ui/Table/DataTable';
import ProductWrapper from 'common/ui/ProductWrapper';
import DocsLink, { DocLink } from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import TestModeBanner from 'merchant/components/TestModeBanner';

import ListFilter from './ListFilter';
import GetCodeModal from 'merchant/views/PaymentButton/PaymentButton/components/GetCodeModal'; // SuccessModal

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { handleProductQuickGuide } from 'merchant/reducers/onboarding';
import { fetchPaymentButtonsList as fetchAll } from 'merchant/reducers/paymentbuttons/list';
import { setIsPaymentButtonCodeUsed } from 'merchant/views/PaymentButton/utils';
import track from './track';

import EmptyListImageRzp from 'assets/payment_button/empty-list.svg';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import EmptyListImageCurlec from 'assets/payment_button/sidebar-display-curlec.svg';

const PAYMENT_BTN_EMPTYLIST_IMG = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: EmptyListImageRzp,
  [ORG_CUSTOM_CODE_MAP.CURLEC]: EmptyListImageCurlec,
};

const TABS_DATA = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: [
    { title: 'Payment Buttons', url: '/paymentbuttons' },
    { title: 'Subscription Buttons', url: '/subscription_buttons' },
  ],
  [ORG_CUSTOM_CODE_MAP.CURLEC]: [{ title: 'Payment Buttons', url: '/paymentbuttons' }],
};

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

const emptyComponent = (org) => {
  const customCode = org.custom_code;
  const emptyListImage =
    PAYMENT_BTN_EMPTYLIST_IMG[customCode] ||
    PAYMENT_BTN_EMPTYLIST_IMG[ORG_CUSTOM_CODE_MAP.RAZORPAY];
  return () => (
    <div class="PaymentButton-empty-list">
      <img src={emptyListImage} width="280px" />

      <div class="description">
        <h4>It’s Lonely Here!</h4>
        <div>Create a Payment Button to get Started</div>
        <br />
        Not sure where to start? See our getting{' '}
        <DocLink target="_blank" href="https://razorpay.com/docs/payment-button/">
          started guide <i class="i i-external-link" />
        </DocLink>
      </div>
    </div>
  );
};

@withRouter
@connect(
  (state) => ({
    ...state.paymentbuttons,
    ...state.session,
  }),
  { fetchAll, openModal, closeModal, handleProductQuickGuide },
)
@RTracking(() => window.rzpQ.component('PaymentButtonsList'))
export default class PaymentButtonsList extends ListContainer {
  state = {
    isPaymentButtonOpen: false,
  };

  componentDidMount() {
    track.init(this.props.tracking.trackEvent);
  }

  openGetCodeModal = (paymentButtonEntity) => {
    track.getCode(paymentButtonEntity.id);

    this.props.openModal({
      size: 'medium',
      className: 'GetCodeModal',
      component: (
        <GetCodeModal
          title="Copy Button Code"
          paymentButton={paymentButtonEntity}
          closeModal={() => {
            this.props.closeModal();

            track.getCodeModalClosed(paymentButtonEntity.id);
          }}
          onCodeCopy={() => track.codeCopy(paymentButtonEntity.id)}
          onClickSeeDocumentation={() => track.openDocs(paymentButtonEntity.id)}
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

  openPaymentButtonsNewPage = () => {
    track.createEnter();

    this.setState(
      {
        isPaymentButtonOpen: true,
      },
      () => {
        this.props.history.push('/paymentbuttons/new');
      },
    );
  };

  render() {
    const { user, org } = this.props;
    const isRoleAllowedEdit = user.isAllowedEdit('payment_buttons');
    const customCode = org.custom_code;
    const tabsData = TABS_DATA[customCode] || TABS_DATA[ORG_CUSTOM_CODE_MAP.RAZORPAY];

    const columns = [
      buttonTitle,
      totalSales,
      itemName,
      unitsSold,
      createdAt,
      status,
      getActions(this.openGetCodeModal),
    ];

    return (
      <ProductWrapper
        tabsData={tabsData}
        extra={
          <>
            <ShowWhen additionalCondition={(_user) => !_user.isOrgAxis}>
              <TakeATourButton feature={RZPFeatures.PB} onSuccess={this.resetCopyPasteCodeStatus} />
            </ShowWhen>

            <DocsLink url="https://razorpay.com/docs/payment-button/" />

            {isRoleAllowedEdit && user.isPaymentButtonEnabledByRazorX && (
              <span class="cta-container">
                <span class="btn btn-primary" onClick={this.openPaymentButtonsNewPage}>
                  <i class="i i-plus" />
                  <span>Create Payment Button</span>
                </span>
              </span>
            )}
          </>
        }
      >
        <content>
          <div class="PaymentButtons--ListingPage content-wrapper">
            <TestModeBanner />
            {/* an example of passing custom value to the filter length for mobile for ListFilter Component */}
            <ListFilter
              form="paymentButtonListFilter"
              count={this.state.count}
              onClearAnalytics={track.searchClear}
              onSubmit={this.search}
              maxMwebFiltersLength={3}
            />
            <DataTable
              title="Payment Buttons"
              columns={columns}
              paginate={(params, type) => {
                track.paginate(params, type);

                this.paginate(params, type);
              }}
              {...this.props}
              count={this.state.count}
              skip={this.state.skip}
              EmptyComponent={emptyComponent(org)}
              onErrorCloseClick={() => {
                track.errorCloseClick(this.state.status.message);
              }}
            />
          </div>
        </content>
      </ProductWrapper>
    );
  }
}
