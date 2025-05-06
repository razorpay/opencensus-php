import React, { useState } from 'react';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';
import {
  Menu,
  Avatar,
  MenuOverlay,
  useTheme,
  TrustedBadgeIcon,
  MenuItem,
  Box,
  Text,
  Button,
  ChevronRightIcon,
  Tooltip,
  LogOutIcon,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
} from '@razorpay/blade/components';
import { Link } from 'react-router-dom';
import { useBreakpoint } from '@razorpay/blade/utils';
import {
  toTitleCase,
  copyToClipboard,
  BASE_ROUTES,
  trackProfileDropdownClicks,
} from '@libs/shared-utils';
import { CopyWrapper } from '@libs/shared-ui';
import SwitchMerchantTypeahead from './SwitchMerchantTypeahead';
import { trustedBadgeTooltipInfo } from './constants';

function ConnectedProfileDropdown({ user, isRTBEnabled, onLogout }: any) {
  const { products } = useConnectedNavigationStore();

  const [shouldShowMobileBottomSheet, setShouldShowMobileBottomSheet] = useState(false);
  const [showSwitchMerchantModal, setShowSwitchMerchantModal] = useState(false);

  const { user: loggedInUser, name, id: merchantId } = user;
  const { name: loggedInUserName } = loggedInUser;

  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';
  const showSwitchMerchant = user.merchants && Object.keys(user.merchants).length > 1;

  const handleMIDCopyClick = () => {
    trackProfileDropdownClicks({
      objectName: 'Profile Options',
      optionName: 'Copy MID',
      bu_title: products.selectedProduct?.title,
      type: 'option',
    });
    copyToClipboard(merchantId);
  };

  const handleSwitchMerchant = () => {
    trackProfileDropdownClicks({
      objectName: 'Profile Options',
      optionName: 'Switch Merchant',
      bu_title: products.selectedProduct?.title,
      type: 'option',
    });
    if (isMobile) {
      setShouldShowMobileBottomSheet(false);
    }
    setShowSwitchMerchantModal(true);
  };

  const handleLogout = () => {
    trackProfileDropdownClicks({
      objectName: 'Profile Options',
      optionName: 'Log out',
      bu_title: products.selectedProduct?.title,
      type: 'option',
    });
    onLogout();
  };
  const onSwitchMerchantDismiss = () => {
    setShowSwitchMerchantModal(false);
  };

  const onSwitchMerchant = () => {
    setShowSwitchMerchantModal(false);
  };

  const getProfileDropdownItems = () => {
    return (
      <>
        <Link to={BASE_ROUTES.accountsettings} style={{ textDecoration: 'none' }}>
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
                    {loggedInUserName ? toTitleCase(loggedInUserName) : '--'}
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
                <Link to={BASE_ROUTES.trustedbadge}>
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
        <Box marginTop="spacing.3" paddingX={isMobile ? 'spacing.0' : 'spacing.3'}>
          <Box marginY={isMobile ? 'spacing.3' : 'spacing.1'}>
            <MenuItem
              leading={<LogOutIcon color="interactive.icon.negative.normal" />}
              title="Log out"
              color="negative"
              onClick={handleLogout}
            />
          </Box>
        </Box>
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
              bu_title: products.selectedProduct?.title,
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
          <SwitchMerchantTypeahead
            isOpen={showSwitchMerchantModal}
            onDismiss={onSwitchMerchantDismiss}
            onSwitchMerchant={onSwitchMerchant}
            isMobile={isMobile}
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
              bu_title: products.selectedProduct?.title,
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
        <SwitchMerchantTypeahead
          isOpen={showSwitchMerchantModal}
          onDismiss={onSwitchMerchantDismiss}
          onSwitchMerchant={onSwitchMerchant}
          isMobile={isMobile}
        />
      ) : null}
    </>
  );
}

export default ConnectedProfileDropdown;
