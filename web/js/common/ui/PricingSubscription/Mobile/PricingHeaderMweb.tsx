import React from 'react';
import { Text, Badge, Box, useTheme, BladeProvider } from '@razorpay/blade/components';
import { makeSpace } from '@razorpay/blade/utils';
import { ToggleLabel, Input, Switch } from 'common/ui/PricingSubscription/PricingStyled';
import {
  PricingHeaderTag,
  PricingSubHeader,
  StyleSwitchText,
} from 'common/ui/PricingSubscription/Mobile/PricingMwebStyle';
import { TogglePlanValue } from 'common/ui/PricingSubscription/PricingBundleCommon';
import { bladeTheme } from '@razorpay/blade/tokens';

interface ToggleSwitchProps {
  isChecked: boolean;
  togglePlan: string;
  handlePlanSwitch: (e: React.ChangeEvent<HTMLInputElement>) => void;
}
interface PricingHeaderMwebProps extends ToggleSwitchProps {
  title: string;
  pillText: string;
}

export const ToggleSwitch = ({
  isChecked,
  togglePlan,
  handlePlanSwitch,
}: ToggleSwitchProps): JSX.Element => {
  return (
    <Box display="flex" justifyContent="center" alignItems="center">
      <StyleSwitchText>
        <Text>Switch to </Text>
        <Text weight="semibold">
          {togglePlan === TogglePlanValue.annual ? TogglePlanValue.monthly : TogglePlanValue.annual}{' '}
          Plans
        </Text>
      </StyleSwitchText>
      <ToggleLabel data-testid="switchInput">
        <Input
          data-testid="toggleInput"
          checked={isChecked}
          type="checkbox"
          onChange={handlePlanSwitch}
        />
        <Switch />
      </ToggleLabel>
    </Box>
  );
};

const PricingHeaderMweb = ({
  togglePlan,
  title,
  pillText,
  isChecked,
  handlePlanSwitch,
}: PricingHeaderMwebProps): JSX.Element => {
  const { theme } = useTheme();
  return (
    <Box
      display="flex"
      justifyContent="center"
      alignItems="flex-start"
      flexDirection="column"
      marginBottom="spacing.6"
      margin={makeSpace(-theme.spacing[5])}
    >
      <BladeProvider themeTokens={bladeTheme} colorScheme="dark">
        <PricingHeaderTag data-testid="title">
          <Badge emphasis="intense" size="large" color="positive">
            New pricing plans
          </Badge>
          <Text
            marginTop="spacing.4"
            weight="semibold"
            size="medium"
            color="surface.text.gray.normal"
          >
            {title}
          </Text>
        </PricingHeaderTag>
      </BladeProvider>

      <PricingSubHeader data-testid="switchContainer">
        <ToggleSwitch
          isChecked={isChecked}
          togglePlan={togglePlan}
          handlePlanSwitch={handlePlanSwitch}
        />
        <Badge testID="bundle-pricing-header-badge" emphasis="subtle" size="large" color="positive">
          {String(pillText)}
        </Badge>
      </PricingSubHeader>
    </Box>
  );
};

export default PricingHeaderMweb;
