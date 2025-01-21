import React, { useEffect, useMemo } from 'react';
import { Box, Button, Heading, Text } from '@razorpay/blade/components';

import { CardDetails } from './EditableCard';
import { PolicyPageContent, getReviewPagesData, isPolicyPageCreatedByRazorpay } from './utils';
import useBusinessWebsiteData from '../hooks/useBusinessWebsiteData';
import useModalComponents from '../hooks/useModalComponents';
import { track } from '../tracking';
import { WebsitePolicyPages } from '../types';
import { getWebsiteCount, snapPoints } from '../utils';
import CompletedPagesIllustration from './assets/completedPolicyPages.svg';

export interface PolicyPagesCompleteProps {
  isOpen: boolean;
  isMobile: boolean;
  onDismiss: () => void;
  pagesBeingVerified: Array<WebsitePolicyPages>;
}

function CompletedPolicyPages({
  isOpen,
  isMobile,
  onDismiss,
  pagesBeingVerified,
}: PolicyPagesCompleteProps): JSX.Element {
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);
  const { websiteUpdateData: { website_verification_page_status } = {} } = useBusinessWebsiteData();

  const { missingPages, missingPagesKeys } = useMemo(
    () => getReviewPagesData(website_verification_page_status, pagesBeingVerified),
    [website_verification_page_status, pagesBeingVerified],
  );

  useEffect(() => {
    if (isOpen) {
      track({
        objectName: 'Create Website Pages Confirm and Publish',
        properties: {
          policyPageCountTotal: missingPagesKeys.length,
          policyPageRZPCreate: missingPagesKeys.filter((pageKey) =>
            isPolicyPageCreatedByRazorpay(missingPages[pageKey].value),
          ).length,
          websiteCount: getWebsiteCount(window.rzp_user),
        },
      });
    }
  }, [isOpen]);

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} snapPoints={snapPoints} size="large">
      <ModalHeader />
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Box display="flex" flexDirection="row" minHeight={isMobile ? 'none' : '509px'}>
          <Box
            paddingX={isMobile ? 'none' : 'spacing.8'}
            paddingY={isMobile ? 'none' : 'spacing.7'}
            flex="2"
          >
            <Box display="flex" flexDirection="column" flex="2" gap="spacing.8">
              <Box
                display="flex"
                flexDirection="column"
                gap={isMobile ? 'spacing.4' : 'spacing.7'}
                testID="completed-pages"
              >
                <Box>
                  <Heading size="small" weight="semibold">
                    You are all done! 🙌
                  </Heading>
                  <Text size="medium" color="surface.text.gray.muted">
                    Please find the links to your policy pages below.
                  </Text>
                </Box>
                {missingPagesKeys.map((page) => {
                  const { Icon, title } = PolicyPageContent[page];
                  const { value } = missingPages[page];
                  return <CardDetails Icon={Icon} key={page} title={title} value={value} />;
                })}
              </Box>
            </Box>
          </Box>
          {!isMobile && (
            <Box
              backgroundColor="surface.background.sea.subtle"
              flex="1"
              display="flex"
              position="sticky"
              top="spacing.0"
              flexDirection="column"
              gap="spacing.3"
              alignItems="center"
              justifyContent="flex-end"
              padding="spacing.0"
              borderTopRightRadius="large"
            >
              <Box>
                <img src={CompletedPagesIllustration} />
              </Box>
            </Box>
          )}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button isFullWidth={isMobile} onClick={onDismiss}>
            Done
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}

export default CompletedPolicyPages;
