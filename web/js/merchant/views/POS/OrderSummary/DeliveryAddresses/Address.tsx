import React from 'react';
import { Box, Heading, Link, EditIcon } from '@razorpay/blade/components';

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
        <Heading>{name}</Heading>
        {isDisabled ? null : (
          <Link variant="button" icon={EditIcon} onClick={() => onEditClick()}>
            Edit
          </Link>
        )}
      </Box>
      <Heading weight="regular" size="small" type="subtle">
        {phoneNumber}
      </Heading>
      <Heading weight="regular" size="small" type="subtle">
        {address}, {city}, {STATES[state]}-{pincode}
      </Heading>
    </Box>
  );
};

export default Address;
