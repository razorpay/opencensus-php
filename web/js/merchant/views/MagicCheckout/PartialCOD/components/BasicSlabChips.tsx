import React, { Suspense, lazy, useEffect, useState } from 'react';
import { Box, Chip, ChipGroup, EditInlineIcon } from '@razorpay/blade/components';
import {
  BasicSlabChipsProps,
  PREPAID_PAYMENY_AMOUNT_ITEM_TYPE,
  PrepaidPaymentAmountItem,
  PrepaidPaymentAmountItemType,
} from 'merchant/views/MagicCheckout/PartialCOD/types';
import {
  i18nifyConvertToMajorUnit,
  i18nifyConvertToMinorUnit,
} from 'merchant/views/Transactions/v2/common/utils';
import { DEFAULT_BASIC_SLAB_VALUES } from '../constants';

const BasicSlabModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicPartialCODBasicSlabModal' */ 'merchant/views/MagicCheckout/PartialCOD/components/BasicSlabModal'
    ),
);

const BasicSlabChips: React.FC<BasicSlabChipsProps> = ({ activeBasicSlab, setActiveBasicSlab }) => {
  const [isCustomValue, setIsCustomValue] = useState(false);
  const [isBasicModalOpen, setIsBasicModalOpen] = useState(false);

  useEffect(() => {
    // to check if the merchant has set a custom value other than the DEFAULT_BASIC_SLAB_VALUES
    if (
      activeBasicSlab.value &&
      !DEFAULT_BASIC_SLAB_VALUES.some(
        (slab) =>
          slab.value === Number(activeBasicSlab.value) && slab.type === activeBasicSlab.type,
      )
    )
      setIsCustomValue(true);
    else setIsCustomValue(false);
  }, [activeBasicSlab]);

  const updateSlab = (type: PrepaidPaymentAmountItemType, value: number) => {
    setActiveBasicSlab((prev) => ({ ...prev, type, value }));
  };

  const handleChipChange = ({ values }: { values: string[] }) => {
    const selectedValue = values[0];
    if (selectedValue === 'customValue') {
      // Custom value selection - opens modal
      setIsBasicModalOpen(true);
    } else if (+selectedValue) {
      // Flat value selection
      updateSlab(PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT, i18nifyConvertToMinorUnit(+selectedValue));
    } else if (selectedValue.includes('%')) {
      // Percentage value selection
      updateSlab(PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE, +selectedValue.replace('%', ''));
    }
  };

  const getSelectedChipValue = (): string => {
    if (isCustomValue) return 'custom';
    if (activeBasicSlab?.value) {
      return activeBasicSlab.type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
        ? String(i18nifyConvertToMajorUnit(activeBasicSlab.value))
        : `${activeBasicSlab.value}%`;
    }

    return '';
  };

  const getCustomChipValue = (): string => {
    if (isCustomValue && activeBasicSlab?.value)
      return activeBasicSlab.type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
        ? `(₹${i18nifyConvertToMajorUnit(activeBasicSlab.value)})`
        : `(${activeBasicSlab.value}%)`;
    return '';
  };
  return (
    <>
      <ChipGroup
        accessibilityLabel="Choose one business type from the options below"
        onChange={handleChipChange}
        value={getSelectedChipValue()}
        selectionType="single"
      >
        {DEFAULT_BASIC_SLAB_VALUES.map((slab) => (
          <Chip
            key={`${slab.type}-${slab.value}`}
            value={
              slab.type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
                ? String(i18nifyConvertToMajorUnit(slab.value))
                : `${slab.value}%`
            }
          >
            {slab.type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
              ? `₹ ${i18nifyConvertToMajorUnit(slab.value)}`
              : `${slab.value}%`}
          </Chip>
        ))}
        <Chip value="custom">
          {/* @ts-ignore */}
          <div onClick={() => handleChipChange({ values: ['customValue'] })}>
            <Box display="flex" alignItems="center" gap="spacing.2">
              <EditInlineIcon
                color={isCustomValue ? 'interactive.icon.primary.normal' : undefined}
              />
              Custom Value {getCustomChipValue()}
            </Box>
          </div>
        </Chip>
      </ChipGroup>
      <Suspense fallback={null}>
        <BasicSlabModal
          isOpen={isBasicModalOpen}
          onClose={() => setIsBasicModalOpen(false)}
          onSave={(value, type) => {
            setIsCustomValue(true);
            setActiveBasicSlab((prev) => ({ ...prev, type, value } as PrepaidPaymentAmountItem));
          }}
        />
      </Suspense>
    </>
  );
};

export default BasicSlabChips;
