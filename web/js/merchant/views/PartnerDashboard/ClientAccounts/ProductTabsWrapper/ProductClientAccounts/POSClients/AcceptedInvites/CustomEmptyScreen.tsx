import React from 'react';
import { SearchIcon, Box, Heading, Text } from '@razorpay/blade/components';

type CustomEmptyScreenProps = {
  isFilterSearchUsed: boolean;
};
const CustomEmptyScreen = ({ isFilterSearchUsed }: CustomEmptyScreenProps): JSX.Element => {
  return (
    <Box paddingTop="140px" paddingBottom="140px">
      {isFilterSearchUsed ? (
        <Box display="flex" flexDirection="column" gap="spacing.5" alignItems="center">
          <SearchIcon size="medium" color="surface.action.icon.default.lowContrast" />
          <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="center">
            <Heading size="medium">No Results Found</Heading>
            <Text>Try adjusting your search or filter to find what you&apos;re looking for</Text>
          </Box>
        </Box>
      ) : (
        <Box display="flex" flexDirection="column" gap="spacing.5" alignItems="center">
          <Heading size="large">No Accepted Invites</Heading>
          <Box
            display="flex"
            flexDirection="column"
            gap="spacing.2"
            justifyContent="center"
            alignItems="center"
            padding="spacing.5"
            backgroundColor="surface.background.level1.lowContrast"
          >
            <Text weight="bold" size="large" color="feedback.text.notice.lowContrast">
              Did you know?
            </Text>
            {/* eslint-disable-next-line i18n-rules/no-region-specific-keyword */}
            <Text>
              You can now opt in to perform KYC for the client when you invite them onto Razorpay
            </Text>
          </Box>
        </Box>
      )}
    </Box>
  );
};
export default CustomEmptyScreen;
