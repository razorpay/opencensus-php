import React, { useContext, useEffect, useState } from 'react';
import {
  Alert,
  Box,
  Button,
  InfoIcon,
  Link,
  ListIcon,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import BasicSlabChips from 'merchant/views/MagicCheckout/PartialCOD/components/BasicSlabChips';
import RiskCategoryDropdown from 'merchant/views/MagicCheckout/PartialCOD/components/RiskCategoryDropdown';
import { PartialCODContext } from 'merchant/views/MagicCheckout/PartialCOD/context/PartialCODContext';
import {
  PARTIAL_COD_TYPE,
  PrepaidPaymentAmountItem,
} from 'merchant/views/MagicCheckout/PartialCOD/types';
import { NEW_PREPAID_AMOUNT_ITEM } from 'merchant/views/MagicCheckout/PartialCOD/constants';

const partialCODCheckoutImg = require('assets/partial-cod/partial-cod-checkout-view.svg');

const BasicSlabConfig: React.FC = () => {
  const { configsToShow, handleUpdateSlabType, handleUpdateSlab } = useContext(PartialCODContext);

  const [activeBasicSlab, setActiveBasicSlab] =
    useState<PrepaidPaymentAmountItem>(NEW_PREPAID_AMOUNT_ITEM);

  useEffect(() => {
    if (configsToShow[0]) setActiveBasicSlab(configsToShow[0]);
  }, [configsToShow]);

  const [loading, setLoading] = useState(false);

  const handleSave = () => {
    setLoading(true);
    handleUpdateSlab(0, activeBasicSlab, {
      onEnd: () => setLoading(false),
    });
  };

  return (
    <Box
      borderRadius="small"
      borderColor="surface.border.gray.muted"
      borderWidth="thin"
      padding="spacing.8"
      backgroundColor="surface.background.gray.intense"
      display="grid"
      gridTemplateColumns={{ base: '1fr', xl: 'repeat(2, 1fr)' }}
      gap="spacing.8"
      marginTop="spacing.8"
    >
      <Box display="flex" flexDirection="column" gap="spacing.7">
        <Box display="grid" gap="spacing.4">
          <Box display="flex" alignItems="center" justifyContent="space-between">
            <Text weight="semibold">Partial COD enabled for</Text>
            <Tooltip
              content="Medium and high-risk buyers can't place COD orders unless a partial COD amount is set for them, due to higher cancellation risks."
              placement="right"
            >
              <TooltipInteractiveWrapper>
                <Box display="flex" alignItems="center">
                  <InfoIcon color="surface.icon.gray.muted" />
                </Box>
              </TooltipInteractiveWrapper>
            </Tooltip>
          </Box>
          <RiskCategoryDropdown
            value={activeBasicSlab?.rules.customer_risk_category}
            onChange={(val) =>
              setActiveBasicSlab((prev) => ({
                ...prev,
                rules: {
                  ...prev.rules,
                  customer_risk_category: val,
                },
              }))
            }
          />
        </Box>

        <Box display="grid" gap="spacing.4">
          <Text weight="semibold">Partial amount to be paid</Text>
          <BasicSlabChips
            activeBasicSlab={activeBasicSlab}
            setActiveBasicSlab={setActiveBasicSlab}
          />
        </Box>
        <Alert
          color="information"
          description="This pre-pay amount will never be higher than 50% of order value. In such cases, it will be set to 50%."
          emphasis="subtle"
          isDismissible={false}
        />
        <Box display="flex" justifyContent="space-between">
          <Box display="flex" alignItems="center">
            <Link
              onClick={() => handleUpdateSlabType(PARTIAL_COD_TYPE.ADVANCED)}
              variant="button"
              icon={ListIcon}
            >
              Set advanced slabs
            </Link>
            <Tooltip content="Use this option to set price slabs" placement="right">
              <TooltipInteractiveWrapper>
                <Box display="flex" alignItems="center">
                  <InfoIcon color="interactive.icon.primary.normal" marginLeft="spacing.2" />
                </Box>
              </TooltipInteractiveWrapper>
            </Tooltip>
          </Box>
          {!!activeBasicSlab.value && activeBasicSlab.rules.customer_risk_category.length > 0 && (
            <Button variant="primary" onClick={handleSave} isLoading={loading}>
              Save
            </Button>
          )}
        </Box>
      </Box>
      <Box
        height="382px"
        backgroundColor="surface.background.gray.subtle"
        display="flex"
        justifyContent="center"
        alignItems="center"
        borderRadius="medium"
      >
        <img src={partialCODCheckoutImg} alt="partial-cod-checkout-view" loading="lazy" />
      </Box>
    </Box>
  );
};

export default BasicSlabConfig;
