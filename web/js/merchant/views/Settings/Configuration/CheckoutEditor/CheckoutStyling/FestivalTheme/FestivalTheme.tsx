import React from 'react';
import { Badge, Box } from '@razorpay/blade/components';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { FESTIVAL_THEME_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import track from './track';

const TitleWrapper = ({ children }) => (
  <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.2">
    {children}
  </Box>
);

const FestivalTheme = () => {
  const { values, handleFestivalThemeToggle } = useCheckoutEditor();

  const splitz = useSplitzService();
  const shouldShowToggle = isExperimentEnabled(splitz?.abExperiments?.checkout_festival_theme);

  function handleFestivalThemeToggleChange(isChecked: boolean) {
    handleFestivalThemeToggle(isChecked);
    track.toggleFestivalTheme(isChecked ? 'visible' : 'hidden');
  }

  const title = (
    <TitleWrapper>
      {FESTIVAL_THEME_DEFAULT_VALUE.title}
      <Badge color="positive" emphasis="intense" size="small" marginLeft="spacing.2">
        New
      </Badge>
    </TitleWrapper>
  );

  if (!shouldShowToggle) {
    return null;
  }

  return (
    <FeatureToggle
      isChecked={
        values[CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME] === undefined
          ? true
          : values[CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME]
      }
      feature={CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME}
      title={title}
      subTitle={FESTIVAL_THEME_DEFAULT_VALUE.subTitle}
      toggleHandler={handleFestivalThemeToggleChange}
    />
  );
};

export default FestivalTheme;
