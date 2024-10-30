import React from 'react';
import {
  Badge,
  BadgeProps,
  Box,
  ClockIcon,
  IconButton,
  Text,
  TrashIcon,
} from '@razorpay/blade/components';
import { BRAND_EMI_VERIFICATION_STATUS_ENUM } from 'apps/pos/src/app/types/modular';
import { Brand } from 'apps/pos/src/app/utils/paymentsAndServices';

interface BrandInfoCard {
  brand: Brand;
  isUpdateModularLoading: boolean;
  isFormDisabled: boolean;
  removeBrandHandler: (brandName: string) => void;
}
const BrandInfoCard = ({
  isUpdateModularLoading,
  removeBrandHandler,
  brand,
  isFormDisabled,
}: BrandInfoCard) => {
  if (!brand) return null;

  const badgeColorMap: Record<BRAND_EMI_VERIFICATION_STATUS_ENUM, BadgeProps['color']> = {
    [BRAND_EMI_VERIFICATION_STATUS_ENUM.VERIFIED]: 'positive',
    [BRAND_EMI_VERIFICATION_STATUS_ENUM.FAILED]: 'negative',
    [BRAND_EMI_VERIFICATION_STATUS_ENUM.PENDING]: 'notice',
  };
  const badgeTextMap = {
    [BRAND_EMI_VERIFICATION_STATUS_ENUM.VERIFIED]: 'Validated',
    [BRAND_EMI_VERIFICATION_STATUS_ENUM.FAILED]: 'Failed',
    [BRAND_EMI_VERIFICATION_STATUS_ENUM.PENDING]: 'Pending manual validation',
  };
  return (
    <Box
      borderRadius="medium"
      backgroundColor="surface.background.gray.moderate"
      padding={['spacing.7', 'spacing.6', 'spacing.7', 'spacing.6']}
    >
      <Box
        display="flex"
        justifyContent="space-between"
        alignItems="center"
        marginBottom="spacing.6"
      >
        <Badge color={badgeColorMap[brand.verificationStatus]} size="large" icon={ClockIcon}>
          {badgeTextMap[brand.verificationStatus]}
        </Badge>
        <IconButton
          size="large"
          onClick={() => removeBrandHandler(brand.name)}
          accessibilityLabel="remove-icon"
          icon={TrashIcon}
          isDisabled={isFormDisabled || isUpdateModularLoading}
        />
      </Box>
      <Box>
        {!!brand.name && (
          <Box marginBottom="spacing.4" display="flex" alignItems="center" gap="spacing.5">
            <Text color="surface.text.gray.subtle">Brand Name</Text>
            <Text color="surface.text.gray.subtle" weight="semibold">
              {brand.label}
            </Text>
          </Box>
        )}
        {!!brand.dealerCode && (
          <Box marginBottom="spacing.4" display="flex" alignItems="center" gap="spacing.5">
            <Text color="surface.text.gray.subtle">Dealer Code</Text>
            <Text color="surface.text.gray.subtle" weight="semibold">
              {brand.dealerCode}
            </Text>
          </Box>
        )}
        {!!brand.stateCode && (
          <Box marginBottom="spacing.4" display="flex" alignItems="center" gap="spacing.5">
            <Text color="surface.text.gray.subtle">State Code</Text>
            <Text color="surface.text.gray.subtle" weight="semibold">
              {brand.stateCode}
            </Text>
          </Box>
        )}
        {!!brand.distributorCode && (
          <Box marginBottom="spacing.4" display="flex" alignItems="center" gap="spacing.5">
            <Text color="surface.text.gray.subtle">Distributor Code</Text>
            <Text color="surface.text.gray.subtle" weight="semibold">
              {brand.distributorCode}
            </Text>
          </Box>
        )}
        {!!brand.merchantGst && (
          <Box marginBottom="spacing.4" display="flex" alignItems="center" gap="spacing.5">
            <Text color="surface.text.gray.subtle">GST number</Text>
            <Text color="surface.text.gray.subtle" weight="semibold">
              {brand.merchantGst}
            </Text>
          </Box>
        )}
      </Box>
    </Box>
  );
};

export default BrandInfoCard;
