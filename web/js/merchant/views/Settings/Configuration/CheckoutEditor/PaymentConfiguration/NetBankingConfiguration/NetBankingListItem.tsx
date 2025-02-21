import React, { useState } from 'react';
import { Badge, Box, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import { getInstrumentLogo } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/getInstrumentLogo';

export default function NetBankingListItem({ item, activeBanks, setActiveBanks }) {
  const [isLoading, setIsLoading] = useState(false);

  return (
    <ListItemCard
      backgroundColor="surface.background.gray.moderate"
      Title={
        <Text variant="body" weight="medium" size="medium" color="surface.text.gray.normal">
          {item.name}
        </Text>
      }
      Description={
        <Text variant="body" weight="regular" size="small" color="surface.text.gray.subtle">
          {item.type === 'retail' ? 'Retail banking' : 'Corporate banking'}
        </Text>
      }
      accessibilityLabel={item.name}
      LeftIcon={
        <Box
          display="flex"
          alignItems="center"
          width="spacing.8"
          height="spacing.8"
          padding="spacing.2"
          borderRadius="max"
          borderBottomStyle="solid"
          borderWidth="thin"
          borderColor="surface.border.gray.muted"
        >
          <img
            src={getInstrumentLogo('card', item.code)}
            alt={item.name}
            width="22.15px"
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
          <Switch
            accessibilityLabel={`${item.name} switch`}
            isChecked={activeBanks.banks?.includes(item.code)}
            onChange={({ isChecked }) => {
              setActiveBanks((prev) => {
                const updatedBanks = isChecked
                  ? { ...prev, banks: [...prev.banks, item.code] }
                  : {
                      ...prev,
                      banks: prev.banks.filter((prevItem) => prevItem !== item.code),
                    };
                return updatedBanks;
              });
            }}
          />
        </Box>
      }
    />
  );
}
