import React, { Suspense, lazy, useContext, useState } from 'react';
import {
  Box,
  Button,
  CloseIcon,
  EditComposeIcon,
  Link,
  PlusIcon,
  RotateCounterClockWiseIcon,
  Table,
  TableBody,
  TableCell,
  TableHeader,
  TableHeaderCell,
  TableHeaderRow,
  TableNode,
  TableRow,
  Text,
} from '@razorpay/blade/components';
import { PartialCODContext } from 'merchant/views/MagicCheckout/PartialCOD/context/PartialCODContext';
import {
  PARTIAL_COD_TYPE,
  PREPAID_PAYMENY_AMOUNT_ITEM_TYPE,
  PrepaidPaymentAmountItem,
} from 'merchant/views/MagicCheckout/PartialCOD/types';
import { getSlabCustomerRiskText } from 'merchant/views/MagicCheckout/PartialCOD/helpers/utils';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';

const ResetSlabsConfirmModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicPartialCODResetSlabsConfirmModal' */ 'merchant/views/MagicCheckout/PartialCOD/components/ResetSlabsConfirmModal'
    ),
);

const AdvancedSlabModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicPartialCODAdvancedSlabModal' */ 'merchant/views/MagicCheckout/PartialCOD/components/AdvancedSlabModal'
    ),
);

const AdvancedSlabConfig = () => {
  const { configsToShow, handleRemoveSlab, handleAddSlab, handleUpdateSlabType, handleUpdateSlab } =
    useContext(PartialCODContext);

  const [isResetModalOpen, setIsResetModalOpen] = useState(false);
  const [isAdvanceModalOpen, setIsAdvanceModalOpen] = useState(false);
  const [advanceModalType, setAdvanceModalType] = useState<number | null | 'create'>(null);

  const handleCreateNewSlab = () => {
    setAdvanceModalType('create');
    setIsAdvanceModalOpen(true);
  };

  const handleDeleteAllSlabs = () => setIsResetModalOpen(true);

  const handleEditSlab = (index: number) => {
    setAdvanceModalType(index);
    setIsAdvanceModalOpen(true);
  };

  return (
    <>
      <Box
        borderRadius="medium"
        borderColor="surface.border.gray.muted"
        borderWidth="thin"
        padding="spacing.8"
        backgroundColor="surface.background.gray.intense"
        marginTop="spacing.8"
      >
        <Box display="grid" gap="spacing.8">
          <Box display="flex" alignItems="center" justifyContent="space-between">
            <Text color="surface.text.gray.normal" weight="medium" size="large">
              Advanced partial COD slabs
            </Text>
            <Button variant="primary" icon={PlusIcon} onClick={handleCreateNewSlab}>
              New Slab
            </Button>
          </Box>
          <div>
            <Table
              data={{ nodes: configsToShow as TableNode<PrepaidPaymentAmountItem>[] }}
              gridTemplateColumns="4fr 2fr 1fr 1fr"
              display="grid"
            >
              {(tableData) => (
                <>
                  <TableHeader>
                    <TableHeaderRow>
                      <TableHeaderCell>Rule</TableHeaderCell>
                      <TableHeaderCell>Customer Risk</TableHeaderCell>
                      <TableHeaderCell>Partial Amount</TableHeaderCell>
                      <TableHeaderCell>
                        <Box display="flex" justifyContent="flex-end" width="100%">
                          <Text weight="medium">Actions</Text>
                        </Box>
                      </TableHeaderCell>
                    </TableHeaderRow>
                  </TableHeader>
                  <TableBody>
                    {tableData.map((tableItem, index) => (
                      <TableRow key={index} item={tableItem}>
                        <TableCell>
                          <div>
                            If amount is between ₹
                            {i18nifyConvertToMajorUnit(tableItem.rules.min_order_amount)} and ₹
                            {tableItem.rules.max_order_amount &&
                              i18nifyConvertToMajorUnit(tableItem.rules.max_order_amount)}
                          </div>
                        </TableCell>
                        <TableCell>
                          {getSlabCustomerRiskText(tableItem.rules.customer_risk_category)}
                        </TableCell>
                        <TableCell>
                          {tableItem?.type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
                            ? `₹${i18nifyConvertToMajorUnit(tableItem.value)}`
                            : `${tableItem.value}%`}
                        </TableCell>
                        <TableCell>
                          <Box
                            gap="spacing.4"
                            display="flex"
                            justifyContent="flex-end"
                            width="100%"
                          >
                            <Link
                              icon={EditComposeIcon}
                              onClick={() => handleEditSlab(index)}
                              variant="button"
                              size="large"
                            />
                            <Link
                              icon={CloseIcon}
                              onClick={() =>
                                tableData?.length === 1
                                  ? handleDeleteAllSlabs()
                                  : handleRemoveSlab(index)
                              }
                              variant="button"
                              size="large"
                            />
                          </Box>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </>
              )}
            </Table>
            {!!!configsToShow.length && (
              <Box
                height="48px"
                display="grid"
                alignItems="center"
                textAlign="center"
                borderLeftWidth="thin"
                borderRightWidth="thin"
                borderBottomWidth="thin"
                borderColor="surface.border.gray.muted"
              >
                No Advance slab created
              </Box>
            )}
          </div>
          <Box display="flex">
            <Link onClick={handleDeleteAllSlabs} icon={RotateCounterClockWiseIcon} variant="button">
              Delete and reset slabs
            </Link>
          </Box>
        </Box>
      </Box>
      <Suspense fallback={null}>
        <ResetSlabsConfirmModal
          isOpen={isResetModalOpen}
          onClose={() => setIsResetModalOpen(false)}
          onConfirm={(callbacks) => handleUpdateSlabType(PARTIAL_COD_TYPE.BASIC, callbacks)}
        />
      </Suspense>
      <Suspense fallback={null}>
        <AdvancedSlabModal
          configData={
            typeof advanceModalType === 'number' ? configsToShow[advanceModalType] : undefined
          }
          onConfirm={(data, callbacks) => {
            if (advanceModalType === 'create') {
              handleAddSlab(data, callbacks);
            } else if (typeof advanceModalType === 'number') {
              handleUpdateSlab(advanceModalType, data, callbacks);
            }
          }}
          isOpen={isAdvanceModalOpen}
          onClose={() => {
            setIsAdvanceModalOpen(false);
            setAdvanceModalType(null);
          }}
        />
      </Suspense>
    </>
  );
};

export default AdvancedSlabConfig;
