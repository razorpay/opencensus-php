import React from 'react';
import { Box, Button } from '@razorpay/blade/components';

import { FormikHandleChange, UseFormikReturnType } from 'common/typings';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import KYCAccessFormContent from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/KYCAccessFormContent';
import { trackEmailFlowCTAClicked } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';

type KYCAccessFormProps = {
  formik: UseFormikReturnType;
  handleChange: FormikHandleChange;
  isSendingInvite: boolean;
  onSendInviteClick: () => void;
  productType: string;
};
const KYCAccessForm = ({
  formik,
  handleChange,
  isSendingInvite,
  onSendInviteClick,
  productType,
}: KYCAccessFormProps): JSX.Element => {
  const inviteFlow = INVITE_TAB_TYPES.SINGLE_INVITE;

  const handleKycAccessChange = ({ value }) => {
    const isChecked = value === 'true';
    trackEmailFlowCTAClicked({
      ctaClicked: 'request_kyc_access',
      message: isChecked ? 'yes' : 'no',
      productType,
    });
    handleChange({ name: 'request_kyc_access', value: isChecked });
  };
  return (
    <>
      <Box minHeight="380px">
        <KYCAccessFormContent
          inviteFlow={inviteFlow}
          productType={productType}
          onChange={handleKycAccessChange}
          value={String(formik.values.request_kyc_access)}
          errorText={String(formik.errors.request_kyc_access)}
          validationState={formik.errors.request_kyc_access ? 'error' : 'none'}
        />
      </Box>
      <ModalFooter>
        <Button variant="primary" isLoading={isSendingInvite} onClick={onSendInviteClick}>
          Send Invite
        </Button>
      </ModalFooter>
    </>
  );
};

export default KYCAccessForm;
