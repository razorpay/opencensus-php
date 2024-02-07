import React, { useContext, useEffect, useState } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  Heading,
  TextInput,
  Text,
  Button,
} from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';

import { queryClient } from 'common/components/Bootstrap/Wrapper';
import { Error } from 'common/new-ui/Input';
import { getFixedINRAmount, getFormattedAmountNew } from 'common/utils/rzp-utils';
import { OrderItemDenomination } from 'merchant/views/GCMS/Orders/types';
import { SKU } from 'merchant/views/GCMS/Programs/types';
import ProgramHeaderSection from 'merchant/views/GCMS/shared/ProgramHeaderSection';
import { GCMSSession, SessionContext } from 'merchant/views/GCMS/shared/context';
import { ErrorText } from 'merchant/views/Marketplace/PlatformFee/components/styles';

import { GCMSOrderSession, OrderSessionContext } from './context';
import { orderItemsCreate, orderItemsPatch } from './queries';

type Props = {
  isOpen: boolean;
  setIsOpen: (boolean) => void;
  sku: SKU;
  orderItems: OrderItemDenomination[];
};

const OrderCreateProgramDenominationsModal = ({
  isOpen,
  setIsOpen,
  sku,
  orderItems: orderItemsProps,
}: Props) => {
  const [orderItems, setOrderItems] = useState<OrderItemDenomination[]>([]);
  const [customOrderItem, setCustomOrderItem] = useState<OrderItemDenomination>({
    type: 'custom',
    quantity: 0,
    denomination: 0,
  });
  const { mode, merchantId } = useContext<GCMSSession>(SessionContext);
  const { orderId } = useContext<GCMSOrderSession>(OrderSessionContext);

  const {
    mutateAsync: orderItemsCreateMutation,
    isLoading,
    error,
    isError,
    reset,
  } = useMutation({
    mutationFn: orderItemsCreate,
  });

  const {
    mutateAsync: orderItemPatchMutation,
    isLoading: isLoadingOrderItemPatchMutation,
    error: errorOrderItemPatchMutation,
    isError: isErrorOrderItemPatchMutation,
    reset: resetOrderItemsPatch,
  } = useMutation({
    mutationFn: orderItemsPatch,
  });

  const isProgramDenominationArrayAvailable =
    Array.isArray(sku?.policies?.gift_card_price_denominations) &&
    sku?.policies?.gift_card_price_denominations.length > 0;

  useEffect(() => {
    reset();
    resetOrderItemsPatch();
    const orderItemDenominationsIndexArray: string[] = [];

    if (isProgramDenominationArrayAvailable) {
      const formattedOrderItemsDenominations = isProgramDenominationArrayAvailable
        ? sku?.policies?.gift_card_price_denominations.map((denomination) => {
            const filteredOrderItemDenomination = orderItemsProps.find((item) => {
              if (item.denomination === denomination) {
                orderItemDenominationsIndexArray.push(item.id || '');
                return true;
              } else {
                return false;
              }
            });
            return {
              denomination,
              ...filteredOrderItemDenomination,
            };
          })
        : [];
      setOrderItems(formattedOrderItemsDenominations);
    } else {
      const customOrderItem = orderItemsProps.find((item) =>
        orderItemDenominationsIndexArray.every((value) => value !== item.id),
      );
      setCustomOrderItem(
        customOrderItem
          ? { ...customOrderItem, type: 'custom' }
          : { type: 'custom', quantity: 0, denomination: 0 },
      );
    }
  }, [isOpen, orderItemsProps, sku?.policies?.gift_card_price_denominations]);

  const clear = () => {
    setCustomOrderItem({ type: 'custom', quantity: 0, denomination: 0 });
    setOrderItems([]);
  };

  const updateOrderItem = ({ type = 'fixed', quantity, denomination }) => {
    if (type === 'custom') {
      return setCustomOrderItem((prevState) => ({
        ...prevState,
        quantity: quantity || prevState.quantity,
        denomination: denomination || prevState.denomination,
        program_id: sku.program_id,
        sku_id: sku.id,
      }));
    } else {
      return setOrderItems(
        orderItems.map((item) =>
          item.denomination === denomination
            ? { ...item, type, program_id: sku.program_id, sku_id: sku.id, quantity, denomination }
            : item,
        ),
      );
    }
  };

  const onSubmitClick = async (): Promise<void> => {
    try {
      const items = orderItems
        .concat([{ ...customOrderItem, denomination: (customOrderItem.denomination || 0) * 100 }])
        .filter((item) => item.program_id && item.sku_id);

      const data = items.reduce(
        (reduce, item) => {
          /* @ts-expect-error undefined-object-check */
          reduce[item.id ? 'patchedData' : 'updatedData'].push(item);
          return reduce;
        },
        { patchedData: [], updatedData: [] },
      );
      if (data.updatedData.length > 0) {
        await orderItemsCreateMutation({ merchantId, orderItems: data.updatedData, orderId, mode });
      }
      if (data.patchedData.length > 0) {
        await Promise.all(
          data.patchedData.map(async (item) => {
            await orderItemPatchMutation({
              orderId,
              /* @ts-expect-error undefined-object-check */
              itemId: item.id,
              orderItem: item,
              mode,
              merchantId,
            });
          }),
        );
      }
      queryClient.invalidateQueries({
        queryKey: ['wallet:order:items', merchantId, orderId, mode],
      });
      setIsOpen(false);
      clear();
      /* eslint-disable-next-line no-empty */
    } catch (ex) {}
  };

  const onCancelClick = () => {
    setIsOpen(false);
    clear();
  };

  return (
    <Modal isOpen={isOpen} onDismiss={() => setIsOpen(false)} size="medium">
      <ModalHeader title="Selected Gift Card Program" />
      <ModalBody>
        <Box>
          <ProgramHeaderSection
            program={sku}
            containerProps={{
              height: '80px',
              padding: ['spacing.0', 'spacing.0', 'spacing.5', 'spacing.0'],
              borderBottomWidth: 'thick',
              borderBottomColor: 'surface.border.subtle.lowContrast',
            }}
            imageProps={{ height: '60px', width: '94px' }}
          />
        </Box>
        <Box
          display="flex"
          flexDirection="row"
          alignItems="center"
          padding={['spacing.4', 'spacing.0', 'spacing.0', 'spacing.0']}
        >
          <Box width="200px">
            <Text color="surface.text.muted.lowContrast">Denomination</Text>
          </Box>
          <Box width="200px">
            <Text color="surface.text.muted.lowContrast">Quantity</Text>
          </Box>
        </Box>
        {isProgramDenominationArrayAvailable ? (
          orderItems.map(({ id, denomination, quantity }, index) => {
            return (
              <Box
                key={id ? id + index : index}
                borderBottomWidth="thick"
                borderBottomColor="surface.border.subtle.lowContrast"
                display="flex"
                flexDirection="row"
                alignItems="center"
                padding={['spacing.3', 'spacing.0', 'spacing.4', 'spacing.0']}
              >
                <Box width="200px">
                  <Heading size="small" color="surface.text.subdued.lowContrast">
                    {denomination ? getFormattedAmountNew(denomination, true) : 0}
                  </Heading>
                </Box>
                <Box width="200px">
                  <Box width="175px">
                    <TextInput
                      label=""
                      type="number"
                      placeholder="0"
                      /* @ts-expect-error undefined-object-check */
                      defaultValue={quantity}
                      onChange={({ value }) => updateOrderItem({ quantity: value, denomination })}
                    />
                  </Box>
                </Box>
              </Box>
            );
          })
        ) : (
          <Box
            display="flex"
            flexDirection="row"
            alignItems="center"
            padding={['spacing.3', 'spacing.0', 'spacing.0', 'spacing.0']}
          >
            <Box width="200px">
              <Box width="175px">
                <TextInput
                  label=""
                  placeholder="Enter custom amount"
                  type="number"
                  defaultValue={
                    customOrderItem?.denomination
                      ? getFixedINRAmount(customOrderItem?.denomination)
                      : 0
                  }
                  /* @ts-expect-error undefined-object-check */
                  onChange={({ value }) => updateOrderItem({ type: 'custom', denomination: value })}
                />
              </Box>
            </Box>
            <Box width="200px">
              <Box width="175px">
                <TextInput
                  label=""
                  placeholder="0"
                  type="number"
                  /* @ts-expect-error undefined-object-check */
                  defaultValue={customOrderItem?.quantity}
                  /* @ts-expect-error undefined-object-check */
                  onChange={({ value }) => updateOrderItem({ type: 'custom', quantity: value })}
                />
              </Box>
            </Box>
          </Box>
        )}

        {isError ||
          (isErrorOrderItemPatchMutation && (
            <Box>
              <Error
                text={
                  /* @ts-expect-error error-message-check */
                  error?.message || errorOrderItemPatchMutation?.message || 'Something Went Wrong'
                }
              />
            </Box>
          ))}
      </ModalBody>
      <ModalFooter>
        <Box paddingX="spacing.2" display="flex" flexDirection="row">
          <Box paddingRight="spacing.4">
            <Button
              isLoading={isLoading || isLoadingOrderItemPatchMutation}
              onClick={onSubmitClick}
            >
              Add to cart
            </Button>
          </Box>
          <Box>
            <Button
              variant="secondary"
              isDisabled={isLoading || isLoadingOrderItemPatchMutation}
              onClick={onCancelClick}
            >
              Cancel
            </Button>
          </Box>
        </Box>
        {(isError || isErrorOrderItemPatchMutation) && (
          <Box paddingX="spacing.2">
            {/* @ts-expect-error error-message-check */}
            <ErrorText>{error?.message || errorOrderItemPatchMutation?.message}</ErrorText>
          </Box>
        )}
      </ModalFooter>
    </Modal>
  );
};

export default OrderCreateProgramDenominationsModal;
