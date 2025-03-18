import React, { useState } from 'react';
import { Badge, Box, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import { getInstrumentLogo } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/getInstrumentLogo';
import _track from './track';

export default function CardlessEmi({
  finalCardConfigurationObj,
  setFinalCardConfigurationObj,
  cardlessProvider,
}) {
  const [isLoading, setIsLoading] = useState(false);
  return (
    <Box borderRadius="large">
      <Box gap="spacing.5" display="flex" flexDirection="column">
        {cardlessProvider.map((item, index) => (
          <ListItemCard
            elevation="lowRaised"
            key={index}
            Title={
              <Text variant="body" weight="medium" size="medium" color="surface.text.gray.normal">
                {item.details.name}
              </Text>
            }
            accessibilityLabel={item.code}
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
                  src={getInstrumentLogo('cardless_emi', item.code)}
                  alt={
                    item?.details.name?.length > 3
                      ? `${item.details.name.substring(0, 3)}...`
                      : item.details.name
                  }
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
                  accessibilityLabel={`${item.details.name} switch`}
                  isChecked={finalCardConfigurationObj?.providers?.includes(item.code)}
                  onChange={({ isChecked }) => {
                    setFinalCardConfigurationObj((prev) => {
                      const updatedNetworks = isChecked
                        ? [...prev.providers, item.code]
                        : prev.providers.filter((prevItem) => prevItem !== item.code);
                      return {
                        ...prev,
                        providers: updatedNetworks,
                      };
                    });
                    _track.cardlessEMIToggled(item.code, isChecked);
                  }}
                />
              </Box>
            }
          />
        ))}
      </Box>
    </Box>
  );
}
