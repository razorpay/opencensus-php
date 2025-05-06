import React from 'react';
import { Box, Button, TextInput, Text, Divider } from '@razorpay/blade/components';

import { FormikHandleChange, UseFormikReturnType } from 'common/typings';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import KYCAccessCheckbox from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/KYCAccessCheckbox';
import { trackEmailFlowCTAClicked } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { PARTNER_TYPE, PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { ShowWhen } from 'merchant_common/components/RouteGuard';

type SingleInviteFormProps = {
  formik: UseFormikReturnType;
  handleChange: FormikHandleChange;
  isSendingInvite: boolean;
  hasSelectedKycAccess: boolean | null;
  onBackClick: () => void;
  onNextClick: () => void;
  onSendInviteClick: () => void;
  productType: string;
  inviteFlow: string;
};
const SingleInviteForm = ({
  productType,
  inviteFlow,
  hasSelectedKycAccess,
  formik,
  handleChange,
  onBackClick,
  onNextClick,
  isSendingInvite,
  onSendInviteClick,
}: SingleInviteFormProps): JSX.Element => {
  const handleKycAccessChange = ({ isChecked }) => {
    trackEmailFlowCTAClicked({
      ctaClicked: 'request_kyc_access',
      message: isChecked ? 'yes' : 'no',
      productType,
    });
    handleChange({ name: 'request_kyc_access', value: isChecked });
  };

  return (
    <Box display="flex" flexDirection="column" gap="spacing.6" flex="1">
      <Box display="flex" flexDirection="column" gap="spacing.5" minHeight="340px">
        <TextInput
          name="name"
          label="Client Name"
          necessityIndicator="required"
          placeholder="Enter the full business name"
          errorText={String(formik.errors['name'])}
          validationState={formik.errors['name'] ? 'error' : 'none'}
          onChange={handleChange}
        />
        <TextInput
          name="email"
          label="Email ID"
          necessityIndicator="required"
          placeholder="client@email.com"
          helpText="The account access link will be sent to this email ID"
          errorText={String(formik.errors['email'])}
          validationState={formik.errors['email'] ? 'error' : 'none'}
          onChange={handleChange}
          isRequired
        />
        <TextInput
          name="contact_no"
          label="Contact number (optional)"
          // TODO v2: handle curlec org condition here
          prefix="+91"
          type="number"
          errorText={String(formik.errors['contact_no'])}
          validationState={formik.errors['contact_no'] ? 'error' : 'none'}
          onChange={handleChange}
          isRequired
        />
        <ShowWhen additionalCondition={(user) => user.isPartner(PARTNER_TYPE.AGGREGATOR)}>
          <Divider />
          <Box display="flex" gap="spacing.3" alignItems="center" marginTop="spacing.5">
            <Text size="small" color="surface.text.gray.muted">
              You will get access to perform actions on your client's behalf once they sign-up and
              approve account access request.
            </Text>
          </Box>
        </ShowWhen>
        {/* FTUX checkbox */}
        <ShowWhen
          additionalCondition={(user) =>
            !user.isPartner(PARTNER_TYPE.AGGREGATOR) && hasSelectedKycAccess !== null
          }
        >
          <Box marginTop="spacing.6" marginBottom="spacing.2">
            <KYCAccessCheckbox
              inviteFlow={inviteFlow}
              productType={productType}
              isChecked={formik.values['request_kyc_access']}
              onChange={handleKycAccessChange}
            />
          </Box>
        </ShowWhen>
      </Box>
      <ModalFooter>
        {productType !== PRODUCT_TYPE.PG ? (
          <Button variant="tertiary" onClick={onBackClick}>
            Back
          </Button>
        ) : null}
        {hasSelectedKycAccess === null ? (
          <Button variant="primary" onClick={onNextClick}>
            Next
          </Button>
        ) : (
          <Button variant="primary" isLoading={isSendingInvite} onClick={onSendInviteClick}>
            Send Invite
          </Button>
        )}
      </ModalFooter>
    </Box>
  );
};

export default SingleInviteForm;
