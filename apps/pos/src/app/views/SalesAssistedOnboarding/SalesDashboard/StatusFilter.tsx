import React from 'react';
import { Box, ChipGroup, Chip } from '@razorpay/blade/components';
import {
  STATUS_FILTERS as STATUS_FILTERS_TYPE,
  StatusCounts,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { StatusTiles } from 'apps/pos/src/app/constants/SalesAssistedOnboarding';
import useOnboardingStore from 'apps/pos/src/bootstrap/Store';
import { ActivationStatusKeys } from 'apps/pos/src/app/types/common';
import { useMobile } from '@libs/shared-utils';

interface StatusFilter {
  label: string;
  value: STATUS_FILTERS_TYPE;
  icon?: unknown;
}

interface StatusFilterProps {
  defaultValue: STATUS_FILTERS_TYPE;
  value: STATUS_FILTERS_TYPE;
  statusCounts: StatusCounts;
  onChange?: (status: STATUS_FILTERS_TYPE) => void;
}

const filterMap: Record<ActivationStatusKeys, STATUS_FILTERS_TYPE> = {
  activated: 'activated',
  kycQualifiedStb: 'kyc_qualified_stb',
  underReview: 'under_review',
  pending: 'pending',
  rejected: 'rejected',
  needsClarification: 'needs_clarification',
  pricingNeedsClarification: 'pricing_needs_clarification',
};

const StatusFilter = ({ defaultValue, value, statusCounts }: StatusFilterProps): JSX.Element => {
  const { filters, setFilters } = useOnboardingStore();
  const isMobile = useMobile();
  return (
    <Box marginBottom="spacing.3">
      <ChipGroup
        accessibilityLabel="KYC activation status chips"
        onChange={(data) => {
          setFilters({ ...filters, activationStatus: data.values[0] as STATUS_FILTERS_TYPE });
        }}
        selectionType="single"
        size={isMobile ? 'xsmall' : 'small'}
        value={value}
        defaultValue={defaultValue}
      >
        {StatusTiles.map(({ name, key }) => (
          <Box key={key} testID={`${key}-sales-count-field`}>
            <Chip testID={key} value={filterMap[key]}>
              {name} - {statusCounts?.[key] ?? 0}
            </Chip>
          </Box>
        ))}
      </ChipGroup>
    </Box>
  );
};

export default StatusFilter;
