import React, { useContext, useEffect, useState } from 'react';
import {
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Box,
  TextInput,
  Text,
  Button,
  PlusIcon,
  CloseIcon,
} from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';

import { queryClient } from 'merchant/views/GCMS/shared/Wrapper';
import { Error } from 'common/new-ui/Input';
import { getFixedINRAmount } from 'common/utils/rzp-utils';
import { OrderItemDenomination, ModalTypeEnum } from 'merchant/views/GCMS/Orders/types';
import { SKU } from 'merchant/views/GCMS/Programs/types';
import ProgramHeaderSection from 'merchant/views/GCMS/shared/ProgramHeaderSection';
import { GCMSSession, SessionContext } from 'merchant/views/GCMS/shared/context';
import { getFormattedAmountNewDenom, isPositiveInteger } from 'merchant/views/GCMS/shared/utils';
import { ErrorText } from 'merchant/views/Marketplace/PlatformFee/components/styles';
import { showNotification } from 'merchant_common/reducers/notifications';

import { GCMSOrderSession, OrderSessionContext } from './context';
import {
  trackOrdersCreateCartProgramsModalCancelled,
  trackOrdersCreateCartProgramsModalPageLoadSuccess,
  trackOrdersCreateCartProgramsModalSuccess,
} from './events';
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
  const [customOrderItems, setCustomOrderItems] = useState<OrderItemDenomination[]>([
    {
      index: 0,
      type: 'custom',
      quantity: 0,
      denomination: 0,
    },
  ]);
  const [errorText, setErrorText] = useState('');
  const { mode, merchantId } = useContext<GCMSSession>(SessionContext);
  const { orderId, resellerId } = useContext<GCMSOrderSession>(OrderSessionContext);

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
    onError: (error) => {
      /* @ts-expect-error empty */
      setErrorText(error?.message || 'Something went wrong. Please try again.');
    },
  });

  const isProgramDenominationArrayAvailable =
    Array.isArray(sku?.policies?.gift_card_price_denominations) &&
    sku?.policies?.gift_card_price_denominations.length > 0;
  const modalType = isProgramDenominationArrayAvailable
    ? ModalTypeEnum.FIXED
    : ModalTypeEnum.CUSTOM;
  useEffect(() => {
    setErrorText('');
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
      const formattedCustomOrderedItems = orderItemsProps.map((item) => ({
        ...item,
        type: 'custom',
      }));
      setCustomOrderItems(
        orderItemsProps.length > 0
          ? formattedCustomOrderedItems
          : [{ type: 'custom', quantity: 0, denomination: 0 }],
      );
    }
    if (isOpen)
      trackOrdersCreateCartProgramsModalPageLoadSuccess({
        type: modalType,
        programId: sku?.program_id,
        skuId: sku?.id,
        orderId,
        resellerId,
      });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOpen, orderItemsProps, sku?.policies?.gift_card_price_denominations]);

  const clear = () => {
    setCustomOrderItems([{ type: 'custom', quantity: 0, denomination: 0, index: 0 }]);
    setOrderItems([]);
  };

  const updateOrderItem = ({ type = 'fixed', quantity, denomination, index: itemIndex = 0 }) => {
    if (type === 'custom') {
      return setCustomOrderItems(
        customOrderItems.map((item, index) =>
          itemIndex === index
            ? {
                ...item,
                type,
                program_id: sku.program_id,
                sku_id: sku.id,
                quantity: quantity || item.quantity,
                denomination: denomination || item.denomination,
              }
            : item,
        ),
      );
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
        .concat(
          customOrderItems.map((item) => ({
            ...item,
            denomination: item?.id ? item.denomination : (item.denomination || 0) * 100,
          })),
        )
        /* @ts-expect-error type-check-regex */
        .filter((item) => isPositiveInteger(item?.quantity))
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
        queryKey: ['gcms:order:items', merchantId, orderId, mode],
      });
      setIsOpen(false);
      clear();
      showNotification({
        type: 'success',
        message: 'Order has been added to cart successfully',
      });
      /* eslint-disable-next-line no-empty */
    } catch (ex) {}

    trackOrdersCreateCartProgramsModalSuccess({
      type: modalType,
      programId: sku?.program_id,
      skuId: sku?.id,
      orderId,
      resellerId,
    });
  };

  const onCancelClick = () => {
    setIsOpen(false);
    clear();
    trackOrdersCreateCartProgramsModalCancelled({
      type: modalType,
      programId: sku?.program_id,
      skuId: sku?.id,
      orderId,
      resellerId,
    });
  };

  const onSubmitAddCustomOrderItem = () => {
    setCustomOrderItems([
      ...customOrderItems,
      {
        type: 'custom',
        quantity: 0,
        denomination: 0,
        index: customOrderItems.length,
        program_id: sku.program_id,
        sku_id: sku.id,
      },
    ]);
  };

  const customOrderEntriesSet = new Set(
    customOrderItems.map((item) =>
      item.denomination
        ? parseInt(String(item?.id ? item.denomination / 100 : item.denomination), 10)
        : item.denomination,
    ),
  );

  const onSubmitRemoveCustomOrderItem = (index) => {
    setCustomOrderItems(customOrderItems.filter((item, i) => i !== index));
  };

  const hasDuplicateEntries = customOrderEntriesSet.size < customOrderItems.length;

  return (
    <Modal isOpen={isOpen} onDismiss={() => setIsOpen(false)} size="medium">
      <ModalHeader title="Selected Gift Card Program" />
      <ModalBody>
        <Box>
          <ProgramHeaderSection
            program={sku}
            containerProps={{
              minHeight: '80px',
              padding: ['spacing.0', 'spacing.0', 'spacing.5', 'spacing.0'],
              borderBottomWidth: 'thick',
              borderBottomColor: 'surface.border.gray.subtle',
            }}
            imageProps={{
              maxHeight: '60px',
              maxWidth: '94px',
              minHeight: '40px',
              minWidth: '60px',
            }}
          />
        </Box>
        <Box
          display="flex"
          flexDirection="row"
          alignItems="center"
          padding={['spacing.4', 'spacing.0', 'spacing.0', 'spacing.0']}
        >
          <Box minWidth="185px" paddingRight="spacing.4">
            <Text color="surface.text.gray.muted">Denomination</Text>
          </Box>
          <Box minWidth="185px" paddingRight="spacing.4">
            <Text color="surface.text.gray.muted">Quantity</Text>
          </Box>
        </Box>
        {isProgramDenominationArrayAvailable
          ? orderItems.map(({ id, denomination, quantity }, index) => {
              return (
                <Box
                  key={id ? id + index : index}
                  borderBottomWidth="thick"
                  borderBottomColor="surface.border.gray.subtle"
                  display="flex"
                  flexDirection="row"
                  alignItems="center"
                  padding={['spacing.3', 'spacing.0', 'spacing.4', 'spacing.0']}
                >
                  <Box minWidth="185px" paddingRight="spacing.4">
                    <Text color="surface.text.gray.muted" size="large">
                      {denomination ? getFormattedAmountNewDenom(denomination, true) : 0}
                    </Text>
                  </Box>
                  <Box minWidth="185px" paddingRight="spacing.4">
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
              );
            })
          : customOrderItems.map((customOrderItem, index) => {
              return (
                <Box
                  key={customOrderItem?.id ? customOrderItem.id + index : index}
                  borderBottomWidth="thick"
                  borderBottomColor="surface.border.gray.subtle"
                  display="flex"
                  flexDirection="row"
                  alignItems="center"
                  padding={['spacing.3', 'spacing.0', 'spacing.4', 'spacing.0']}
                >
                  <Box minWidth="185px" paddingRight="spacing.4">
                    <TextInput
                      isDisabled={!!customOrderItem.id}
                      label=""
                      placeholder="Enter custom amount"
                      type="number"
                      defaultValue={
                        customOrderItem?.denomination
                          ? getFixedINRAmount(customOrderItem?.denomination)
                          : 0
                      }
                      onChange={({ value }) =>
                        /* @ts-expect-error undefined-object-check */
                        updateOrderItem({ type: 'custom', denomination: value, index })
                      }
                    />
                  </Box>
                  <Box minWidth="185px" paddingRight="spacing.4">
                    <TextInput
                      label=""
                      placeholder="0"
                      type="number"
                      /* @ts-expect-error undefined-object-check */
                      defaultValue={customOrderItem?.quantity}
                      onChange={({ value }) =>
                        /* @ts-expect-error undefined-object-check */
                        updateOrderItem({ type: 'custom', quantity: value, index })
                      }
                    />
                  </Box>
                  {!customOrderItem?.id && index !== 0 && (
                    <Box width="200px">
                      <Button
                        icon={CloseIcon}
                        variant="secondary"
                        onClick={() => onSubmitRemoveCustomOrderItem(index)}
                      />
                    </Box>
                  )}
                </Box>
              );
            })}
        {!isProgramDenominationArrayAvailable && (
          <Box paddingY="spacing.4">
            <Button
              isDisabled={hasDuplicateEntries}
              icon={PlusIcon}
              variant="primary"
              onClick={onSubmitAddCustomOrderItem}
            >
              Add Denomination
            </Button>
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
              isDisabled={hasDuplicateEntries}
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
        {(errorText || isError || isErrorOrderItemPatchMutation || hasDuplicateEntries) && (
          <Box paddingX="spacing.2">
            <ErrorText>
              {hasDuplicateEntries
                ? 'Denominations must be distinct'
                : /* @ts-expect-error error-message-check */
                  errorText || error?.message || errorOrderItemPatchMutation?.message}
            </ErrorText>
          </Box>
        )}
      </ModalFooter>
    </Modal>
  );
};

export default OrderCreateProgramDenominationsModal;
