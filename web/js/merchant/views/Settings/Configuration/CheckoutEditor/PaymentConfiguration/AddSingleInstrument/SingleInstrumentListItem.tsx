import React, { useState } from 'react';
import { Badge, Box, Radio, Text } from '@razorpay/blade/components';

import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import { getInstrumentLogo } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/getInstrumentLogo';

export default function SingleInstrumentListItem({ item, method }) {
  const [isLoading, setIsLoading] = useState(false);
  return (
    <ListItemCard
      backgroundColor="surface.background.gray.moderate"
      Title={
        <Text variant="body" weight="medium" size="medium" color={'surface.text.gray.normal'}>
          {item?.name || item?.app_name || item.details?.name}
        </Text>
      }
      Description={
        item.type && (
          <Text
            variant="body"
            weight="regular"
            size="small"
            color={
              item.status === 'greyed' ? 'surface.text.gray.muted' : 'surface.text.gray.subtle'
            }
          >
            {item.type === 'retail' ? 'Retail banking' : 'Corporate banking'}
          </Text>
        )
      }
      accessibilityLabel={item?.name || item?.details?.name || item?.app_name}
      Radio={<Radio value={item?.code || item?.name || item?.app_name} />}
      LeftIcon={
        <Box
          display="flex"
          alignItems="center"
          height="spacing.8"
          width="spacing.8"
          padding="spacing.2"
          borderRadius="max"
          borderBottomStyle="solid"
          borderWidth="thin"
          borderColor="surface.border.gray.muted"
          opacity={item.status === 'greyed' ? 0.5 : 1}
        >
          <img
            src={getInstrumentLogo(method, item?.code)}
            alt={item?.name || item?.app_name}
            width={'100%'}
            onLoad={() => {
              setIsLoading(true);
            }}
            style={{
              display: `${isLoading ? 'initial' : 'none'}`,
            }}
          />
        </Box>
      }
      RightComponent={
        <Box display="flex" gap="16px">
          <Badge color="positive" size="large">
            Activated
          </Badge>
        </Box>
      }
    />
  );
}
