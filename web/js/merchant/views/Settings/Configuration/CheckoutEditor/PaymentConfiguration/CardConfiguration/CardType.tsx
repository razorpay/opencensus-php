import React from 'react';
import startCase from 'lodash/startCase';
import { ArrowRightIcon, Box, Link, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';

export default function CardType({
  finalCardConfigurationObj,
  setFinalCardConfigurationObj,
  cardType,
}) {
  return (
    <Box
      backgroundColor="surface.background.gray.moderate"
      paddingY="spacing.5"
      paddingX="spacing.4"
      borderRadius="large"
    >
      <Text weight="semibold" size="large">
        Card Type
      </Text>
      <Link
        href="https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods/supported-methods/#supported-cards"
        icon={ArrowRightIcon}
        iconPosition="right"
      >
        See documentation
      </Link>
      <Box gap="spacing.5" display="flex" flexDirection="column" marginTop="spacing.5">
        {cardType.map((item, index) => (
          <ListItemCard
            elevation="lowRaised"
            key={index}
            Title={
              <Text
                variant="body"
                weight="medium"
                size="medium"
                color={item.isDisabled ? 'surface.text.gray.muted' : 'surface.text.gray.normal'}
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
                  setFinalCardConfigurationObj((prev) => {
                    const updatedTypes = isChecked
                      ? [...prev.types, item.title]
                      : prev.types.filter((prevItem) => prevItem !== item.title);

                    return {
                      ...prev,
                      types: updatedTypes,
                    };
                  });
                }}
              />
            }
          />
        ))}
      </Box>
    </Box>
  );
}
