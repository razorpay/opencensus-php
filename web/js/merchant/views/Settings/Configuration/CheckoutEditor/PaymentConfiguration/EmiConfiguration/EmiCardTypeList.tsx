import React from 'react';
import startCase from 'lodash/startCase';
import { Box, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import _track from './track';

export default function EmiCardTypeList({
  finalCardConfigurationObj,
  updateEmiIssuers,
  setFinalCardConfigurationObj,
  cardType,
}) {
  return (
    <Box
      paddingY="spacing.5"
      borderRadius="large"
      display="flex"
      flexDirection="column"
      gap="spacing.5"
    >
      {cardType.map((item, index) => (
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
              {`${startCase(item.title)} Cards`}
            </Text>
          }
          accessibilityLabel={item.title}
          RightComponent={
            <Switch
              accessibilityLabel={`${item.title} switch`}
              isChecked={finalCardConfigurationObj?.types?.includes(item.title)}
              onChange={({ isChecked }) => {
                let updatedTypes = []
                setFinalCardConfigurationObj((prev) => {
                  updatedTypes = isChecked
                    ? [...prev.types, item.title]
                    : prev.types.filter((prevItem) => prevItem !== item.title);

                  return {
                    ...prev,
                    types: updatedTypes,
                  };
                });
                updateEmiIssuers(updatedTypes)
                _track.cardTypeToggled(item.title, isChecked);
              }}
            />
          }
        />
      ))}
    </Box>
  );
}
