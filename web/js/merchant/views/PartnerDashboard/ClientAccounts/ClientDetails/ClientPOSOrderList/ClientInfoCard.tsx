import React from 'react';
import { Box, Spinner, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { ShowNotificationType } from 'common/typings';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import useSubmerchantDetails from './hooks/useSubmerchantDetails';
import { SpinnerContainer } from './styles';
const TEXT_NA = 'not available';

type ClientInfoCardProps = {
  showNotification: ShowNotificationType;
  submerchantId: string;
};
const ClientInfoCard = ({ showNotification, submerchantId }: ClientInfoCardProps): JSX.Element => {
  const { isLoading, submerchant } = useSubmerchantDetails({
    showNotification,
    submerchantId,
    productType: PRODUCT_TYPE.POS,
  });
  if (isLoading) {
    return (
      <SpinnerContainer>
        <Spinner testID="client-details-spinner" accessibilityLabel="spinner" size="xlarge" />
      </SpinnerContainer>
    );
  }

  // Data Parsing
  const {
    id: accountId = TEXT_NA,
    name: accountName = TEXT_NA,
    user: { contact_mobile: contactMobile = TEXT_NA } = {},
    email: contactEmail = TEXT_NA,
  } = submerchant || {};
  return (
    <Box
      display={{ base: 'grid', l: 'flex' }}
      gridTemplateColumns={{ base: 'auto', s: 'auto auto' }}
      gap="spacing.7"
      justifyContent="start"
      flexWrap="wrap"
      width="100%"
    >
      <Box>
        <Text size="large" weight="regular" color="interactive.text.onPrimary.muted">
          Account Details
        </Text>
      </Box>
      <Box>
        <Text
          size="large"
          weight="regular"
          color="surface.text.staticWhite.normal"
          truncateAfterLines={1}
        >
          {accountId}
        </Text>
      </Box>
      <Box>
        <Text size="large" weight="regular" color="interactive.text.onPrimary.muted">
          {accountName !== TEXT_NA ? accountName : 'Contact'}
        </Text>
      </Box>
      <Box
        display="flex"
        gap="spacing.7"
        flexWrap="wrap"
        flexDirection={{ base: 'column', l: 'row' }}
      >
        {contactMobile !== TEXT_NA ? (
          <Box>
            <Text
              size="large"
              weight="regular"
              color="surface.text.staticWhite.normal"
              truncateAfterLines={1}
            >
              {contactMobile}
            </Text>
          </Box>
        ) : null}
        {contactEmail !== TEXT_NA ? (
          <Box>
            <Text
              size="large"
              weight="regular"
              color="surface.text.staticWhite.normal"
              truncateAfterLines={1}
            >
              {contactEmail}
            </Text>
          </Box>
        ) : null}
      </Box>
    </Box>
  );
};

export default connect(null, (dispatch) => bindActionCreators({ showNotification }, dispatch))(
  ClientInfoCard,
);
