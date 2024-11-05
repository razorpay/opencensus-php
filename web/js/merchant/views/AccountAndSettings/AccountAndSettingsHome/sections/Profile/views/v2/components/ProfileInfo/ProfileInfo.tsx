import React from 'react';
import { Box, CheckIcon, CopyIcon, IconButton, Text, Tooltip } from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';
import useClipboard from 'merchant/hooks/useClipboard';
import { getInitials } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/utils';
import { User } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

import { ProfilePhotoContainer, StyledInitialsImage } from './styled';

interface ProfileInfoInterface {
  user: User;
  userRole: string;
}

const ProfileInfo = ({ user, userRole }: ProfileInfoInterface): JSX.Element => {
  const { id: merchantId, logo_url: imageUrl, user: loggedInUser } = user;
  const { name: loggedInUserName } = loggedInUser;
  const userNameInitials = getInitials(loggedInUserName);

  const { copy, isCopied } = useClipboard(2000);

  return (
    <Box display="flex" gap={{ base: 'spacing.6', m: 'spacing.7' }} alignItems="center">
      <ProfilePhotoContainer>
        {imageUrl ? (
          <img title="profile-pic" src={imageUrl} alt="user-profile-pic" />
        ) : userNameInitials ? (
          <StyledInitialsImage>{userNameInitials}</StyledInitialsImage>
        ) : (
          <i className="i i-profile" />
        )}
      </ProfilePhotoContainer>
      <Box
        display="flex"
        flexDirection="column"
        gap={{ base: 'spacing.4', m: 'spacing.5' }}
        flex="1"
      >
        <Box display="flex" flexDirection="column">
          <Text weight="semibold" size="large">
            {loggedInUserName ? titleCase(loggedInUserName) : '--'}
          </Text>
          {userRole && (
            <Text size="medium" color="surface.text.gray.muted">
              {userRole}
            </Text>
          )}
        </Box>
        <Box display="flex" flexDirection="column">
          <Text size="medium" color="surface.text.gray.muted">
            Merchant ID
          </Text>
          <Box display="flex" gap="spacing.3" alignItems="end">
            <Text size="medium" weight="semibold">
              {merchantId}
            </Text>
            <Tooltip content={isCopied ? 'Copied' : 'Click to copy'}>
              <IconButton
                accessibilityLabel="Copy MID to Clipboard"
                icon={isCopied ? CheckIcon : CopyIcon}
                onClick={() => copy(merchantId)}
              />
            </Tooltip>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

export default ProfileInfo;
