import React from 'react';
import { connect } from 'react-redux';

import { Box, Heading, Text } from '@razorpay/blade/components';
import { WhatsNewCard } from 'merchant/views/MagicCheckout/MagicDashboard/WhatsNew/Components/Card';
import { OnboardedMerchantTabHeader } from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/styled';

import { NEW_OFFERINGS } from 'merchant/views/MagicCheckout/MagicDashboard/WhatsNew/constants';

const WhatsNew = ({ platform, isRCODEnabled }) => {
  return (
    <>
      <OnboardedMerchantTabHeader>
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Heading size="xlarge" weight="semibold">
            What’s new in Magic Checkout
          </Heading>
          <Text weight="regular" color="surface.text.gray.subtle">
            Discover new feature updates and product launches
          </Text>
        </Box>
      </OnboardedMerchantTabHeader>
      <Box padding="33px" display="flex" flexDirection="column" gap="15px">
        {NEW_OFFERINGS.map((newOffering, index) => {
          return (
            newOffering.condition(platform, isRCODEnabled) && (
              <WhatsNewCard key={index} item={newOffering} isRCODEnabled={isRCODEnabled} />
            )
          );
        })}
      </Box>
    </>
  );
};

const mapStateToProps = (state) => ({
  platform: state?.magicCheckout?.platform,
  isRCODEnabled: state?.magicCheckout?.rcod,
});

export default connect(mapStateToProps, null)(WhatsNew);
