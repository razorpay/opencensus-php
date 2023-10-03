import React from 'react';
import { Box, Button, TextInput } from '@razorpay/blade/components';

import { FormikHandleChange, UseFormikReturnType } from 'common/typings';
import { OAuthAppDetailsType } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import ApplicationDetails from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/ApplicationDetails';

type OAuthInviteFormProps = {
  formik: UseFormikReturnType;
  handleChange: FormikHandleChange;
  isSendingInvite: boolean;
  onChangeAppClick: () => void;
  selectedApp: OAuthAppDetailsType;
  onSendInviteClick: () => void;
};
const SingleOAuthInviteForm = ({
  formik,
  handleChange,
  onChangeAppClick,
  isSendingInvite,
  onSendInviteClick,
  selectedApp,
}: OAuthInviteFormProps): JSX.Element => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.6" flex="1">
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
          name="contact_no"
          label="Contact number (optional)"
          // TODO v2: handle curlec org condition here
          prefix="+91"
          type="number"
          errorText={String(formik.errors.contact_no)}
          validationState={formik.errors.contact_no ? 'error' : 'none'}
          onChange={handleChange}
        />
        {selectedApp?.name ? (
          <ApplicationDetails
            name={selectedApp.name}
            id={selectedApp.application_id}
            handleChange={onChangeAppClick}
          />
        ) : null}
      </Box>
      <ModalFooter>
        <Button variant="primary" isLoading={isSendingInvite} onClick={onSendInviteClick}>
          Send Invite
        </Button>
      </ModalFooter>
    </Box>
  );
};

export default SingleOAuthInviteForm;
