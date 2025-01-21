import React from 'react';
import { Box, BoxProps, Text } from '@razorpay/blade/components';

import imagePlaceholder from '@apps/digital-bills/src/assets/icons/image-placeholder.svg';
import Avatar from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/Avatar';

type BrandOverviewHeaderProps = {
  brandLogo: string;
  brandName: string;
  address: string;
  boxProps?: BoxProps;
};

const BrandOverviewHeader = ({
  brandLogo,
  brandName,
  address,
  boxProps,
}: BrandOverviewHeaderProps): React.ReactElement => {
  return (
    <Box display="flex" gap="spacing.4" {...boxProps}>
      <Avatar
        imageSrc={brandLogo || imagePlaceholder}
        imageAlt={`${brandName || ''} Logo`}
        boxSize="50px"
      />
      <Box width={{ s: '100%', m: '60%' }}>
        <Text size="large" weight="semibold">
          {brandName}
        </Text>
        <Text>{address}</Text>
      </Box>
    </Box>
  );
};

export default BrandOverviewHeader;
