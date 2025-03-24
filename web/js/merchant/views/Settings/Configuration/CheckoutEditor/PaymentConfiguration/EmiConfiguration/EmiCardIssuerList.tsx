import React, { useState } from 'react';
import { Badge, Box, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import { getInstrumentLogo } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/getInstrumentLogo';
import _track from './track';

export default function EmiCardIssuerList({
  finalCardConfigurationObj,
  setFinalCardConfigurationObj,
  cardIssuer,
}) {
  const [isLoading, setIsLoading] = useState(false);

  return (
    <Box>
      <Box gap="spacing.5" display="flex" flexDirection="column" marginTop="spacing.5">
        {cardIssuer.map((item, index) => (
          <ListItemCard
            elevation="lowRaised"
            key={index}
            Title={
              <Text
                variant="body"
                weight="medium"
                size="medium"
                color={
                  item.status === 'greyed' ? 'surface.text.gray.muted' : 'surface.text.gray.normal'
                }
              >
                {item.name}
              </Text>
            }
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
                  src={getInstrumentLogo('emi', item.code.replace('_DC', ''))}
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
            accessibilityLabel={item.name}
            RightComponent={
              <Box display="flex" gap="spacing.5">
                <Switch
                  accessibilityLabel={`${item.name} switch`}
                  isChecked={finalCardConfigurationObj?.issuers?.includes(
                    item.code.replace('_DC', ''),
                  )}
                  onChange={({ isChecked }) => {
                    setFinalCardConfigurationObj((prev) => {
                      const updatedIssuers = isChecked
                        ? [...prev.issuers, item.code.replace('_DC', '')]
                        : prev.issuers.filter(
                            (prevItem) => prevItem !== item.code.replace('_DC', ''),
                          );

                      return {
                        ...prev,
                        issuers: updatedIssuers,
                      };
                    });
                    _track.cardIssuerToggled(item.code.replace('_DC', ''), isChecked);
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
