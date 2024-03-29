import { Link, Text } from '@razorpay/blade/components';
import Collapsible from 'common/components/Collapsible';
import { titleCase } from 'common/utils/rzp-utils';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import MerchantDetails from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/MerchantDetails';
import ProfilePhoto from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/ProfilePhoto';
import UserInfo from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/UserInfo';
import Verification from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Verification';
import { InfoDataConfig } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/profile';
import { ProfileViewpropsInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { getInfoData } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/utils/profile';
import React, { useState } from 'react';
import {
  Details,
  MobileProfileContainer,
  MobileProfileView,
  ProfileContainer,
  ProfileContent,
  ProfileDetail,
  Subheading,
  UserProfile,
} from './styled';

const ProfileView = ({
  isMobile,
  user,
  userRole,
  handleEditClick,
  profile,
}: ProfileViewpropsInterface): JSX.Element => {
  const [isShowMore, setIsShowMore] = useState<boolean>(false);
  const { id: merchantId, logo_url: imageUrl, user: loggedInUser } = user;
  const { name: loggedInUserName } = loggedInUser;

  const infoData = getInfoData({ user, profile, dataConfig: InfoDataConfig });

  return isMobile ? (
    <MobileProfileContainer>
      <MobileProfileView isOpen={isShowMore}>
        <ProfileDetail>
          <ProfilePhoto imageUrl={imageUrl} />
          <Details>
            <Text size="large">{loggedInUserName ? titleCase(loggedInUserName) : '--'}</Text>
            {userRole && (
              <Text size="small" color="surface.text.gray.muted">
                {userRole}
              </Text>
            )}
          </Details>
        </ProfileDetail>
        <Link onClick={() => setIsShowMore((prevState) => !prevState)} variant="button">
          {isShowMore ? 'Show less' : 'Show more'}
        </Link>
      </MobileProfileView>
      <Collapsible open={isShowMore}>
        <>
          <MerchantDetails merchantId={merchantId} isMobile={isMobile} />
          <Divider noMargin />
          <Verification isMobile={isMobile} />
          <Divider noMargin />
          <UserInfo onClick={handleEditClick} isMobile={isMobile} infoData={infoData} />
        </>
      </Collapsible>
      <Divider noMargin />
    </MobileProfileContainer>
  ) : (
    <ProfileContainer>
      <Text size="large">Your profile</Text>
      <ProfileContent>
        <UserProfile>
          <ProfilePhoto imageUrl={imageUrl} />
          <Details>
            <Text size="large">{loggedInUserName ? titleCase(loggedInUserName) : '--'}</Text>
            {userRole && <Subheading>{userRole}</Subheading>}
            <MerchantDetails merchantId={merchantId} isMobile={isMobile} />
            <Divider />
            <Verification isMobile={isMobile} />
          </Details>
        </UserProfile>
        <UserInfo onClick={handleEditClick} isMobile={isMobile} infoData={infoData} />
      </ProfileContent>
    </ProfileContainer>
  );
};

export default ProfileView;
