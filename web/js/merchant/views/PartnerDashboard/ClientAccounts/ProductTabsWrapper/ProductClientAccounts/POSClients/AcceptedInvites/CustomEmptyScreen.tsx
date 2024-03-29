import React from 'react';
import { Box, Heading, SearchIcon, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

type CustomEmptyScreenProps = {
  org: Org;
  isFilterSearchUsed: boolean;
};
const CustomEmptyScreen = ({ org, isFilterSearchUsed }: CustomEmptyScreenProps): JSX.Element => {
  return (
    <Box paddingTop="140px" paddingBottom="140px">
      {isFilterSearchUsed ? (
        <Box display="flex" flexDirection="column" gap="spacing.5" alignItems="center">
          <SearchIcon size="medium" color="interactive.icon.gray.normal" />
          <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="center">
            <Heading size="small">No Results Found</Heading>
            <Text>Try adjusting your search or filter to find what you&apos;re looking for</Text>
          </Box>
        </Box>
      ) : (
        <Box display="flex" flexDirection="column" gap="spacing.5" alignItems="center">
          <Heading size="medium">No Accepted Invites</Heading>
          <Box
            display="flex"
            flexDirection="column"
            gap="spacing.2"
            justifyContent="center"
            alignItems="center"
            padding="spacing.5"
            backgroundColor="surface.background.gray.subtle"
          >
            <Text weight="semibold" size="large" color="feedback.text.notice.intense">
              Did you know?
            </Text>
            <Text>
              You can now opt in to perform KYC for the client when you invite them onto{' '}
              {org?.business_name}
            </Text>
          </Box>
        </Box>
      )}
    </Box>
  );
};

export default connect(
  (state) => ({
    org: state.session.org,
  }),
  null,
)(CustomEmptyScreen);
