import React from 'react';
import { Box, Button, TextInput } from '@razorpay/blade/components';

import { FormikHandleChange, UseFormikReturnType } from 'common/typings';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';

type AddMerchantFormProps = {
  formik: UseFormikReturnType;
  handleChange: FormikHandleChange;
  isSendingInvite: boolean;
  onBackClick: () => void;
  onSendInviteClick: () => void;
};
const AddMerchantForm = ({
  formik,
  handleChange,
  onBackClick,
  isSendingInvite,
  onSendInviteClick,
}: AddMerchantFormProps): JSX.Element => {
  return (
    <Box>
      <Box display="flex" flexDirection="column" gap="spacing.5" minHeight="340px">
        <TextInput
          name="name"
          label="Client Name"
          placeholder="Enter the full business name"
          errorText={String(formik.errors.name)}
          validationState={formik.errors.name ? 'error' : 'none'}
          onChange={handleChange}
        />
        <TextInput
          name="email"
          label="Email ID"
          placeholder="client@email.com"
          helpText="The account access link will be sent to this email ID"
          errorText={String(formik.errors.email)}
          validationState={formik.errors.email ? 'error' : 'none'}
          onChange={handleChange}
        />
        <TextInput
          name="contact_mobile"
          label="Contact number (optional)"
          // TODO v2: handle curlec org condition here
          prefix="+91"
          type="number"
          errorText={String(formik.errors.contact_mobile)}
          validationState={formik.errors.contact_mobile ? 'error' : 'none'}
          onChange={handleChange}
        />
      </Box>
      <ModalFooter>
        <Button variant="tertiary" onClick={onBackClick}>
          Back
        </Button>
        <Button variant="primary" isLoading={isSendingInvite} onClick={onSendInviteClick}>
          Send Invite
        </Button>
      </ModalFooter>
    </Box>
  );
};

export default AddMerchantForm;
