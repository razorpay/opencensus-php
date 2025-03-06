import React from 'react';
import { Box, Heading, Divider } from '@razorpay/blade/components';
import TriggersAndActions from './TriggersAndActions';
import BurnRules from './BurnRules';
import CampaignSettings from './CampaignSettings';

const ReviewAndPublish = (props) => {
  return (
    <Box display="flex" flex={1} flexDirection="column">
      <Heading
        size="small"
        weight="semibold"
        color="surface.text.gray.normal"
        marginBottom="spacing.6"
      >
        Review & Publish
      </Heading>
      <TriggersAndActions {...props} viewOnly />
      <Divider
        dividerStyle="dashed"
        orientation="horizontal"
        thickness="thin"
        marginY="spacing.7"
      />
      <BurnRules {...props} viewOnly />
      <Divider
        dividerStyle="dashed"
        orientation="horizontal"
        thickness="thin"
        marginY="spacing.7"
      />
      <CampaignSettings {...props} viewOnly />
    </Box>
  );
};

export default ReviewAndPublish;
