import React from 'react';
import { Box, EditComposeIcon, TrashIcon, IconButton, Text } from '@razorpay/blade/components';

import { DeleteIconWrapper, ShippingMethodTableWrapper } from './styles';
import MagicDataTable from 'merchant/views/MagicCheckout/common/components/Datatable';
import {
  COD,
  ETD,
  Slab,
  //SubscribeRate,
} from 'merchant/views/MagicCheckout/ShippingSettings/common/cellItem';
import { ShippingMethod } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { useFormContext } from './FormContext';

const ShippingMethodsTable = ({
  shipping_methods,
  handleEdit,
  handleDelete,
}: {
  shipping_methods: ShippingMethod[];
  handleEdit?: () => void;
  handleDelete?: (id) => void;
}): JSX.Element => {
  const { setSelectedMethod } = useFormContext();
  const handleMethodEdit = (method) => {
    setSelectedMethod(method);
    handleEdit?.();
  };
  return (
    <div>
      {shipping_methods.map((method) => (
        <ShippingMethodTableWrapper key={method.id}>
          <Box display="flex" justifyContent="space-between" marginBottom="spacing.4">
            <Box>
              <Text weight="bold">
                {`${method.name} @ ${getFormattedAmountNew(method.fee, true)}`}
              </Text>
              {method.description && (
                <Text size="small" type="subdued">
                  {method.description}
                </Text>
              )}
            </Box>
            {handleEdit && handleDelete ? (
              <Box display="flex" alignItems="center" gap="spacing.4">
                <IconButton
                  onClick={() => handleMethodEdit(method)}
                  accessibilityLabel="edit"
                  icon={() => <EditComposeIcon size="medium" color="action.icon.link.active" />}
                />

                <DeleteIconWrapper>
                  <IconButton
                    accessibilityLabel="delete"
                    onClick={() => handleDelete(method.id)}
                    icon={() => (
                      <TrashIcon size="medium" color="feedback.icon.negative.lowContrast" />
                    )}
                  />
                </DeleteIconWrapper>
              </Box>
            ) : null}
          </Box>

          <MagicDataTable
            data={[method]}
            columns={[
              COD,
              Slab,
              // SubscribeRate,
              ETD,
            ]}
          />
        </ShippingMethodTableWrapper>
      ))}
    </div>
  );
};

export default ShippingMethodsTable;
