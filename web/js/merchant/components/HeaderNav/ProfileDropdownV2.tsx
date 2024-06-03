import React from 'react';
import {
  ActionList,
  ActionListItem,
  ActionListItemAsset,
  ActionListItemIcon,
  Box,
  Divider,
  Dropdown,
  DropdownLink,
  DropdownOverlay,
  LogOutIcon,
  RazorpayXIcon,
  Text,
  Theme,
  Tooltip,
  UserIcon,
  HeadphonesIcon,
  Link as BladeLink,
} from '@razorpay/blade/components';
import { Link } from 'react-router-dom';
import styled from 'styled-components';
import ShowWhen from 'merchant/components/ShowWhen';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { isOrgFeatureExist } from 'merchant/models/User';

import { User } from 'common/typings';
import copyToClipboard from 'common/utils/copyToClipboard';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import { getInitials } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/utils';
import { getRole } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/profile';
import { CopyWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

import LiveModeIcon from 'assets/rtux/live-mode.svg';
import TestModeIcon from 'assets/rtux/test-mode.svg';
import SwitchMerchantIcon from 'assets/rtux/switch-merchant.svg';
import { analyticsTrack } from 'common/utils/analytics';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

const TrustedBadgeIcon = styled.div(
  ({ theme }: { theme: Theme }) => `
  background: url('https://cdn.razorpay.com/static/assets/trustedbadge/rtb-live.svg') no-repeat
    center;
  height: ${theme.spacing[5]}px;
  width: ${theme.spacing[5]}px;
  display: inline-block;
  background-size: contain;
  margin-left: ${theme.spacing[2]}px;
`,
);

const ImageContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  height: 50px;
  width: 50px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 50%;
  background-color: ${theme.colors.interactive.border.gray.faded};
`,
);

const ProfileDropdownV2: React.FC<{
  user: User;
  mode: 'test' | 'live';
  onSwitchMode: (mode: string) => void;
  onLogout: () => void;
  openSwitchMerchantModal: () => void;
  isRTBEnabled: boolean;
  trustedBadgeTooltipInfo: string;
  showPartnerIntent: () => void;
}> = ({
  user,
  mode,
  onSwitchMode,
  onLogout,
  openSwitchMerchantModal,
  isRTBEnabled,
  trustedBadgeTooltipInfo,
  showPartnerIntent,
}) => {
  const {
    id: merchantId,
    logo_url: imageUrl,
    user: loggedInUser,
    name,
    isRazorxAnnouncementEnabled,
  } = user;
  const { name: loggedInUserName } = loggedInUser!;
  const userRole = getRole({ user });

  const userNameInitials = getInitials(loggedInUserName);

  const invertMode = mode === 'live' ? 'test' : 'live';

  const commonAnalyticsProperties = {
    version: 'v2',
    ...getCommonAnalyticsProperties(window.rzp_user, {
      addUserProperties: true,
    }),
  };

  const track = (objectName: string) => {
    analyticsTrack({
      objectName,
      actionName: 'clicked',
      screen: 'home page',
      properties: commonAnalyticsProperties,
    });
  };

  const handleModeSwitch = () => {
    onSwitchMode(invertMode);
    track('Account Dropdown Switch Mode');
  };

  const handleLogout = () => {
    onLogout();
    track('Account Dropdown Logout');
  };

  const handleMIDClick = () => {
    copyToClipboard(merchantId);
    track('Account Dropdown Copy MID');
  };

  const handleSwitchMerchantClick = () => {
    openSwitchMerchantModal();
    track('Account Dropdown Switch Merchant');
  };

  const handleHelpSupport = () => {
    CreateTicketEmitter.emit('toggle-help-section');
  };

  return (
    <Dropdown>
      <DropdownLink
        margin="spacing.5"
        icon={() => <UserIcon size="medium" color="interactive.icon.gray.subtle" />}
        onClick={() => {
          analyticsTrack({
            objectName: 'Account Dropdown',
            actionName: 'clicked',
            screen: 'home page',
            properties: commonAnalyticsProperties,
          });
        }}
      />
      <DropdownOverlay>
        <Box minWidth="300px">
          <Box display="flex" gap="spacing.3" alignItems="center" padding="spacing.5">
            <ImageContainer>
              {imageUrl ? (
                <img
                  title="profile-pic"
                  src={imageUrl}
                  alt="user-profile-pic"
                  height="50px"
                  width="50px"
                  style={{
                    borderRadius: '50%',
                  }}
                />
              ) : userNameInitials ? (
                <Text>{userNameInitials}</Text>
              ) : (
                <UserIcon size="large" color="interactive.icon.gray.subtle" />
              )}
            </ImageContainer>
            <Box display="flex" flexDirection="column">
              <Text weight="semibold" size="large">
                {loggedInUserName ? titleCase(loggedInUserName) : '--'}
              </Text>
              <Text>{userRole}</Text>
            </Box>
          </Box>
          <Divider />
        </Box>
        <Box padding="spacing.5">
          <Box display="flex" flexDirection="row" alignItems="center">
            <Text weight="semibold" display="block">
              {name}
            </Text>
            {isRTBEnabled && (
              <Tooltip content={trustedBadgeTooltipInfo} placement="bottom">
                <Link to={ROUTES_INFO.TRUSTED_BADGE}>
                  <TrustedBadgeIcon data-testid="trusted-badge" />
                </Link>
              </Tooltip>
            )}
          </Box>
          <CopyWrapper onClick={handleMIDClick}>
            <Text weight="semibold">MID:</Text>
            <Text>{merchantId}</Text>
          </CopyWrapper>
        </Box>
        <Divider />
        <ActionList>
          {user.merchants && Object.keys(user.merchants).length > 1 && (
            <ActionListItem
              leading={<ActionListItemAsset alt="switch-merchant-icon" src={SwitchMerchantIcon} />}
              title="Switch Merchant"
              value="Switch Merchant"
              onClick={handleSwitchMerchantClick}
            />
          )}
          <ActionListItem
            leading={
              <ActionListItemAsset
                alt="test-mode-icon"
                src={mode === 'live' ? TestModeIcon : LiveModeIcon}
              />
            }
            title={`Enable ${titleCase(invertMode)} Mode`}
            value={`Enable ${titleCase(invertMode)} Mode`}
            onClick={handleModeSwitch}
          />
          {isRazorxAnnouncementEnabled && (
            <ActionListItem
              leading={<ActionListItemIcon icon={RazorpayXIcon} />}
              title="Go to RazorpayX"
              value="Go to RazorpayX"
              href="https://x.razorpay.com"
            />
          )}
          <ActionListItem
            leading={<ActionListItemIcon icon={HeadphonesIcon} />}
            title="Help & Support"
            value="Help & Support"
            onClick={handleHelpSupport}
          />
          <ActionListItem
            leading={<ActionListItemIcon icon={LogOutIcon} />}
            title="Log out"
            value="Log out"
            onClick={handleLogout}
          />
        </ActionList>
        <ShowWhen
          additionalCondition={(user) =>
            user.role === rolesList.OWNER &&
            user.partner_type === null &&
            !isOrgFeatureExist('hide_razorpay_text_link')
          }
        >
          <Divider />
          <Box display="flex" flexDirection="column" padding="spacing.5" gap="spacing.2">
            Partner with us and start earning on every referral
            <BladeLink variant="button" onClick={showPartnerIntent}>
              Explore Partner Program
            </BladeLink>
          </Box>
        </ShowWhen>
      </DropdownOverlay>
    </Dropdown>
  );
};

export default ProfileDropdownV2;
