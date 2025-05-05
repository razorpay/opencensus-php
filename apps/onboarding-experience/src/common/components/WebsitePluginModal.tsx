import React, { useState } from 'react';
import {
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Box,
  Text,
  Card,
  Dropdown,
  DropdownOverlay,
  AutoComplete,
  ActionList,
  ActionListItem,
  ActionListItemAsset,
  CardBody,
} from '@razorpay/blade/components';
import ShopifyLogo from 'apps/onboarding-experience/src/common/assets/ShopifyLogo.svg';
import WoocommerceLogo from 'apps/onboarding-experience/src/common/assets/WoocommerceLogo.svg';
import WixLogo from 'apps/onboarding-experience/src/common/assets/WixLogo.svg';

export const TOP_THREE_PLUGINS = [
  {
    pluginName: 'Shopify',
    src: ShopifyLogo,
    alt: 'Shopify Logo',
  },
  {
    pluginName: 'WooCommerce',
    src: WoocommerceLogo,
    alt: 'WooCommerce Logo',
  },
  {
    pluginName: 'Wix',
    src: WixLogo,
    alt: 'Wix Logo',
  },
];

const SelectableHeroPlugin = ({
  pluginName,
  src,
  alt,
  onClick,
  selectedPlugin,
}: {
  pluginName: string;
  src: string;
  alt: string;
  onClick: (pluginName: string) => void;
  selectedPlugin: string;
}) => {
  return (
    <Box
      flex="1 0 0"
      alignSelf="strech"
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
      gap="8px"
      borderRadius="medium"
      elevation="lowRaised"
    >
      <Card
        width="100%"
        height="100%"
        elevation="none"
        padding="spacing.5"
        isSelected={selectedPlugin === pluginName}
        onClick={() => onClick(pluginName)}
        accessibilityLabel="Payment Links Card"
        shouldScaleOnHover
        testID={`plugin-card-${pluginName}`}
      >
        <CardBody>
          <Box display="flex" justifyContent="center" alignItems="center">
            <img src={src} alt={alt} />
          </Box>
        </CardBody>
      </Card>
    </Box>
  );
};

const WebsitePluginModal = ({
  onDismiss,
  supportedPlugins = [],
  handleAddPlugin,
}: {
  onDismiss: () => void;
  supportedPlugins?: { name: string; icon: string }[];
  handleAddPlugin: (newPlugin: string) => Promise<void>;
}) => {
  const [selectedPlugin, setselectedPlugin] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  // GQL mutation to add the plugin
  const handleUpdatePlugin = async () => {
    setIsSubmitting(true);
    try {
      await handleAddPlugin(selectedPlugin);
      onDismiss();
    } catch (error) {
      console.error('Failed to add plugin:', error);
    }
    setIsSubmitting(false);
  };

  return (
    <Modal isOpen={true} onDismiss={onDismiss}>
      <ModalHeader
        title="Select your website plugin"
        subtitle="Every plugin has a different way of integration so make sure you select the right one."
      />
      <ModalBody>
        <Box
          display="flex"
          flexDirection="column"
          alignItems="flex-start"
          gap="spacing.7"
          alignSelf="strech"
        >
          <Box display="flex" flexDirection="column" alignItems="flex-start" gap="spacing.5">
            <Text color="surface.text.gray.muted" size="small" weight="semibold">
              Most popular website builders
            </Text>
            <Box display="flex" width="360px" alignItems="center" gap="spacing.2">
              {TOP_THREE_PLUGINS.map((plugin) => (
                <SelectableHeroPlugin
                  key={plugin.pluginName}
                  pluginName={plugin.pluginName}
                  src={plugin.src}
                  alt={plugin.alt}
                  onClick={(pluginName) => {
                    setselectedPlugin(pluginName);
                  }}
                  selectedPlugin={selectedPlugin}
                />
              ))}
            </Box>
          </Box>
          <Dropdown selectionType="single" _width="100%">
            <AutoComplete
              label="Select other website plugins"
              placeholder=""
              name="action"
              value={selectedPlugin}
              onChange={({ values }) => setselectedPlugin(values[0])}
            />
            <DropdownOverlay>
              <ActionList>
                {supportedPlugins?.map((plugin) => (
                  <ActionListItem
                    key={plugin.name}
                    leading={<ActionListItemAsset src={plugin.icon} alt={plugin.name} />}
                    title={plugin.name}
                    value={plugin.name}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end">
          <Button
            isDisabled={!selectedPlugin || isSubmitting}
            isLoading={isSubmitting}
            onClick={handleUpdatePlugin}
          >
            Done
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default WebsitePluginModal;
