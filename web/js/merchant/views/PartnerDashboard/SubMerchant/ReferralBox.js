import React, { useState } from 'react';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/SocialShareGroup';
import ModalHeader from 'common/ui/ModalHeader';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import SelectBox from 'merchant/views/PartnerDashboard/SubMerchant/components/SelectBox';


export default function ReferralBox({ closeModal, referralData, tracking, partnerID, partnershipForXEnabled }) {
  const [productType, setProductType] = useState('');

  if (!partnershipForXEnabled) {
    return (
      <div className='referral-box-modal'>
        <div style={{ padding: '10px' }}>
          <img src="/dist/css/assets/onboarding/share-referral-link.png" height="50" />{' '}
          <strong>
            <strong>Share Referral Link</strong>
          </strong>
          <button type="button" class="close" onClick={closeModal} style={{ marginTop: '10px' }}>
            <i class="i i-close" />
          </button>
        </div>
        <div style={{ padding: '14px' }}>
          <p>
            You <strong>get 0.1% commission for every transaction</strong> done by your affiliate
            accounts who signs up with this link.
          </p>
          <SocialShareGroup
            referralUrl={referralData[PRODUCT_TYPE.PG].url}
            tracking={tracking}
            product={PRODUCT_TYPE.PG}
            partnerID={partnerID}
          />
        </div>
      </div>
    )
  }
  return (
    <div class="referral-box-modal">
      <ModalHeader title={'Share Referral Link'} onCloseClick={closeModal} />
      <div className="modal-body">
        <SelectBox
          label={'Razorpay Payments'}
          description={
            'Refer merchants to Razorpay Payment gateway and other products to receive payments'
          }
          onClick={() => setProductType(PRODUCT_TYPE.PG)}
          checked={productType === PRODUCT_TYPE.PG}
        >
          {productType === PRODUCT_TYPE.PG ? (
            <SocialShareGroup
              referralUrl={referralData[productType].url}
              tracking={tracking}
              product={PRODUCT_TYPE.PG}
              partnerID={partnerID}
            />
          ) : (
            ''
          )}
        </SelectBox>
        <SelectBox
          label={'RazorpayX'}
          description={
            'Refer merchants to RazorpayX products like Current account to process payouts'
          }
          onClick={() => setProductType(PRODUCT_TYPE.X)}
          checked={productType === PRODUCT_TYPE.X}
        >
          {productType === PRODUCT_TYPE.X ? (
            <SocialShareGroup
              referralUrl={referralData[productType].url}
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
  );
}
