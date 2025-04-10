import React from 'react';
import { Box, Button, RefreshIcon } from '@razorpay/blade/components';
import { DateRangePicker } from 'merchant/views/Insights/DateRangePicker';

const FilteredSection = ({
  selectedDateCallback,
  trackAnalytics,
  embedSupersetDashboard,
  refetch,
  setGuestToken,
}) => {
  const handleRefreshClick = async () => {
    const { data: updatedGuestToken } = await refetch();
    trackAnalytics('Refresh Button Clicked');
    setGuestToken(updatedGuestToken);
    embedSupersetDashboard(updatedGuestToken);
  };
  return (
    <Box display="flex" flexWrap="wrap" gap="spacing.4" paddingBottom="spacing.6">
      <Box display="flex" alignItems="center" justifyContent="flex-start">
        <DateRangePicker selectedDateCallback={selectedDateCallback} />
      </Box>
      <Box display="flex" alignItems="flex-end">
        <Button
          marginRight="spacing.3"
          icon={RefreshIcon}
          color="primary"
          variant="tertiary"
          aria-label="Refresh"
          onClick={handleRefreshClick}
        />
      </Box>
    </Box>
  );
};

export default FilteredSection;
