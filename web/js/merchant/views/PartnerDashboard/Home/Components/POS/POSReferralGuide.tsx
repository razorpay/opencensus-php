/* eslint-disable import/extensions */
import React, { useEffect } from 'react';
import { Box, Button, Text, Link, PlusIcon, Heading } from '@razorpay/blade/components';
import posBackground from 'assets/partner-dashboard/posBannerBackground.svg';
import posIllustration from 'assets/partner-dashboard/posIllustration.svg';
import { connect } from 'react-redux';
import { ActionCreator } from 'redux';

import { User } from 'common/typings';
import { OpenModalPayload, CloseModalType } from 'common/typings/Store/modal';
import ModalHeader from 'common/ui/ModalHeader';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { sendInvitation } from 'merchant/reducers/invitation';
import NewInvitation from 'merchant/views/Account/ManageTeam/components/NewInvitationTyped';
import {
  trackPOSBannerLoaded,
  trackPartnerHomepageCtaClicked,
  trackAgentInviteCtaClicked,
} from 'merchant/views/PartnerDashboard/Home/Components/POS/analytics';
import { AddMerchantSource } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import { ColoredLine } from './Styled';

interface POSReferralGuideProps {
  handleReferClient: (source: AddMerchantSource, type?: string) => void;
  openModal: ActionCreator<OpenModalPayload>;
  closeModal: CloseModalType;
  sendInvitation: (args: { email: string; role: string; sender_name: string }) => void;
  user: User;
  org: {
    business_name: string;
  };
}
const POSReferralGuide = ({
  handleReferClient,
  openModal,
  closeModal,
  sendInvitation,
  user,
  org,
}: POSReferralGuideProps): JSX.Element => {
  const orgName = org?.business_name || 'Razorpay';

  useEffect(() => {
    trackPOSBannerLoaded();
  }, []);

  const handleInviteAgent = () => {
    const visibleFields = {
      email: true,
      role: true,
    };

    const defaults = {
      sender_name: user?.user?.name,
      role: rolesList.PARTNER_AGENT,
    };
    trackPartnerHomepageCtaClicked({ ctaClicked: 'Add POS Agent' });
    trackAgentInviteCtaClicked({ screen: 'partner_dashboard_homepage' });
    openModal({
      size: 'small',
      component: (
        <>
          <ModalHeader title="Invite New Member" onCloseClick={closeModal} />
          <div className="modal-body">
            <NewInvitation
              isRenderedFromPartnerRoute
              visibleFields={visibleFields}
              defaults={defaults}
              onSuccess={closeModal}
              onFormSubmit={sendInvitation}
              successMsg={(data) => `Invitation has been successfully sent to ${data.email}`}
              ctaText="Send Invitation"
              screen="partner_dashboard_homepage"
            />
          </div>
        </>
      ),
    });
  };

  const handleReferClientClick = () => {
    trackPartnerHomepageCtaClicked({ ctaClicked: 'Refer Now' });
    handleReferClient('referral-guide-pos', PRODUCT_TYPE.POS);
  };

  return (
    <Box
      backgroundImage={`url(${posBackground})`}
      backgroundRepeat="no-repeat"
      backgroundSize="cover"
      margin={['spacing.0', 'spacing.6']}
      display="flex"
      flexWrap="wrap"
      justifyContent="space-between"
      borderRadius="medium"
      maxHeight="500px"
      padding={['spacing.3']}
      elevation="midRaised"
    >
      <Box display="flex" flex="2" padding={['spacing.2']}>
        <Box display="flex" gap="spacing.3" justifyContent="center" alignItems="center">
          <img src={posIllustration} alt="pos" height="150px" />
        </Box>
        <Box
          display="flex"
          flexDirection="column"
          justifyContent="center"
          gap="spacing.4"
          maxWidth="330px"
          margin="spacing.3"
        >
          <Box display="flex" flexDirection="column" gap="spacing.3" flexWrap="wrap">
            <Heading size="small">Refer clients to {orgName} POS!</Heading>
          </Box>
          <ColoredLine />
          <Text>Add your agents to assist clients with KYC link to manage agents flow</Text>
        </Box>
        <Box />
      </Box>
      <Box
        display="flex"
        justifyContent="space-evenly"
        alignItems="center"
        padding={['spacing.2']}
        flex="1"
      >
        <Button onClick={handleReferClientClick}>Refer Now</Button>
        <Link variant="button" icon={PlusIcon} iconPosition="left" onClick={handleInviteAgent}>
          {' '}
          Add POS Agent
        </Link>
      </Box>
    </Box>
  );
};

export default connect((state) => ({ user: state.session.user, org: state.session.org }), {
  sendInvitation,
  openModal,
  closeModal,
})(POSReferralGuide);
