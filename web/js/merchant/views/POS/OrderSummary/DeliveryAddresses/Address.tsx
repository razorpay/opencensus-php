import React from 'react';
import { Box, Link, EditIcon, Text } from '@razorpay/blade/components';

import { states as STATES } from 'merchant/helpers/data';
import { DeliveryAddress } from 'merchant/views/POS/types';

type AddressProps = {
  deliveryAddress: DeliveryAddress;
  isDisabled: boolean;
  onEditClick: () => void;
};

const Address = ({ deliveryAddress, isDisabled, onEditClick }: AddressProps): JSX.Element => {
  const { name, pincode, phoneNumber, address, city, state } = deliveryAddress;
  return (
    <Box width="100%">
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        marginBottom="spacing.3"
      >
        <Text size="large">{name}</Text>
        {isDisabled ? null : (
          <Link variant="button" icon={EditIcon} onClick={() => onEditClick()}>
            Edit
          </Link>
        )}
      </Box>
      <Text weight="regular" size="large" color="surface.text.gray.subtle">
        {phoneNumber}
      </Text>
      <Text weight="regular" size="large" color="surface.text.gray.subtle">
        {address}, {city}, {STATES[state]}-{pincode}
      </Text>
    </Box>
  );
};

export default Address;
