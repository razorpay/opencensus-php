import React from 'react';
import { Badge, Box, Radio, Text } from '@razorpay/blade/components';
import {
  DeliveryAddressFields,
  DeviceDeliveryAddress,
  DeviceDeliveryAddressTypes,
} from 'apps/pos/src/app/types/DeviceSelection';

interface DeliverAddressRadio {
  value: DeviceDeliveryAddressTypes;
  address: DeviceDeliveryAddress;
}

const ADDRESS_NAME_MAP: Record<DeviceDeliveryAddressTypes, string> = {
  operation: 'Operational Address',
  registered: 'Registered Address',
};

const DeliveryAddressRadio = ({ value, address }: DeliverAddressRadio): JSX.Element => {
  return (
    <Box display="flex" alignItems="flex-start" marginBottom="spacing.5">
      <Box>
        <Radio value={value} marginRight="spacing.5" />
      </Box>
      <Box width="100%">
        <Box display="flex" justifyContent="space-between" marginBottom="spacing.3">
          <Box maxWidth={{ base: '200px', l: '100%' }}>
            <Text weight="semibold" size="large">
              {address?.[DeliveryAddressFields.name]}
            </Text>
          </Box>
          {ADDRESS_NAME_MAP[value] ? (
            <Badge size="large" color="primary">
              {ADDRESS_NAME_MAP[value]}
            </Badge>
          ) : null}
        </Box>
        <Text color="surface.text.gray.subtle" marginBottom="spacing.5">
          {address?.[DeliveryAddressFields.contact]}
        </Text>
        <Text color="surface.text.gray.subtle"> {address?.[DeliveryAddressFields.line1]}</Text>
        <Text color="surface.text.gray.subtle">
          {address?.[DeliveryAddressFields.city]}, {address?.[DeliveryAddressFields.state]} -{' '}
          {address?.[DeliveryAddressFields.zipcode]}
        </Text>
      </Box>
    </Box>
  );
};

export default DeliveryAddressRadio;
