import React, { useState, useEffect } from 'react';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/SocialShareGroup';
import ModalHeader from 'common/ui/ModalHeader';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import SelectBox from 'merchant/views/PartnerDashboard/SubMerchant/components/SelectBox';
import ShareReferralLink from 'assets/onboarding/share-referral-link.png';
import Image from 'common/ui/Image';
import ShowWhen from 'merchant/components/ShowWhen';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

export default function ReferralBox({
  user,
  closeModal,
  referralData,
  tracking,
  partnerID,
  partnershipForXEnabled,
  product = PRODUCT_TYPE.PG,
}) {
  const [productType, setProductType] = useState(PRODUCT_TYPE.PG);
  const pgReferralLink = referralData?.[PRODUCT_TYPE.PG]?.url ?? '';
  const bankingReferralLink = referralData?.[PRODUCT_TYPE.X]?.url ?? '';
  const capitalReferralLink = referralData?.[PRODUCT_TYPE.CAPITAL]?.url ?? '';
  const isCapitalProduct = productType === PRODUCT_TYPE.CAPITAL;
  const getCurrentProduct = () => {
    if (productType === PRODUCT_TYPE.PG) {
      return 'Payments';
    }
    if (productType === PRODUCT_TYPE.X) {
      return 'X';
    }
    return '';
  };
  useEffect(() => {
    switch (product) {
      case PRODUCT_TYPE.CAPITAL:
        setProductType(PRODUCT_TYPE.CAPITAL);
        break;
      case PRODUCT_TYPE.X:
        setProductType(PRODUCT_TYPE.X);
        break;
      default:
        setProductType(PRODUCT_TYPE.PG);
    }
  }, [product]);

  const trackUserEvent = (eventName, properties = {}) => {
    const productGroup = getCurrentProduct();
    tracking.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        productGroup,
        ...properties,
      }),
    );
  };

  useEffect(() => {
    if (productType !== '') {
      trackUserEvent('partnerships.submerchant.referral.product_group');
    }
  }, [productType]);

  const handleModalClose = () => {
    closeModal();
    trackUserEvent('partnerships.submerchant.referral.product_group', {
      action: 'cancel',
    });
  };

  if (!partnershipForXEnabled) {
    return (
      <div className="referral-box-modal">
        <div style={{ padding: '10px' }}>
          <Image src={ShareReferralLink} isWebP height="50" />{' '}
          <strong>
            <strong>Share Referral Link</strong>
          </strong>
          <button
            type="button"
            class="close"
            onClick={handleModalClose}
            style={{ marginTop: '10px' }}
          >
            <i class="i i-close" />
          </button>
        </div>
        <div style={{ padding: '14px' }}>
          <p>
            You <strong>get 0.1% commission for every transaction</strong> done by your affiliate
            accounts who signs up with this link.
          </p>
          <SocialShareGroup
            referralUrl={pgReferralLink}
            tracking={tracking}
            product={PRODUCT_TYPE.PG}
            partnerID={partnerID}
          />
        </div>
      </div>
    );
  }
  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} rank={Ranks.P1} resetOnProps>
      <div class="referral-box-modal">
        <ModalHeader title="Share Referral Link" onCloseClick={handleModalClose} />
        <div className="modal-body">
          <SelectBox
            label="Razorpay Payments"
            description="Invite affiliates to use Razorpay Payment products to collect payments"
            onClick={() => setProductType(PRODUCT_TYPE.PG)}
            checked={productType === PRODUCT_TYPE.PG}
          >
            {productType === PRODUCT_TYPE.PG ? (
              <SocialShareGroup
                referralUrl={pgReferralLink}
                tracking={tracking}
                product={PRODUCT_TYPE.PG}
                partnerID={partnerID}
              />
            ) : (
              ''
            )}
          </SelectBox>
          <ShowWhen additionalCondition={() => user.isPartnershipForCapitalEnabled}>
            <SelectBox
              label="Line Of Credit"
              description="Refer merchants to Capital products like Line Of Credit"
              onClick={() => setProductType(PRODUCT_TYPE.CAPITAL)}
              checked={isCapitalProduct}
            >
              {isCapitalProduct ? (
                <SocialShareGroup
                  referralUrl={capitalReferralLink}
                  tracking={tracking}
                  product={PRODUCT_TYPE.CAPITAL}
                  partnerID={partnerID}
                />
              ) : (
                ''
              )}
            </SelectBox>
          </ShowWhen>
          <SelectBox
            label="RazorpayX"
            description="Invite affiliates to open RazorpayX powered Current Account to process payouts"
            onClick={() => setProductType(PRODUCT_TYPE.X)}
            checked={productType === PRODUCT_TYPE.X}
          >
            {productType === PRODUCT_TYPE.X ? (
              <SocialShareGroup
                referralUrl={bankingReferralLink}
                tracking={tracking}
                product={PRODUCT_TYPE.X}
                partnerID={partnerID}
              />
            ) : (
              ''
            )}
          </SelectBox>
        </div>
      </div>
    </ErrorBoundary>
  );
}
