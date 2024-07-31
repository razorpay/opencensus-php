import React from 'react';

import {
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  BottomSheetBody,
  BottomSheet,
  BottomSheetHeader,
} from '@razorpay/blade/components';
import { STATUS_FILTERS as STATUS_FILTERS_TYPE } from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';

interface StatusFilter {
  label: string;
  value: STATUS_FILTERS_TYPE;
  icon?: unknown;
}

interface StatusFilterProps {
  defaultValue: STATUS_FILTERS_TYPE;
  value: STATUS_FILTERS_TYPE;
  onChange: (status: STATUS_FILTERS_TYPE) => void;
}

const STATUS_FILTERS: StatusFilter[] = [
  {
    label: 'All',
    value: 'all',
  },
  {
    label: 'Activated',
    value: 'activated',
  },
  {
    label: 'KYC Qualified',
    value: 'kyc_qualified_stb',
  },
  {
    label: 'Under Review',
    value: 'under_review',
  },
  {
    label: 'Pending',
    value: 'pending',
  },
  {
    label: 'Rejected',
    value: 'rejected',
  },
  {
    label: 'Needs Clarification',
    value: 'needs_clarification',
  },
];

const StatusFilter = ({ defaultValue, value, onChange }: StatusFilterProps): JSX.Element => {
  const { isMobile } = useScreen();

  const renderBody = () => (
    <ActionList>
      {STATUS_FILTERS.map(({ label, value }) => (
        <ActionListItem key={value} title={label} value={value} />
      ))}
    </ActionList>
  );

  return (
    <Dropdown testID="sales-dashboard-filters">
      <SelectInput
        label="Status"
        name="status"
        value={value}
        defaultValue={defaultValue}
        onChange={({ values }) => {
          onChange?.(values[0] as STATUS_FILTERS_TYPE);
        }}
      />
      {isMobile ? (
        <BottomSheet snapPoints={[0.5, 0.8, 1]}>
          <BottomSheetHeader title="Status" />
          <BottomSheetBody>{renderBody()}</BottomSheetBody>
        </BottomSheet>
      ) : (
        <DropdownOverlay>{renderBody()}</DropdownOverlay>
      )}
    </Dropdown>
  );
};

export default StatusFilter;
