import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';

import { RZPFeatures } from 'merchant/helpers/data';
import { subscriptionButtonTitle, createdAt } from 'common/ui/item/pair';

import DataTable from 'common/ui/Table/DataTable';
import ProductWrapper from 'common/ui/ProductWrapper';
import DocsLink, { DocLink } from 'merchant/components/DocsLink';
import ListContainer from 'merchant/containers/ListContainer';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import TestModeBanner from 'merchant/components/TestModeBanner';
import Popover, { PopoverBody } from 'common/ui/Popover';

import ListFilter from './ListFilter';
import GetCodeModal from 'merchant/views/PaymentButton/SubscriptionButton/components/GetCodeModal'; // SuccessModal

import { classList } from 'common/utils/rzp-utils';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { handleProductQuickGuide } from 'merchant/reducers/onboarding';
import { fetchSubscriptionButtonsList as fetchAll } from 'merchant/reducers/subscriptionButtons/list';
import { setIsPaymentButtonCodeUsed } from 'merchant/views/PaymentButton/utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import track from './track';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import EmptyListImage from 'assets/payment_button/empty-list.svg';

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

const itemNames = {
  title: 'Items',
  value: (item) => {
    const recurringItems = [];
    const oneTimeItems = [];

    item.payment_page_items.forEach((payment_page_item) => {
      if (payment_page_item.plan_id) {
        recurringItems.push(payment_page_item);
      } else {
        oneTimeItems.push(payment_page_item);
      }
    });

    return (
      <React.Fragment>
        <div>
          <span class={classList(recurringItems.length && 'Button--transparent Button')}>
            Recurring Plans ({recurringItems.length})
            {!!recurringItems.length && (
              <Popover align={isMobileDevice() ? 'top' : 'right'}>
                <PopoverBody>
                  <table width="100%">
                    <thead>
                      <tr>
                        <th>Plan Name</th>
                        <th class="text-right">Subscriptions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {recurringItems.map((_item, index) => (
                        <tr key={index}>
                          <td>{_item.item.name}</td>
                          <td class="text-right">{_item.quantity_sold}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </PopoverBody>
              </Popover>
            )}
          </span>
        </div>

        <div>
          <span class={classList(oneTimeItems.length && 'Button--transparent Button ')}>
            One-Time Items ({oneTimeItems.length})
            {!!oneTimeItems.length && (
              <Popover align="right">
                <PopoverBody>
                  <table width="100%">
                    <thead>
                      <tr>
                        <th>Item Name</th>
                        <th class="text-right">Units Sold</th>
                      </tr>
                    </thead>

                    <tbody>
                      {oneTimeItems.map((_item, index) => (
                        <tr key={index}>
                          <td>{_item.item.name}</td>
                          <td class="text-right">{_item.quantity_sold}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </PopoverBody>
              </Popover>
            )}
          </span>
        </div>
      </React.Fragment>
    );
  },
};

const totalTransactions = {
  title: 'Transactions',
  value: (item) => {
    const recurringItems = [];
    const oneTimeItems = [];

    item.payment_page_items.forEach((payment_page_item) => {
      if (payment_page_item.plan_id) {
        recurringItems.push(payment_page_item);
      } else {
        oneTimeItems.push(payment_page_item);
      }
    });

    return (
      <React.Fragment>
        <div>{recurringItems.reduce((total, _item) => total + Number(_item.quantity_sold), 0)}</div>
        <div>{oneTimeItems.reduce((total, _item) => total + Number(_item.quantity_sold), 0)}</div>
      </React.Fragment>
    );
  },
};

export const status = {
  columnClass: 'status-col',
  title: 'Status',
  value: (item) => <PaymentPagesStatusLabel status={item.status} />,
};

@connect(
  (state) => ({
    ...state.subscription_buttons,
    ...state.session,
  }),
  { fetchAll, openModal, closeModal, handleProductQuickGuide },
)
@RTracking(() => window.rzpQ.component('SubscriptionButtonsList'))
class SubscriptionButtonsList extends ListContainer {
  state = {
    isSubscriptionButtonOpen: false,
  };

  componentDidMount() {
    track.lj.init({
      track: this.props.tracking.trackEvent,
    });
  }

  openGetCodeModal = (subscriptionButtonEntity) => {
    track.lj.trackGetCode(subscriptionButtonEntity.id);

    this.props.openModal({
      size: 'medium',
      className: 'GetCodeModal',
      component: (
        <GetCodeModal
          title="Copy Button Code"
          paymentButton={subscriptionButtonEntity}
          closeModal={() => {
            this.props.closeModal();

            track.lj.trackGetCodeModalClosed(subscriptionButtonEntity.id);
          }}
          onClickCopy={() => track.lj.trackCopyCode(subscriptionButtonEntity.id)}
          onCodeCopy={() => track.lj.trackCodeCopy(subscriptionButtonEntity.id)}
          onClickSeeDocumentation={() => track.lj.trackOpenDocs(subscriptionButtonEntity.id)}
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
    track.lj.trackCreateEnter();

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
    const { user, org } = this.props;
    const isRoleAllowedEdit = user.isAllowedEdit('subscription_buttons');
    const customCode = org.custom_code;
    const tabsData = TABS_DATA[customCode] || TABS_DATA[ORG_CUSTOM_CODE_MAP.RAZORPAY];

    const columns = [
      subscriptionButtonTitle,
      itemNames,
      totalTransactions,
      createdAt,
      status,
      getActions(this.openGetCodeModal),
    ];

    return (
      <ProductWrapper
        tabsData={tabsData}
        extra={
          <>
            <TakeATourButton feature={RZPFeatures.PB} onSuccess={this.resetCopyPasteCodeStatus} />

            <DocsLink url="https://razorpay.com/docs/payment-button/subscription-buttons/" />

            {isRoleAllowedEdit && user.isSubscriptionButtonEnabled && (
              <span class="cta-container">
                <span class="btn btn-primary" onClick={this.openSubscriptionButtonsNewPage}>
                  <i class="i i-plus" />
                  <span>Create Subscription Button</span>
                </span>
              </span>
            )}
          </>
        }
      >
        <content>
          <div class="PaymentButtons--ListingPage content-wrapper">
            <TestModeBanner />
            <ListFilter
              form="paymentButtonListFilter"
              count={this.state.count}
              onClearAnalytics={track.lj.trackSearchClear}
              onSubmit={this.search}
            />

            <DataTable
              title="Subscription Buttons"
              columns={columns}
              paginate={(params, type) => {
                track.lj.trackPaginate(params, type);

                this.paginate(params, type);
              }}
              {...this.props}
              count={this.state.count}
              skip={this.state.skip}
              EmptyComponent={EmptyComponent}
              onErrorCloseClick={() => {
                track.lj.trackErrorCloseClick(this.state.status.message);
              }}
            />
          </div>
        </content>
      </ProductWrapper>
    );
  }
}

function EmptyComponent() {
  return (
    <div class="PaymentButton-empty-list">
      <img src={EmptyListImage} width="280px" />

      <div class="description">
        <h4>It’s Lonely Here!</h4>
        <div>Create a Subscription Button to get Started</div>
        <br />
        Not sure where to start? See our getting{' '}
        <DocLink
          target="_blank"
          href="https://razorpay.com/docs/payment-button/subscription-buttons/"
        >
          started guide <i class="i i-external-link" />
        </DocLink>
      </div>
    </div>
  );
}

export default withRouter(SubscriptionButtonsList);
