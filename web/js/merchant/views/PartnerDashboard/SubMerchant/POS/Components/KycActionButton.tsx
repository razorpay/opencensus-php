// TODO remove this file and reuse ActionKYCButton from POS once pos changes are merged

import React, { useState } from 'react';
import { Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';

import { ShowNotificationType, User } from 'common/typings';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import ConditionalTooltip from 'merchant/containers/ConditionalTooltip';
import { merchantFetch } from 'merchant/utils/ajax';
import { PosSubmerchantDetailsResponseDataType } from 'merchant/views/PartnerDashboard/SubMerchant/POS/TypeDeclares';
import { getKycActionButtonState } from 'merchant/views/PartnerDashboard/SubMerchant/POS/utils';
import { checkIsEasyEnabledForSubmerchant } from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';
import { showNotification } from 'merchant_common/reducers/notifications';

interface KycActionButtonProps {
  showNotification: ShowNotificationType;
  submerchant: PosSubmerchantDetailsResponseDataType;
  user: User;
}
const KycActionButton = ({
  submerchant,
  showNotification,
  user,
}: KycActionButtonProps): JSX.Element | null => {
  const [isActionLoading, setIsActionLoading] = useState(false);
  const navigate = useNavigate();
  const isMWeb = isMobileAndTablet();
  const isSubmerchantKYCAccess = user.isFeatureEnabled('partner_sub_kyc_access');
  const submerchantId = submerchant?.id.replace('acc_', '');

  const openKYCFormUtil = async (): Promise<void> => {
    const isEasyEnabledForSubmerchant = await checkIsEasyEnabledForSubmerchant(
      submerchant,
      showNotification,
    );
    // check for splitz and redirect to easy for kyc
    if (isEasyEnabledForSubmerchant) {
      const easyOnboardingUrl = `${window.EASY_ONBOARDING_URL}/onboarding?account_id=${submerchant.id}`;
      // Note: this will maintain only one open tab.
      window.open(easyOnboardingUrl, 'submerchant_onboarding_via_easy');
    } else if (isMWeb) {
      navigate(`/partners/submerchants/onboarding/${submerchant.id}/steps`);
    } else {
      navigate(`/partners/submerchants/${submerchant.id}/activation`);
    }
  };
  const openKYCForm = () => {
    if (!isActionLoading) {
      setIsActionLoading(true);
      openKYCFormUtil().then(() => {
        setIsActionLoading(false);
      });
    }
  };
  const sendKYCRequest = () => {
    merchantFetch({
      url: 'partner/kyc_access_request',
      method: 'post',
      data: { entity_id: submerchantId },
    })
      .then(() => {
        showNotification({
          type: 'success',
          message:
            'We have sent a mail to the merchant to approve your request. You will receive an email once the request has been approved',
        });
      })
      .catch((err: { errors: Array<string> }) => {
        showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  const { buttonText, onClickAction, isHidden, isKycRejected } = getKycActionButtonState({
    submerchant,
    sendKYCRequest,
    openKYCForm,
    isSubmerchantKYCAccess,
  });

  if (isHidden) {
    return null;
  }

  return (
    <ConditionalTooltip
      showTooltip={isKycRejected}
      content="Sorry, you’ve reached the maximum number of KYC attempts."
      onOpenChange={function noRefCheck() {}}
      placement="left"
    >
      <Button
        size="small"
        onClick={onClickAction}
        isLoading={isActionLoading}
        isDisabled={isKycRejected}
      >
        {buttonText}
      </Button>
    </ConditionalTooltip>
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  { showNotification },
)(KycActionButton);
