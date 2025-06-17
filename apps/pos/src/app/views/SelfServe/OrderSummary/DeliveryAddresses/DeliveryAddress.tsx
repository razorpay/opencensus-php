import React from 'react';
import { Radio, Box } from '@razorpay/blade/components';

import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';
import {
  DeliveryAddress as DeliveryAddressType,
  UpdateDeliveryAddress,
} from 'apps/pos/src/app/views/SelfServe/types';

import Address from './Address';
import AddressForm from './AddressForm';

type DeliveryAddressProps = {
  id: string;
  deliveryAddress?: DeliveryAddressType;
  isNewAddress: boolean;
  isEditing: boolean;
  isSelectDisabled?: boolean;
  onEditClick: () => void;
  onEditCancel: () => void;
  onSubmit: (values: UpdateDeliveryAddress) => void;
};

const DeliveryAddress = ({
  id,
  isNewAddress,
  isEditing,
  deliveryAddress,
  isSelectDisabled,
  onEditClick,
  onEditCancel,
  onSubmit,
}: DeliveryAddressProps): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const hasDeliveryAddress = deliveryAddress?.address && deliveryAddress?.name;

  const isOnMobileAndisEditingNewAddress = isMobile && isNewAddress && isEditing;
  const isNotOnMobileAndEditingNewAddress = !isMobile && isNewAddress && isEditing;

  return (
    <Box
      testID={`delivery-address-${id}`}
      display="flex"
      padding={
        isOnMobileAndisEditingNewAddress || (isNewAddress && !isEditing) ? 'spacing.0' : 'spacing.5'
      }
    >
      {!isNewAddress || isNotOnMobileAndEditingNewAddress ? (
        <Radio value={id} isDisabled={isSelectDisabled} marginRight="spacing.3">
          {''}
        </Radio>
      ) : null}

      <Box width="100%">
        {(!isEditing || isMobile) && hasDeliveryAddress ? (
          <Address
            deliveryAddress={deliveryAddress}
            isDisabled={deliveryAddress.type === 'default'}
            onEditClick={onEditClick}
          />
        ) : null}
        {isMobile || isEditing ? (
          <AddressForm
            isBottomSheetOpen={isMobile && isEditing}
            deliveryAddress={deliveryAddress}
            isEdit={!isNewAddress}
            onSubmit={(values) =>
              onSubmit({ id, address: values, isNewDeliveryAddress: !!isNewAddress })
            }
            onCancelClick={onEditCancel}
          />
        ) : null}
      </Box>
    </Box>
  );
};

export default DeliveryAddress;
