import React, { useCallback, useContext, useEffect, useState } from 'react';
import {
  Box,
  Button,
  Divider,
  MapPinIcon,
  PlusIcon,
  RadioGroup,
  Text,
} from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import { INDIAN_STATES as STATES } from '@libs/shared-utils';
import OrderCollapsible from 'apps/pos/src/app/views/SelfServe/OrderSummary/OrderCollapsible';
import { ACTIONS } from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import { updateDeliveryAddress } from 'apps/pos/src/app/views/SelfServe/helpers';
import { UpdateDeliveryAddress } from 'apps/pos/src/app/views/SelfServe/types';
import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';

import DeliveryAddress from './DeliveryAddress';

const DeliveryAddresses = ({ defaultIsExpanded }: { defaultIsExpanded: boolean }): JSX.Element => {
  const { state, dispatch } = useContext(PosDeviceStoreContext);
  const { user, deliveryAddresses } = state;
  const { isMobile } = useBladeBreakpoints();

  const [addreses, setAddresses] = useState(deliveryAddresses);
  const [editiableAddressIndex, setEditableAddresssIndex] = useState<number | null>(null);
  const [isExpanded, setIsExapanded] = useState<boolean>(defaultIsExpanded || false);
  const [isAddNewDeliveryAddress, setIsAddNewDeliveryAddress] = useState<boolean>(false);

  useEffect(() => {
    setAddresses(deliveryAddresses);
  }, [deliveryAddresses]);

  useEffect(() => {
    dispatch({
      type: ACTIONS.SET_DELIVERY_ADDRESS_FORM_OPEN,
      payload: {
        isDeliveryAddressFormOpen: editiableAddressIndex !== null || isAddNewDeliveryAddress,
      },
    });
  }, [editiableAddressIndex, isAddNewDeliveryAddress]);

  const getSelectedAddress = useCallback(
    () => deliveryAddresses.findIndex((address) => address.isSelected) ?? 0,
    [deliveryAddresses],
  );

  const handleOnAddNewAddressClick = () => {
    setEditableAddresssIndex(null);
    setIsAddNewDeliveryAddress(true);
  };

  const handleOnEditClick = (index: number) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
      label: 'Edit',
      whatsAppUpdates: 'No',
      section: 'Pre-checkout - Edit Address',
      subSection: 'Pre-checkout - Edit Address',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Pre-checkout - Edit Address',
    });

    setAddresses(deliveryAddresses);
    setIsAddNewDeliveryAddress(false);
    setEditableAddresssIndex(index);
  };

  const handleOnCancelClick = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
      label: 'Cancel',
      whatsAppUpdates: 'No',
      section: 'Pre-checkout - Edit Address',
      subSection: 'Pre-checkout - Edit Address',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Pre-checkout - Edit Address',
    });
    setEditableAddresssIndex(null);
    setIsAddNewDeliveryAddress(false);
  };

  const handleOnAddressSwitch = (id) => {
    if (user) {
      const newAddresses = updateDeliveryAddress({
        id,
        address: addreses[id],
        isNewDeliveryAddress: false,
        addresses: deliveryAddresses,
        user,
      });
      dispatch({
        type: ACTIONS.UPDATE_DELIVERY_ADDRESSES,
        payload: {
          deliveryAddresses: newAddresses,
        },
      });
    }
  };

  const handleSaveAddress = ({ id, address, isNewDeliveryAddress }: UpdateDeliveryAddress) => {
    if (user) {
      analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
        label: 'Save Address',
        whatsAppUpdates: 'No',
        l1FunnelStage: 'Purchase Intention',
        l2FunnelStage: isNewDeliveryAddress ? 'Pre-checkout' : 'Pre-checkout - Edit Address',
        section: 'Pre-checkout',
        subSection: 'Delivery Address',
      });

      const newAddresses = updateDeliveryAddress({
        id,
        address,
        isNewDeliveryAddress,
        addresses: deliveryAddresses,
        user,
      });

      dispatch({
        type: ACTIONS.UPDATE_DELIVERY_ADDRESSES,
        payload: {
          deliveryAddresses: newAddresses,
        },
      });
    }
    setEditableAddresssIndex(null);
    setIsAddNewDeliveryAddress(false);
  };

  const selectedAddressIndex = editiableAddressIndex ?? getSelectedAddress();
  const selectedAddress = deliveryAddresses?.[selectedAddressIndex];

  const handleOnCollapsibleChange = (expandedState) =>
    expandedState ? setIsExapanded(expandedState) : handleOnCancelClick();

  return (
    <OrderCollapsible
      icon={
        <MapPinIcon
          size="large"
          color={isExpanded ? 'interactive.icon.primary.normal' : 'interactive.icon.gray.normal'}
        />
      }
      title={
        <Box display={{ base: 'block', l: 'flex' }}>
          <Box flex="0 0 auto">
            <Text marginRight="spacing.3" size="large">
              Delivery Address
            </Text>
          </Box>
          {selectedAddress?.address && !editiableAddressIndex && !isAddNewDeliveryAddress ? (
            <Box testID="header-address">
              <Text
                weight="regular"
                truncateAfterLines={1}
                size="large"
                color="surface.text.gray.subtle"
              >
                {selectedAddress?.name}, {selectedAddress?.address}, {selectedAddress?.city},{' '}
                {STATES[selectedAddress?.state]}-{selectedAddress?.pincode}
              </Text>
            </Box>
          ) : null}
        </Box>
      }
      testID="delivery-address-container"
      defaultIsExpanded={defaultIsExpanded}
      onCollapsibleChange={handleOnCollapsibleChange}
    >
      <>
        <RadioGroup
          onChange={({ value }) => handleOnAddressSwitch(value)}
          value={
            isAddNewDeliveryAddress ? addreses.length.toString() : selectedAddressIndex.toString()
          }
          margin={isMobile ? 'spacing.0' : '8px'}
        >
          {addreses.map((address, index: number) => (
            <React.Fragment key={`${index}-delivery-address`}>
              <DeliveryAddress
                key={`delivery-address-${index}`}
                id={index.toString()}
                deliveryAddress={address}
                isEditing={index === editiableAddressIndex}
                isSelectDisabled={isAddNewDeliveryAddress}
                onSubmit={handleSaveAddress}
                onEditClick={() => handleOnEditClick(index)}
                onEditCancel={handleOnCancelClick}
                isNewAddress={false}
              />
              {index !== addreses.length - 1 || (isAddNewDeliveryAddress && !isMobile) ? (
                <Divider marginX="spacing.5" />
              ) : null}
            </React.Fragment>
          ))}
          <DeliveryAddress
            key={`${addreses.length}-delivery-address`}
            id={addreses.length.toString()}
            isEditing={isAddNewDeliveryAddress}
            onSubmit={handleSaveAddress}
            onEditClick={() => handleOnEditClick(addreses.length)}
            onEditCancel={handleOnCancelClick}
            isNewAddress
          />
        </RadioGroup>
        <Box margin={isMobile ? 'spacing.4' : ['4px', '24px', '24px', '24px']} paddingTop="4px">
          <Button
            variant="tertiary"
            isFullWidth
            icon={PlusIcon}
            isDisabled={isAddNewDeliveryAddress}
            onClick={handleOnAddNewAddressClick}
          >
            Add New Address
          </Button>
        </Box>
      </>
    </OrderCollapsible>
  );
};

export default DeliveryAddresses;
