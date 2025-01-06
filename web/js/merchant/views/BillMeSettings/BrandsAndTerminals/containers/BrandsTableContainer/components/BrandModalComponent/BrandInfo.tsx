import React from 'react';
import { Box, Text, Divider } from '@razorpay/blade/components';

import imagePlaceholder from 'assets/billme-integration/brandInfo-image-placeholder.svg';

import type { Brand } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/types';

type BrandInfoProps = {
  selectedBrandInfo: Brand;
};

const BrandInfo = ({ selectedBrandInfo }: BrandInfoProps): React.ReactElement => {
  const { name, description, logo } = selectedBrandInfo;

  return (
    <Box
      paddingX="spacing.6"
      paddingY="spacing.5"
      display="flex"
      gap="spacing.7"
      alignItems="center"
      flexDirection={{ base: 'column', m: 'row' }}
    >
      <Box display="flex" flexDirection="column" gap="spacing.4" alignItems="center">
        <Text size="medium">Image</Text>
        <Box>
          <img src={logo || imagePlaceholder} width="100px" height="100px" alt={`${name} logo`} />
        </Box>
      </Box>
      <Box width="100%">
        <Box
          display="flex"
          flexDirection={{ base: 'column', m: 'row' }}
          gap={{ base: 'spacing.2', m: 'spacing.0' }}
        >
          <Box flex={1}>
            <Text size="medium">Name</Text>
          </Box>
          <Box flex={1.5}>
            <Text size="medium" weight="semibold" wordBreak="break-word">
              {name || '-'}
            </Text>
          </Box>
        </Box>
        <Divider marginY="spacing.4" />
        <Box
          display="flex"
          flexDirection={{ base: 'column', m: 'row' }}
          gap={{ base: 'spacing.2', m: 'spacing.0' }}
        >
          <Box flex={1}>
            <Text size="medium">Description</Text>
          </Box>
          <Box flex={1.5}>
            <Text size="medium" weight="medium" wordBreak="break-word">
              {description || '-'}
            </Text>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

export default BrandInfo;
