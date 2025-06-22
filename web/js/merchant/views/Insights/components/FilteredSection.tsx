import React, { useCallback } from 'react';
import {
  Box,
  Button,
  Heading,
  RefreshIcon,
  Link,
  ExternalLinkIcon,
} from '@razorpay/blade/components';
import {
  renderDashboardIcon,
  getDisplayName,
  getDocumentationUrl,
  trackDocumentationClick,
} from 'merchant/views/Insights/utils/renderUtils';

const FilteredSection = ({
  activeTab,
  trackAnalytics,
  embedSupersetDashboard,
  refetch,
  setGuestToken,
}) => {
  const handleRefreshClick = async () => {
    try {
      const { data: updatedGuestToken } = await refetch();
      trackAnalytics('Refresh Button Clicked');
      setGuestToken(updatedGuestToken);
      embedSupersetDashboard(updatedGuestToken);
    } catch (error) {
      trackAnalytics('Refresh Button Error', {
        error: error instanceof Error ? error.message : 'Unknown error',
      });
    }
  };

  return (
    <Box
      display="flex"
      flexWrap="wrap"
      justifyContent="space-between"
      alignItems="center"
      gap="spacing.4"
    >
      <Box display="flex" alignItems="center">
        {renderDashboardIcon(activeTab)}
        <Heading
          color="surface.text.gray.normal"
          size="large"
          weight="semibold"
          marginRight="spacing.5"
        >
          {getDisplayName(activeTab)}
        </Heading>
        <Button
          icon={RefreshIcon}
          color="primary"
          variant="tertiary"
          aria-label="Refresh"
          onClick={handleRefreshClick}
        >
          Refresh
        </Button>
      </Box>
      <Box display="flex" gap="spacing.5">
        <Box gap="spacing.5" display="flex" justifyContent="center" alignItems="center">
          <Link
            href={getDocumentationUrl(activeTab)}
            target="_blank"
            rel="noopener noreferrer"
            color="primary"
            onClick={() => trackDocumentationClick(activeTab, trackAnalytics)}
            icon={ExternalLinkIcon}
          >
            View Documentation
          </Link>
        </Box>
      </Box>
    </Box>
  );
};

export default FilteredSection;
