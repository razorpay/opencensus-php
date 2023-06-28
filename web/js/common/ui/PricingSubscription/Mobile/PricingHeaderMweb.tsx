import React from 'react';
import { Text, Badge, Heading, Box, useTheme } from '@razorpay/blade/components';
import { makeSpace } from '@razorpay/blade/utils';
import { Label, Input, Switch } from 'common/ui/PricingSubscription/PricingStyled';
import {
  PricingHeaderTag,
  PricingSubHeader,
  PricingBadge,
  StyleSwitchText,
} from 'common/ui/PricingSubscription/Mobile/PricingMwebStyle';
import { TogglePlanValue } from 'common/ui/PricingSubscription/PricingBundleCommon';

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
        <Text weight="bold">
          {togglePlan === TogglePlanValue.annual ? TogglePlanValue.monthly : TogglePlanValue.annual}{' '}
          Plans
        </Text>
      </StyleSwitchText>

      <Label data-testid="switchInput">
        <Input
          data-testid="toggleInput"
          checked={isChecked}
          type="checkbox"
          onChange={handlePlanSwitch}
        />
        <Switch />
      </Label>
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
      <PricingHeaderTag data-testid="title">
        <PricingBadge addColor data-testid="staticBadge">
          <Badge contrast="low" fontWeight="bold" size="medium" variant="neutral">
            NEW PRICING PLANS
          </Badge>
        </PricingBadge>
        <Heading contrast="low" size="large" type="normal" weight="bold">
          {title}
        </Heading>
      </PricingHeaderTag>
      <PricingSubHeader data-testid="switchContainer">
        <ToggleSwitch
          isChecked={isChecked}
          togglePlan={togglePlan}
          handlePlanSwitch={handlePlanSwitch}
        />
        <PricingBadge addBackgroundColor data-testid="dynamicBadge">
          <Badge contrast="low" fontWeight="bold" size="large" variant="neutral">
            {String(pillText)}
          </Badge>
        </PricingBadge>
      </PricingSubHeader>
    </Box>
  );
};

export default PricingHeaderMweb;
