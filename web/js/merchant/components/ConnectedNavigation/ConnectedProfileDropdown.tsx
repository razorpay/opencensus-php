import React, { useLayoutEffect, useState } from 'react';
import {
  Avatar,
  Box,
  Button,
  Menu,
  MenuHeader,
  MenuOverlay,
  TrustedBadgeIcon,
  Text,
  Tooltip,
  MenuItem,
  ActionListItemAsset,
  HeadphonesIcon,
  LogOutIcon,
  useTheme,
  BottomSheet,
  BottomSheetBody,
  BottomSheetHeader,
  ChevronRightIcon,
  ConfettiIcon,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import LiveModeIcon from 'assets/rtux/live-mode.svg';
import TestModeIcon from 'assets/rtux/test-mode.svg';
import { Link } from 'react-router-dom';

import { withI18Service } from 'common/i18';
import copyToClipboard from 'common/utils/copyToClipboard';
import { titleCase } from 'common/utils/rzp-utils';
import ExplorePartnerProgramCard from 'merchant/components/ExplorePartnerProgramCard';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { isOrgFeatureExist } from 'merchant/models/User';
import { getRole } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/profile';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import PaymentHandleSlug from 'merchant/views/PaymentHandle/components/DropDownSlug';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

import { Mode } from './types';
import { getCurrentMode } from './utils';
import SwitchMerchantTypeaheadV2 from '../HeaderNav/SwitchMerchantTypeaheadV2';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';

import ShowWhen from '../ShowWhen';
import { CopyWrapper } from '@libs/shared-ui';
import { isExperimentEnabled, trackProfileDropdownClicks } from '@libs/shared-utils';
import { loadPlotlineScript } from '@dashboards/payments/utils/plotlineLoadScript';
import { useSplitzService } from '@libs/web-nexus/common/splitz';

interface ConnectedProfileDropdownProps {
  user: any;
  isRTBEnabled: boolean;
  trustedBadgeTooltipInfo: string;
  i18: any;
  mode: Mode;
  partnerMode: Mode;
  onSwitchMode: (mode: Mode) => void;
  onLogout: () => void;
  showPartnerIntent: () => void;
  openSwitchMerchantModal: () => void;
  showSwitchMerchantModal: boolean;
  onSwitchMerchantDismiss: () => void;
  onSwitchMerchant: (merchantId: string) => void;
}

const PLOTLINE_SDK_FRONTEND_PUBLIC_KEY = process.env['PLOTLINE_SDK_FRONTEND_PUBLIC_KEY'];

function ConnectedProfileDropdown({
  user,
  isRTBEnabled,
  trustedBadgeTooltipInfo,
  i18,
  mode,
  partnerMode,
  onSwitchMode,
  onLogout,
  showPartnerIntent,
  openSwitchMerchantModal,
  showSwitchMerchantModal,
  onSwitchMerchantDismiss,
  onSwitchMerchant,
}: ConnectedProfileDropdownProps): JSX.Element {
  const { products } = useConnectedNavigationStore();
  const splitz = useSplitzService();
  const [shouldShowMobileBottomSheet, setShouldShowMobileBottomSheet] = useState(false);
  const { user: loggedInUser, name, id: merchantId } = user;
  const loggedInUserName = loggedInUser?.name || '';
  const { isConfigTagEnabled } = i18;
  const userRole = getRole({ user });
  const currentMode = getCurrentMode({
    mode,
    partnerMode,
    selectedProduct: products.selectedProduct?.alias,
  });
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';
  const showSwitchMerchant = user.merchants && Object.keys(user.merchants).length > 1;
  const invertMode = currentMode === 'live' ? 'test' : 'live';
  const isPlotlineExperimentActive =
    isExperimentEnabled(splitz?.abExperiments?.['plotline_milestone_widget']) ||
    isExperimentEnabled(splitz?.abExperiments?.['plotline_milestone_whitelisted_mids']);

  // Loading the plotline script on layout effect to ensure it is loaded before the widget is rendered
  useLayoutEffect(() => {
    if (isPlotlineExperimentActive) loadPlotlineScript();
  }, []);

  const handleSwitchMerchant = () => {
    trackProfileDropdownClicks({
      objectName: 'Profile Options',
      optionName: 'Switch Merchant',
      bu_title: products.selectedProduct?.title as string,
      type: 'option',
    });
    if (isMobile) {
      setShouldShowMobileBottomSheet(false);
    }
    openSwitchMerchantModal();
  };

  const handleMIDCopyClick = () => {
    //TOOD: Add feedback that copy is successful
    trackProfileDropdownClicks({
      objectName: 'Profile Options',
      optionName: 'Copy MID',
      bu_title: products.selectedProduct?.title as string,
      type: 'option',
    });
    copyToClipboard(merchantId);
  };
  const handleModeSwitch = () => {
    trackProfileDropdownClicks({
      objectName: 'Profile Options',
      optionName: `Enable ${titleCase(invertMode)} Mode`,
      bu_title: products.selectedProduct?.title as string,
      type: 'option',
    });
    onSwitchMode(invertMode);
  };

  const handleHelpSupport = () => {
    trackProfileDropdownClicks({
      objectName: 'Profile Options',
      optionName: 'Help & Support',
      bu_title: products.selectedProduct?.title as string,
      type: 'option',
    });
    CreateTicketEmitter.emit('toggle-help-section');
  };

  const handleLogout = () => {
    trackProfileDropdownClicks({
      objectName: 'Profile Options',
      optionName: 'Log out',
      bu_title: products.selectedProduct?.title as string,
      type: 'option',
    });
    onLogout();
  };

  const getProfileDropdownItems = () => {
    return (
      <>
        <Link to={ROUTES_INFO.ACCOUNT_AND_SETTINGS}>
          <MenuItem
            onClick={() => {
              if (isMobile) {
                setShouldShowMobileBottomSheet(false);
              }
            }}
          >
            <Box
              paddingX="spacing.2"
              display="flex"
              alignItems="center"
              justifyContent="space-between"
              width="100%"
            >
              <Box display="flex" gap="spacing.4" alignItems="center">
                <Avatar
                  size="large"
                  name={loggedInUserName || name}
                  {...(isRTBEnabled ? { bottomAddon: TrustedBadgeIcon } : {})}
                />
                <Box display="flex" gap="spacing.2" flexDirection="column">
                  <Text
                    weight="semibold"
                    variant="body"
                    size="large"
                    color="surface.text.gray.normal"
                  >
                    {loggedInUserName ? titleCase(loggedInUserName) : '--'}
                  </Text>
                  <Text
                    color="surface.text.gray.muted"
                    variant="body"
                    size="medium"
                    weight="regular"
                  >
                    {userRole}
                  </Text>
                </Box>
              </Box>
              <Box>
                <ChevronRightIcon size="medium" />
              </Box>
            </Box>
          </MenuItem>
        </Link>
        <Box
          marginX={isMobile ? 'spacing.0' : 'spacing.4'}
          paddingX="spacing.3"
          paddingY="spacing.4"
          borderRadius="medium"
          borderColor="surface.border.gray.muted"
          borderWidth="thinner"
          marginTop={isMobile ? 'spacing.4' : 'spacing.2'}
        >
          <Text variant="body" size="medium" weight="semibold" color="surface.text.gray.subtle">
            {name}
          </Text>
          {isRTBEnabled && (
            <Box display="flex" alignItems="center" marginBottom="spacing.3">
              <Tooltip content={trustedBadgeTooltipInfo} placement="bottom">
                <Link to={ROUTES_INFO.TRUSTED_BADGE}>
                  <TrustedBadgeIcon size="small" />
                </Link>
              </Tooltip>
              <Box marginLeft="spacing.2">
                <Text
                  variant="caption"
                  size="small"
                  weight="regular"
                  color="surface.text.gray.muted"
                >
                  Razorpay Trusted Business
                </Text>
              </Box>
            </Box>
          )}
          <CopyWrapper onClick={handleMIDCopyClick}>
            <Text variant="body" size="small" weight="regular" color="surface.text.gray.subtle">
              MID: {merchantId}
            </Text>
          </CopyWrapper>
          {showSwitchMerchant && (
            <Button
              variant="tertiary"
              color="primary"
              size="xsmall"
              isFullWidth
              marginTop="spacing.3"
              onClick={handleSwitchMerchant}
            >
              Switch Merchant
            </Button>
          )}
        </Box>
        {user.isPaymentHandleSplitzEnabled && !isConfigTagEnabled('profile.razorpay_me') && (
          <PaymentHandleSlug isConnectedNavigation={true} />
        )}
        <Box marginTop="spacing.3" paddingX={isMobile ? 'spacing.0' : 'spacing.3'}>
          {/* TODO: Replace with bigger icons post initial release */}
          {/* TODO: Make changes for this to reflect the current selected product mode post initial release */}
          <Box marginY={isMobile ? 'spacing.3' : 'spacing.1'}>
            <MenuItem
              leading={
                <ActionListItemAsset
                  alt={`${invertMode}-mode-icon`}
                  src={currentMode === 'live' ? TestModeIcon : LiveModeIcon}
                />
              }
              title={`Enable ${titleCase(invertMode)} Mode`}
              onClick={handleModeSwitch}
            />
          </Box>

          {isPlotlineExperimentActive && (
            <Box marginY={isMobile ? 'spacing.3' : 'spacing.1'}>
              <MenuItem
                leading={<ConfettiIcon />}
                title="My Rewards"
                onClick={() => {
                  if (typeof window.plotline === 'function') {
                    window.plotline('init', PLOTLINE_SDK_FRONTEND_PUBLIC_KEY, merchantId);
                    window.plotline('track', 'LAUNCH_REWARDS_PAGE');
                  }
                }}
              />
            </Box>
          )}

          <Box marginY={isMobile ? 'spacing.3' : 'spacing.1'}>
            <Link to={ROUTES_INFO.SUPPORT_TICKETS_MERCHANT}>
              <MenuItem leading={<HeadphonesIcon />} title="View Support Tickets" />
            </Link>
          </Box>
          <Box marginY={isMobile ? 'spacing.3' : 'spacing.1'}>
            <MenuItem
              leading={<LogOutIcon color="interactive.icon.negative.normal" />}
              title="Log out"
              color="negative"
              onClick={handleLogout}
            />
          </Box>
        </Box>
        <ShowWhen
          additionalCondition={(user) =>
            user.role === rolesList.OWNER &&
            user.partner_type === null &&
            !isOrgFeatureExist('hide_razorpay_text_link')
          }
        >
          <Box margin={isMobile ? 'spacing.0' : 'spacing.4'}>
            <ExplorePartnerProgramCard onClick={showPartnerIntent} />
          </Box>
        </ShowWhen>
      </>
    );
  };

  if (isMobile) {
    return (
      <>
        <Avatar
          size="medium"
          name={loggedInUserName || name}
          variant="square"
          {...(isRTBEnabled ? { bottomAddon: TrustedBadgeIcon } : {})}
          onClick={() => {
            trackProfileDropdownClicks({
              objectName: 'L0 Main Frame Icons',
              optionName: 'Profile Icon/Button',
              bu_title: products.selectedProduct?.title as string,
              type: 'icon',
            });
            setShouldShowMobileBottomSheet(true);
          }}
        />
        <BottomSheet
          isOpen={shouldShowMobileBottomSheet}
          onDismiss={() => {
            setShouldShowMobileBottomSheet(false);
          }}
          snapPoints={[0.5, 0.7, 0.85]}
        >
          <BottomSheetHeader title="" subtitle="" />
          <BottomSheetBody>{getProfileDropdownItems()}</BottomSheetBody>
        </BottomSheet>
        {showSwitchMerchantModal ? (
          <SwitchMerchantTypeaheadV2
            isOpen={showSwitchMerchantModal}
            onDismiss={onSwitchMerchantDismiss}
            onSwitchMerchant={onSwitchMerchant}
          />
        ) : null}
      </>
    );
  }

  return (
    <>
      <Menu
        onOpenChange={({ isOpen }) => {
          if (isOpen) {
            trackProfileDropdownClicks({
              objectName: 'L0 Main Frame Icons',
              optionName: 'Profile Icon/Button',
              bu_title: products.selectedProduct?.title as string,
              type: 'icon',
            });
          }
        }}
      >
        <Avatar
          size="medium"
          name={loggedInUserName || name}
          variant="square"
          {...(isRTBEnabled ? { bottomAddon: TrustedBadgeIcon } : {})}
        />
        <MenuOverlay minWidth="300px" maxWidth="400px">
          {getProfileDropdownItems()}
        </MenuOverlay>
      </Menu>
      {showSwitchMerchantModal ? (
        <SwitchMerchantTypeaheadV2
          isOpen={showSwitchMerchantModal}
          onDismiss={onSwitchMerchantDismiss}
          onSwitchMerchant={onSwitchMerchant}
        />
      ) : null}
    </>
  );
}

export default withI18Service(ConnectedProfileDropdown);
