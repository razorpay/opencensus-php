import React from 'react';
import { Box } from '@razorpay/blade/components';

import BasicInfoContainer from 'merchant/views/StoreSettings/StoreCreateOrEdit/containers/BasicInfoContainer';
import LinkRzpProducts from 'merchant/views/StoreSettings/StoreCreateOrEdit/containers/LinkRzpProducts';
import LocationAndStoreInfoContainer from 'merchant/views/StoreSettings/StoreCreateOrEdit/containers/LocationAndStoreInfoContainer';

type StoreBasicDetailsProps = {
  isLoading: boolean;
};

const StoreBasicDetails = (props: StoreBasicDetailsProps) => {
  const { isLoading } = props;
  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      <BasicInfoContainer isLoading={isLoading} />
      <LocationAndStoreInfoContainer isLoading={isLoading} />
      <LinkRzpProducts isLoading={isLoading} />
    </Box>
  );
};

export default StoreBasicDetails;
