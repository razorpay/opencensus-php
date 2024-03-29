import { Box, Text } from '@razorpay/blade/components';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import { InfoDataConfig } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/profile';
import { ProfileViewpropsInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { getInfoData } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/utils/profile';
import React from 'react';
import ProfileInfo from './components/ProfileInfo';
import UserInfo from './components/UserInfo';
import { PersonalInfoContainer, ProfileContainer } from './styled';

const ProfileView = ({
  isMobile,
  user,
  userRole,
  handleEditClick,
  profile,
}: ProfileViewpropsInterface): JSX.Element => {
  const infoData = getInfoData({ user, profile, isRevampedInfo: true, dataConfig: InfoDataConfig });

  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.4"
      padding={{
        base: ['spacing.4', 'spacing.0', 'spacing.0', 'spacing.0'],
        m: ['spacing.0', 'spacing.5', 'spacing.0', 'spacing.0'],
      }}
    >
      {isMobile ? (
        <Box display="flex" flexDirection="column" gap="spacing.6">
          <PersonalInfoContainer>
            <ProfileInfo userRole={userRole} user={user} />
          </PersonalInfoContainer>
          <UserInfo infoData={infoData} onClick={handleEditClick} isMobile={isMobile} />
          <Divider noMargin />
        </Box>
      ) : (
        <>
          <Text size="large">Your profile</Text>
          <ProfileContainer>
            <PersonalInfoContainer>
              <ProfileInfo userRole={userRole} user={user} />
            </PersonalInfoContainer>
            <UserInfo infoData={infoData} onClick={handleEditClick} isMobile={isMobile} />
          </ProfileContainer>
        </>
      )}
    </Box>
  );
};

export default ProfileView;
