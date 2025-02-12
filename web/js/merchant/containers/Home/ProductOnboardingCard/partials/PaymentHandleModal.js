import React, { useState, useMemo } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
// import EditPaymentHandle from './EditPaymentHandle';
import { merchantFetch } from 'merchant/utils/ajax';
import { PaymentHandleAmount } from 'merchant/containers/Home/ProductOnboardingCard/fields';
import { DEFAULT_ERROR_MESSAGE } from 'merchant/containers/Home/ProductOnboardingCard/constants';
import copyToClipboard from 'common/utils/copyToClipboard';
import { validateAmount } from 'common/utils/validators';
import { getHandleEntities } from 'merchant/containers/Home/ProductOnboardingCard/utils';
import { trackCTAClick } from 'merchant/containers/Home/ProductOnboardingCard/events';
import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import errorService from '@razorpay/universe-utils/errorService';

const PaymentHandleModal = (props) => {
  const {
    closeModal,
    paymentHandleData,
    // isPaymentHandleEditable,
    showNotification,
    product,
  } = props;

  const [amount, setAmount] = useState('');
  const [isSaving, setIsSaving] = useState(false);
  const handleEntities = getHandleEntities(paymentHandleData.paymentHandleUrl);

  //! Commenting this because Edit Payment Link feature is called off from Product
  //! Slack Reference - https://razorpay.slack.com/archives/C043K5N223F/p1668411467771009?thread_ts=1668410847.606959&cid=C043K5N223F
  // const [paymentHandleUrl, setPaymentHandleUrl] = useState('');
  // const [paymentHandleSlug, setPaymentHandleSlug] = useState('');
  // const [isHandleValid, setIsHandleValid] = useState(false);

  // const updateHandle = async () => {
  //   const { data } = await merchantFetch({
  //     url: 'payment_handle',
  //     data: {
  //       slug: paymentHandleSlug,
  //     },
  //     method: 'PATCH',
  //   });
  //   return data?.url;
  // };
  // const isSlugChanged = paymentHandleData.paymentHandleSlug !== paymentHandleSlug;
  // const updatePaymentHandleData = ({ paymentHandleUrl, paymentHandleSlug }) => {
  //   setPaymentHandleUrl(paymentHandleUrl);
  //   setPaymentHandleSlug(paymentHandleSlug);
  // };

  const updateAmount = async () => {
    const { data } = await merchantFetch({
      url: 'payment_handle/custom_amount',
      data: {
        amount: parseInt(amount || 0, 10) * 100,
      },
      method: 'POST',
    });
    return `${paymentHandleData.paymentHandleUrl}?amount=${data.encrypted_amount}`;
  };

  const handleError = ({ error = 'CARE ERROR' } = {}) => {
    errorService.captureError(error, {
      tags: {
        team: Teams.PG_DASHBOARD,
      },
      rank: Ranks.P2,
    });
  };

  const shareURL = (url, title = 'Accept payment through payment handle') => {
    if (navigator.share) {
      try {
        navigator
          .share({
            title,
            url,
          })
          .catch((error) => {
            if (error.name === 'AbortError') {
              console.log('Share operation aborted by the user.');
            } else {
              handleError({ error });
            }
          });
      } catch (error) {
        handleError({ error });
      }
    } else {
      copyToClipboard(url);
      showNotification({
        type: 'success',
        message: 'Link successfully copied. Share it with your customers to collect payments',
      });
    }
  };
  const onSubmit = async () => {
    trackCTAClick('PH Copy', { product, section: 'CustomModal' });
    setIsSaving(true);
    let paymentLink = '';

    try {
      //! TCommenting this because Edit Payment Link feature is called off from Product
      //! Slack Reference - https://razorpay.slack.com/archives/C043K5N223F/p1668411467771009?thread_ts=1668410847.606959&cid=C043K5N223F
      // if (paymentHandleData?.paymentHandleUrl !== paymentHandleUrl) {
      //   paymentLink = await updateHandle();
      //   fetchPaymentHandle();
      // }
      if (amount) {
        paymentLink = await updateAmount();
        shareURL(paymentLink);
      } else {
        shareURL(`${paymentHandleData.paymentHandleUrl}`);
      }
      closeModal();
    } catch (err) {
      showNotification({
        type: 'error',
        message: err?.errors?.[0] ?? DEFAULT_ERROR_MESSAGE,
      });
    } finally {
      setIsSaving(false);
    }
  };

  const isCtaDisabled = useMemo(() => {
    const isValidAmount = amount.length === 0 || !validateAmount(amount);
    // return (isSlugChanged && !isHandleValid) || !isValidAmount || isSavingLink;
    return !isValidAmount || isSaving;
  }, [
    amount,
    isSaving,
    // paymentHandleData.slug, paymentHandleSlug, isHandleValid,
  ]);

  const onAmountInputChange = (ev) => {
    const {
      target: { value },
    } = ev;
    setAmount(value);
  };

  return (
    <div className="payment-handle-modal product-led-onboarding">
      <ModalHeader
        title="Share your Razorpay.me link"
        onCloseClick={() => {
          closeModal();
        }}
      />
      <div className="modal-body">
        <div className="form-group handle-input-container">
          {/* 
          //! Commenting this because Edit Payment Link feature is called off from Product
          //! Slack Reference - https://razorpay.slack.com/archives/C043K5N223F/p1668411467771009?thread_ts=1668410847.606959&cid=C043K5N223F
          <EditPaymentHandle
            product={product}
            paymentHandleData={paymentHandleData}
            isPaymentHandleEditable={isPaymentHandleEditable}
            updateIsHandleValid={setIsHandleValid}
            updatePaymentHandleData={updatePaymentHandleData}
          /> */}
          <div className="handle-link">
            <span>{`${handleEntities.domain}${handleEntities.prefix}`}</span>
            <span>{handleEntities.slug}</span>
          </div>
        </div>
        <div className="form-group" className="amount-input-container">
          <PaymentHandleAmount required={false} defaultAmount="" onChange={onAmountInputChange} />
        </div>
        <div>
          You can add a specific amount for your customer to pay. This will not affect your default
          link.
        </div>
        <div className="Modal__actions">
          <button className="btn btn-primary" disabled={isCtaDisabled} onClick={onSubmit}>
            {isSaving
              ? 'Saving...'
              : `${amount ? 'Save and ' : ''}${navigator.share ? 'Share' : 'Copy'} Link`}
          </button>
        </div>
      </div>
    </div>
  );
};

export default PaymentHandleModal;
