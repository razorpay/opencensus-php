import React from 'react';
import { connect } from 'react-redux';
import { Box } from '@razorpay/blade/components';

import rolesList from 'merchant/helpers/permissions/roles-list';
import { sendInvitation } from 'merchant/reducers/invitation';

import NewInvitation from './NewInvitationTyped';
import { useModalComponents } from '@libs/shared-ui';
import { isMobileDevice } from '@libs/shared-utils';

// This component is created just as an wrapper to NewInvitation
// such that we can use this component standalone and also expose this to other microapps

const NewInvitationWrapper = ({
  userName,
  onSuccess,
  onDismiss,
  isRenderedFromPartnerRoute,
  sendInvitation,
  defaultRole,
  customRolesData,
}: {
  userName?: string;
  customRolesData?: Record<
    string,
    {
      label: string;
      desc?: string;
    }
  >;
  defaultRole: string;
  onSuccess: () => void;
  onDismiss: () => void;
  isRenderedFromPartnerRoute: boolean;
  sendInvitation: (args) => void;
}) => {
  const isMobile = isMobileDevice();
  const { Modal, ModalHeader, ModalBody } = useModalComponents(isMobile);

  const visibleFields = {
    email: true,
    role: true,
  };

  const defaults = {
    sender_name: userName,
    role: isRenderedFromPartnerRoute ? rolesList.PARTNER_AGENT : defaultRole || rolesList.MANAGER,
  };

  return (
    <Modal size="small" isOpen onDismiss={onDismiss} snapPoints={[1, 1, 1]}>
      <ModalHeader title="Invite New Member" />
      <ModalBody>
        <Box minHeight="250px">
          <NewInvitation
            isRenderedFromPartnerRoute={isRenderedFromPartnerRoute}
            isHandlingPosPartnerAgent={defaults.role === rolesList.PARTNER_AGENT}
            visibleFields={visibleFields}
            defaults={defaults}
            onSuccess={onSuccess}
            onFormSubmit={sendInvitation}
            successMsg={(data) => `Invitation has been successfully sent to ${data.email}`}
            ctaText="Send Invitation"
            customRolesData={customRolesData}
          />
        </Box>
      </ModalBody>
    </Modal>
  );
};

const mapStateToProps = (state) => ({
  userName: state.session.user?.user?.name,
});

export default connect(mapStateToProps, { sendInvitation })(NewInvitationWrapper);
