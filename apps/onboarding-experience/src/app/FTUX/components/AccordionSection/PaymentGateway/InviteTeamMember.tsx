import React, { lazy, Suspense, useState } from 'react';
import { Box, Text, Link, PlayCircleIcon, UserPlusIcon, Spinner } from '@razorpay/blade/components';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { INVITE_TEAM_MEMBER_VIDEO_URL } from '@FTUX/constants/accordion';

const InviteNewMemberModal = lazy(
  () =>
    import(
      /* webpackChunkName: "InviteNewMemberModal" */ '@federated/dashboards/payments/components/NewMemberInvitationModal'
    ),
);

const InviteTeamMember = () => {
  const { initiateTwoFaAuth } = useMerchantContext();

  const [isInvitationModalOpen, setIsInvitationModalOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  // This will be be client side check to handle toggling CTA text when member is invited
  const [isAnyMemberInvited, setIsAnyMemberInvited] = useState(false);

  const handleInviteTeamMember = async () => {
    setIsLoading(true);
    const isVerified = await initiateTwoFaAuth?.();
    if (!isVerified) {
      setIsLoading(false);
      return;
    }

    setIsInvitationModalOpen(true);
  };

  const handleCloseInvitationModal = () => {
    setIsInvitationModalOpen(false);
    setIsLoading(false);
  };

  return (
    <Box
      backgroundColor="surface.background.gray.subtle"
      padding="spacing.4"
      display="flex"
      flexDirection={{ base: 'column', l: 'row' }}
      gap="spacing.4"
      borderRadius="medium"
      alignItems={{ l: 'center' }}
    >
      <Text size="small" color="surface.text.gray.subtle">
        This is a technical step and you may need some help to integrate keys.
      </Text>
      <Link
        size="small"
        color="neutral"
        icon={PlayCircleIcon}
        href={INVITE_TEAM_MEMBER_VIDEO_URL}
        rel="noreferrer noopener"
        target="_blank"
        marginLeft={{ base: 'none', l: 'auto' }}
      >
        Watch a set up video
      </Link>
      {isLoading ? (
        <Box display="flex" alignItems="center" justifyContent="center" width="200px">
          <Spinner
            size="medium"
            color="primary"
            accessibilityLabel="Loading Invite Member Journey"
          />
        </Box>
      ) : (
        <Link
          size="small"
          icon={UserPlusIcon}
          variant="button"
          onClick={() => handleInviteTeamMember()}
          isDisabled={isLoading}
        >
          {isAnyMemberInvited ? 'Invite more team members' : 'Invite your developer as an admin'}
        </Link>
      )}
      {isInvitationModalOpen && (
        <Suspense fallback={<></>}>
          <InviteNewMemberModal
            isOpen={true}
            onSuccess={() => {
              setIsAnyMemberInvited(true);
              handleCloseInvitationModal();
            }}
            defaultRole="admin"
            customRolesData={{
              admin: {
                label: 'Admin',
                desc: 'Can do everything except for Team Management.',
              },
            }}
            onDismiss={handleCloseInvitationModal}
          />
        </Suspense>
      )}
    </Box>
  );
};

export default InviteTeamMember;
