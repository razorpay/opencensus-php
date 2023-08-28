import React, { useEffect, useState } from 'react';
import ImgTopBg from 'assets/onboarding/top_bg.png';
import { fetchAmount } from 'merchant/reducers/fetchTransaction';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import { fetchPaymentHandle } from 'merchant/reducers/home';
import { isPgMerchant } from 'merchant/components/Activation/ActivationUtils';
import QuickActionsCard from './partials/QuickActionsCard';
import PaymentLinksCard from './partials/PaymentLinksCard';
import PaymentButtonsCard from './partials/PaymentButtonsCard';
import PaymentHandleCard from './partials/PaymentHandleCard';
import PaymentGatewayCard from './partials/PaymentGatewayCard';
import PaymentHandleModal from './partials/PaymentHandleModal';
import { showNotification } from 'merchant_common/reducers/notifications';
import { PAYMENT_HANDLE_PREFIX, PAYMENT_HANDLE_DOMAIN } from './constants';
import { trackCTAClick } from 'merchant/containers/Home/ProductOnboardingCard/events';
import copyToClipboard from 'common/utils/copyToClipboard';
import { isNone } from 'common/utils/rzp-utils';
import BottomBgImage from 'assets/product-led-onboarding/bottom-bg.svg';
import OverviewImage from 'assets/product-recommendation/overview.svg';

const ProductOnboardingCard = ({
  user,
  fetchTransactionAmount,
  transaction,
  showNotification,
  paymentHandle,
  fetchPaymentHandle,
  ...props
}) => {
  const [isHandleCopied, setIsHandleCopied] = useState(false);
  const paymentHandleData = {
    paymentHandleSlug: paymentHandle.data?.slug ?? PAYMENT_HANDLE_PREFIX,
    paymentHandleUrl:
      paymentHandle.data?.url ?? `${PAYMENT_HANDLE_DOMAIN}/${PAYMENT_HANDLE_PREFIX}`,
  };

  const { openModal, closeModal, history } = props;

  const hasMerchantTransacted = !isNone(transaction.amount) && transaction.amount > 0;
  const pgMerchant = isPgMerchant(user);
  const product = pgMerchant ? 'PG' : 'PH';

  useEffect(() => {
    fetchPaymentHandle();
    if (transaction.amount === null && !transaction.loading)
      fetchTransactionAmount(user.created_at);
  }, []);

  const onCopyHandle = ({ section }) => {
    if (isHandleCopied) return;

    trackCTAClick('PH Copy', { product, section });

    if (navigator.share) {
      navigator.share({
        title: 'Accept payment though payment handle',
        url: paymentHandleData.paymentHandleUrl,
      });
    } else {
      copyToClipboard(paymentHandleData.paymentHandleUrl);
      showNotification({
        type: 'success',
        message: 'Link successfully copied. Share it with your customers to collect payments',
        closeTimeout: 2000,
      });
      setIsHandleCopied(true);
      setTimeout(() => {
        // reset after 2sec
        setIsHandleCopied(false);
      }, 2000);
    }
  };

  const showCustomizeModal = ({ section }) => {
    trackCTAClick('PH Customize', { product, section });
    openModal({
      component: (
        <PaymentHandleModal
          closeModal={closeModal}
          paymentHandleData={paymentHandleData}
          //! Commenting this because Edit Payment Link feature is called off from Product
          //! Slack Reference - https://razorpay.slack.com/archives/C043K5N223F/p1668411467771009?thread_ts=1668410847.606959&cid=C043K5N223F
          // isPaymentHandleEditable={!hasMerchantTransacted}
          // fetchPaymentHandle={fetchPaymentHandle}
          showNotification={showNotification}
          product={product}
        />
      ),
      size: 'medium',
      className: 'PaymentHandle--Modal',
    });
  };

  const goToPaymentLinks = ({ section }) => {
    trackCTAClick('PL', { product, section });
    history.push(`/paymentlinks/new?redirect=/dashboard`);
  };

  if (hasMerchantTransacted) {
    return (
      <div className="product-onboarding-card-container">
        <QuickActionsCard
          paymentHandleData={paymentHandleData}
          showCustomizeModal={() => showCustomizeModal({ section: 'QuickAction' })}
          goToPaymentLinks={() => goToPaymentLinks({ section: 'QuickAction' })}
          onCopyHandle={() => onCopyHandle({ section: 'QuickAction' })}
          isCTADisabled={paymentHandle.loading}
          isHandleCopied={isHandleCopied}
        />
      </div>
    );
  }

  return (
    <div className="product-onboarding-card-container">
      <div className="product-onboarding-card product-led-onboarding">
        <div className="illustration-top">
          <img src={ImgTopBg} alt="Top" />
        </div>
        <div className="illustration-bottom">
          <img src={BottomBgImage} />
        </div>
        <div className="illustration-right">
          <img
            src={OverviewImage}
            // eslint-disable-next-line react/no-unknown-property
            fetchpriority="high"
          />
        </div>
        <p className="title">
          Congratulations {user.contact_name}! You can start collecting payments
        </p>
        <div className="card-container">
          {pgMerchant ? (
            <>
              <PaymentGatewayCard history={history} product="PG" />
              <PaymentButtonsCard history={history} product="PG" />
              <PaymentHandleCard
                paymentHandleData={paymentHandleData}
                showCustomizeModal={() => showCustomizeModal({ section: 'Regular' })}
                onCopyHandle={() => onCopyHandle({ section: 'Regular' })}
                isCTADisabled={paymentHandle.loading}
                isHandleCopied={isHandleCopied}
              />
            </>
          ) : (
            <>
              <PaymentHandleCard
                paymentHandleData={paymentHandleData}
                showCustomizeModal={() => showCustomizeModal({ section: 'Regular' })}
                onCopyHandle={() => onCopyHandle({ section: 'Regular' })}
                isCTADisabled={paymentHandle.loading}
                isHandleCopied={isHandleCopied}
              />
              <PaymentButtonsCard history={history} product="PH" />
              <PaymentLinksCard
                goToPaymentLinks={() => goToPaymentLinks({ section: 'Regular' })}
                product="PH"
              />
            </>
          )}
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  transaction: state.transactionAmount,
  paymentHandle: state.home.paymentHandle,
});

export default compose(
  withRouter,
  connect(mapStateToProps, {
    openModal: fnOpenModal,
    closeModal: fnCloseModal,
    fetchTransactionAmount: fetchAmount,
    showNotification,
    fetchPaymentHandle,
  }),
)(ProductOnboardingCard);
