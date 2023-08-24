import React from 'react';
import { Box, Button } from '@razorpay/blade/components';

import { FormikHandleChange, UseFormikReturnType } from 'common/typings';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import KYCAccessFormContent from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/KYCAccessFormContent';

type KYCAccessFormProps = {
  formik: UseFormikReturnType;
  handleChange: FormikHandleChange;
  isSendingInvites: boolean;
  onSendInvitesClick: () => void;
  productType: string;
};
const KYCAccessForm = ({
  formik,
  handleChange,
  isSendingInvites,
  productType,
  onSendInvitesClick,
}: KYCAccessFormProps): JSX.Element => {
  const inviteFlow = INVITE_TAB_TYPES.BULK_UPLOAD;

  const handleKycAccessChange = ({ value }) => {
    handleChange({ name: 'request_kyc_access', value: value === 'true' });
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
        <Button variant="primary" isLoading={isSendingInvites} onClick={onSendInvitesClick}>
          Send Invites
        </Button>
      </ModalFooter>
    </>
  );
};

export default KYCAccessForm;
