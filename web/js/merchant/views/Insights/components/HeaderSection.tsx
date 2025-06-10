import React from 'react';
import { Box, Heading, Link } from '@razorpay/blade/components';
import { DOCUMENTATION_ROUTES } from 'merchant/views/Insights/constants';

const HeaderSection = ({ Insights_Dashboard, activeTab, trackAnalytics }) => {
  const getDocumentationUrl = (activeTab) => {
    return DOCUMENTATION_ROUTES[activeTab];
  };

  const trackDocumentationClick = (activeTab) => {
    const documentationUrl = DOCUMENTATION_ROUTES[activeTab];
    trackAnalytics(`${activeTab} Documentation Clicked`, {
      documentation_link: documentationUrl,
    });
    return documentationUrl;
  };

  const displayTabName = activeTab === 'MagicX' ? 'Magic' : activeTab;

  return (
    <Box
      display="flex"
      flexWrap="wrap"
      paddingY="spacing.7"
      justifyContent="space-between"
      alignItems="center"
    >
      <Heading color="surface.text.staticBlack.subtle" size="2xlarge" weight="semibold">
        {Insights_Dashboard} - {displayTabName}
      </Heading>
      <Box gap="spacing.5" display="flex" justifyContent="center" alignItems="center">
        <Link
          href={getDocumentationUrl(activeTab)}
          target="_blank"
          rel="noopener noreferrer"
          color="primary"
          onClick={() => trackDocumentationClick(activeTab)}
        >
          Documentation
        </Link>
      </Box>
    </Box>
  );
};

export default HeaderSection;
