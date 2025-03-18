import React, { useEffect } from 'react';
import { Box, Text, IconButton, CloseIcon } from '@razorpay/blade/components';
import { withRouter } from '@libs/web-nexus/common/deprecated/withRouter';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';
import { TemplateSelectionProps } from './types';
import { PAGE_CONFIGS, PAYMENT_PAGES_TYPES } from './PageConfig';
import { PageCard } from './PageComponents';

const TemplateSelectionV2: React.FC<TemplateSelectionProps> = ({
  handlePageType,
  isMobile,
  history,
}) => {
  useEffect(() => {
    track.selectTemplatePageLoaded();
  }, []);

  const handleClose = () => {
    history.push('/paymentpages');
  };

  const handleCreatePaymentPage = () => {
    handlePageType(PAYMENT_PAGES_TYPES.payment_page);
    analyticsTrack({
      objectName: 'Payment page',
      actionName: 'clicked',
      screen: 'Select page of your choice',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...PAGE_CONFIGS[PAYMENT_PAGES_TYPES.payment_page].analyticsData,
      },
    });
  };

  const handleCreateStorefrontPage = () => {
    handlePageType(PAYMENT_PAGES_TYPES.storefront);
    track.selectStorefrontPage(PAGE_CONFIGS[PAYMENT_PAGES_TYPES.storefront].analyticsData);
  };

  return (
    <Box width="100vw" height="100vh" overflow="scroll" padding={isMobile ? "spacing.4" :"spacing.8"}>
      <Box
        display="flex"
        justifyContent="space-between"
        alignItems="center"
        marginBottom={isMobile ? "spacing.5" : "spacing.8"}
      >
        <Text size="large" weight="semibold" variant="body" color="surface.text.gray.subtle">
          Select a page according to your needs
        </Text>
        <IconButton
          icon={CloseIcon}
          size="large"
          onClick={handleClose}
          accessibilityLabel="header-back-btn"
        />
      </Box>
      <Box
        display="flex"
        alignItems="center"
        gap="spacing.8"
        flexDirection={isMobile ? 'column' : 'row'}
      >
        <PageCard
          type={PAYMENT_PAGES_TYPES.storefront}
          config={PAGE_CONFIGS[PAYMENT_PAGES_TYPES.storefront]}
          onCreateClick={handleCreateStorefrontPage}
          isMobile={isMobile}
        />
        <PageCard
          type={PAYMENT_PAGES_TYPES.payment_page}
          config={PAGE_CONFIGS[PAYMENT_PAGES_TYPES.payment_page]}
          onCreateClick={handleCreatePaymentPage}
          isMobile={isMobile}
        />
      </Box>
    </Box>
  );
};

export default withRouter(TemplateSelectionV2);
