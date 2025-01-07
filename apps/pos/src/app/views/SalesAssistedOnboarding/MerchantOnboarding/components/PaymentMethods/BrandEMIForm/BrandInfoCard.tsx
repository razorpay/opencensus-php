import React from 'react';
import {
  Alert,
  AlertCircleIcon,
  AlertTriangleIcon,
  Badge,
  Box,
  CheckCircleIcon,
  ClockIcon,
  IconButton,
  IconComponent,
  Text,
  TrashIcon,
} from '@razorpay/blade/components';
import { BRAND_EMI_VERIFICATION_STATUS_ENUM } from 'apps/pos/src/app/types/modular';
import { Brand } from 'apps/pos/src/app/utils/paymentsAndServices';
import { FeedbackColors } from 'apps/pos/src/app/types/AgreementSigning';

interface BrandInfoCard {
  brand: Brand;
  isUpdateModularLoading: boolean;
  isFormDisabled: boolean;
  removeBrandHandler: (brandName: string) => void;
}

const getBadgeDetails = (
  status: BRAND_EMI_VERIFICATION_STATUS_ENUM,
): {
  badgeText: string;
  color: FeedbackColors;
  icon?: IconComponent;
} => {
  switch (status) {
    case BRAND_EMI_VERIFICATION_STATUS_ENUM.VERIFIED:
      return {
        badgeText: 'Validated',
        color: 'positive',
        icon: CheckCircleIcon,
      };
    case BRAND_EMI_VERIFICATION_STATUS_ENUM.FAILED:
      return {
        badgeText: 'Failed',
        color: 'negative',
        icon: AlertTriangleIcon,
      };
    case BRAND_EMI_VERIFICATION_STATUS_ENUM.PENDING:
      return {
        badgeText: 'Pending validation',
        color: 'notice',
        icon: ClockIcon,
      };
    default:
      return {
        badgeText: 'Unknown status',
        color: 'neutral',
        icon: undefined,
      };
  }
};

const BrandInfoCard = ({
  isUpdateModularLoading,
  removeBrandHandler,
  brand,
  isFormDisabled,
}: BrandInfoCard) => {
  if (!brand) return null;

  const badgeDetails = getBadgeDetails(brand.verificationStatus);
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
        <Badge color={badgeDetails.color} size="large" icon={badgeDetails.icon}>
          {badgeDetails.badgeText}
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
        {brand.verificationStatus === BRAND_EMI_VERIFICATION_STATUS_ENUM.FAILED && (
          <Alert
            color="negative"
            description="Records will be validated manually"
            emphasis="subtle"
            isDismissible={false}
            title="Auto-verification failed"
            icon={AlertCircleIcon}
          />
        )}
      </Box>
    </Box>
  );
};

export default BrandInfoCard;
