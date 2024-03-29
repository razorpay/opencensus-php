import React, { useState } from 'react';
import { connect } from 'react-redux';

import {
  Box,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon,
} from '@razorpay/blade/components';

import { SettingsWrapper } from 'merchant/views/MagicCheckout/ShippingSettings/styles';
import ConfigItem from './ConfigItem';

import { ShippingEngineStore, Zone } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import ShippingModal from './Modal';
import { MODAL_MODES } from 'merchant/views/MagicCheckout/common/components/SettingsModal/constants';
import { ADD_PROFILE } from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { useFormContext } from './FormContext';

const ShippingMethods = ({ shippingEngine }): JSX.Element => {
  const [isOpen, setIsOpen] = useState(false);
  const [mode, setMode] = useState(MODAL_MODES.CREATE);
  const { resetForm } = useFormContext();
  const [selectZone, setSelectedZone] = useState({});
  const { selected_profile, shipping_profiles } = shippingEngine as ShippingEngineStore;
  const zones =
    selected_profile && selected_profile !== ADD_PROFILE
      ? shipping_profiles[selected_profile].zones
      : null;

  const handleConfigClick = (zone: Zone, mode) => {
    setMode(mode);
    setSelectedZone(zone);

    setIsOpen(true);
  };

  const handleClose = () => {
    setIsOpen(false);
    resetForm();
  };

  return (
    <Box display="flex" gap="spacing.5" flexDirection={{ base: 'column', l: 'row' }}>
      <Box flex="1">
        <Text size="large">
          Shipping method & rate
          <Text as="span" color="feedback.text.negative.intense">
            *
          </Text>
          <Tooltip
            content="Define your shipping methods (e.g., Standard, Express) and associate specific rates with them. Tailor rules based on factors like weight and cart value."
            placement="bottom"
          >
            <TooltipInteractiveWrapper>
              <InfoIcon
                color="interactive.icon.gray.muted"
                marginLeft="spacing.2"
                position="relative"
                top="spacing.1"
                size="medium"
              />
            </TooltipInteractiveWrapper>
          </Tooltip>
        </Text>
      </Box>
      <SettingsWrapper>
        {zones?.map((zone) => (
          <ConfigItem key={zone.id} zone={zone} handleClick={handleConfigClick} />
        ))}
      </SettingsWrapper>
      <ShippingModal
        zone={selectZone as Zone}
        isOpen={isOpen}
        closeModal={handleClose}
        isLoading={false}
        mode={mode}
      />
    </Box>
  );
};

const mapStateToProps = (state) => ({
  shippingEngine: state.magicShippingEngine,
});

export default connect(mapStateToProps, null)(ShippingMethods);
