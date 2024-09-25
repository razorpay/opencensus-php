import React from 'react';

import { RadioGroup, Radio, Box } from '@razorpay/blade/components';
import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import { useCheckoutFeatures } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/index';

import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';
import { CHECKOUT_FEATURE_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/constants';
import { EMAIL_SETTINGS_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutFeatures/constants/DefaultValue';

const ExtraItems = ({ isChecked, selectedValue, handleEmailValueChange }): React.ReactElement => {
  return (
    <>
      {isChecked && (
        <RadioGroup
          name="checkout-config-email"
          onChange={({ value }) => handleEmailValueChange(value)}
          value={selectedValue}
        >
          <Box display="flex" gap="8px">
            <Radio testID="aggregator-model" value={EmailLessCheckoutConfigOptions.OPTIONAL}>
              Optional
            </Radio>
            <Radio testID="direct-model" value={EmailLessCheckoutConfigOptions.MANDATORY}>
              Mandatory
            </Radio>
          </Box>
        </RadioGroup>
      )}
    </>
  );
};

const EmailSettings = () => {
  const { values, handleEmailToggle, handleEmailValueChange } = useCheckoutFeatures();
  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_FEATURE_FIELDS.EMAIL].isEnabled}
      feature={CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT}
      title={EMAIL_SETTINGS_DEFAULT_VALUE.title}
      subTitle={EMAIL_SETTINGS_DEFAULT_VALUE.subTitle}
      toggleHandler={(isChecked) => handleEmailToggle(isChecked)}
      extraItems={
        <ExtraItems
          isChecked={values.email.isEnabled}
          selectedValue={values.email.value}
          handleEmailValueChange={handleEmailValueChange}
        />
      }
    />
  );
};

export default EmailSettings;
