import React, { useState } from 'react';
import {
  ArrowLeftIcon,
  ArrowUpRightIcon,
  Badge,
  Box,
  Heading,
  IconButton,
  Menu,
  MenuItem,
  MenuOverlay,
  MoreVerticalIcon,
  Popover,
  PopoverInteractiveWrapper,
  Text,
  Button,
  useToast,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose } from 'redux';

import copyToClipboard from 'common/utils/copyToClipboard';
import { CreateConfigModal } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CreateConfigModal';
import { SetConfigAsDefaultModal } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/SetConfigAsDefaultModal';
import { DEFAULT_PAYMENT_CONFIG } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { PreventDiscardChangesModal } from './PreventDiscardChangesModal';
import { IconButtonWrapper } from './styled';
import _track from './track';

export type HeaderProps = {
  org: { business_name: string };
  hideConfigDetails: () => void;
};

function CopyConfigIDPopOver({ configID, docLink }: { configID: string; docLink: string }) {
  const toast = useToast();

  function copyConfigIDToClipboard(configID: string) {
    toast.show({
      content: 'Configuration ID copied to clipboard',
      color: 'neutral',
      autoDismiss: true,
    });
    copyToClipboard(configID);
  }

  const Content = ({ configID, docLink }: { configID: string; docLink: string }) => (
    <Box display="flex" flexDirection="column" gap="spacing.5">
      <Text size="medium" weight="regular">
        Share the Configuration ID with your developer to integrate this setup. You can also define
        conditions for displaying this configuration to a particular set of customers by using the
        Configuration ID.
      </Text>
      <Box display="flex" justifyContent="flex-end" gap="spacing.4">
        <Button variant="tertiary" size="small" href={docLink} target="_blank">
          Learn more
        </Button>
        <Button variant="primary" size="small" onClick={() => copyConfigIDToClipboard(configID)}>
          Copy ID
        </Button>
      </Box>
    </Box>
  );

  return (
    <Popover
      content={<Content configID={configID} docLink={docLink} />}
      placement="bottom"
      title="Configuration ID"
    >
      <PopoverInteractiveWrapper>
        <Badge color="neutral">{configID}</Badge>
      </PopoverInteractiveWrapper>
    </Popover>
  );
}

function Header({ org, hideConfigDetails }: HeaderProps) {
  const [isConfigNameModalOpen, setIsConfigNameModalOpen] = useState(false);
  const [isSetDefaultConfigModalOpen, setIsSetDefaultConfigModalOpen] = useState(false);
  const [isPreventDiscardChangesModalOpen, setIsPreventDiscardChangesModalOpen] = useState(false);

  const {
    values,
    handleConfigNameChange,
    handleSetConfigAsDefault,
    handleDiscardAllChanges,
    isValueModified,
  } = useCheckoutEditor();

  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const isRazorpayConfig = selectedConfig?.config_id === DEFAULT_PAYMENT_CONFIG.config_id;
  const isNewConfig = selectedConfig?.config_id === '';

  function handleConfigNameModalSave(updatedName: string) {
    handleConfigNameChange(selectedConfig, updatedName);
    _track.configurationRenamed(updatedName);
  }

  function handleSetConfigAsDefaultModalSave() {
    handleSetConfigAsDefault(selectedConfig);
  }

  function handleGoBack() {
    handleDiscardAllChanges();
    setIsPreventDiscardChangesModalOpen(false);
    hideConfigDetails();
  }

  function triggerDiscardChangesModalOpen() {
    if (isValueModified) {
      setIsPreventDiscardChangesModalOpen(true);
    } else {
      handleGoBack();
    }
    _track.goBackButtonClicked();
  }

  function handleRenameConfigurationClicked() {
    setIsConfigNameModalOpen(true);
    _track.renameConfigurationClicked();
  }

  function handleSaveAsDefaultClicked() {
    setIsSetDefaultConfigModalOpen(true);
    _track.saveAsDefaultClicked();
  }

  function handleViewSetupGuideClicked() {
    const url = `https://${org.business_name.toLowerCase()}.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods`;
    window.open(url, '_blank');
    _track.viewSetupGuideClicked();
  }

  function handleDiscardChangesContinue() {
    handleGoBack();
    _track.discardChangesContinueClicked();
  }

  return (
    <Box>
      <Box
        display="flex"
        paddingBottom="spacing.3"
        gap="spacing.3"
        alignItems="center"
        borderBottomColor="surface.border.gray.muted"
      >
        <IconButton
          accessibilityLabel="go back"
          size="large"
          icon={ArrowLeftIcon}
          onClick={triggerDiscardChangesModalOpen}
        />
        <Box display="flex" flexGrow="1" gap="spacing.3" alignItems="center">
          <Heading size="small" weight="semibold" color="surface.text.gray.normal">
            {selectedConfig.name}
          </Heading>
          {selectedConfig?.is_default && (
            <Badge color="primary" size="small" emphasis="subtle">
              Default
            </Badge>
          )}
        </Box>

        {!isRazorpayConfig && (
          <Box display="flex" gap="spacing.5">
            {!isNewConfig && (
              <CopyConfigIDPopOver
                configID={selectedConfig.config_id ?? ''}
                docLink={`https://${org.business_name.toLowerCase()}.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods/understand-configuration`}
              />
            )}
            <Menu>
              <IconButtonWrapper>
                <MoreVerticalIcon size="medium" />
              </IconButtonWrapper>

              <MenuOverlay>
                <MenuItem onClick={handleRenameConfigurationClicked} title="Rename configuration" />
                <MenuItem
                  isDisabled={!!selectedConfig?.is_default || isNewConfig}
                  onClick={handleSaveAsDefaultClicked}
                  title="Save as default"
                />
                <MenuItem
                  title="View Setup Guide"
                  trailing={<ArrowUpRightIcon />}
                  onClick={handleViewSetupGuideClicked}
                />
              </MenuOverlay>
            </Menu>
          </Box>
        )}
      </Box>
      <CreateConfigModal
        isOpen={isConfigNameModalOpen}
        onClose={() => setIsConfigNameModalOpen(false)}
        onSave={handleConfigNameModalSave}
        ctaText="Save"
        initialConfigName={selectedConfig.name}
      />
      <SetConfigAsDefaultModal
        isOpen={isSetDefaultConfigModalOpen}
        onClose={() => setIsSetDefaultConfigModalOpen(false)}
        onSave={handleSetConfigAsDefaultModalSave}
        config={selectedConfig}
      />
      <PreventDiscardChangesModal
        isOpen={isPreventDiscardChangesModalOpen}
        title="Are you sure you want to go back?"
        content="Going back will discard your progress. Are you sure you want to continue?"
        onClose={() => setIsPreventDiscardChangesModalOpen(false)}
        onDiscard={handleDiscardChangesContinue}
      />
    </Box>
  );
}

export default compose(connect((state) => ({ org: state.session.org })))(Header);
