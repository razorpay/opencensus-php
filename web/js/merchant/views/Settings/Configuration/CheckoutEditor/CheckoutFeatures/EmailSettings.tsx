import React from 'react';

import { RadioGroup, Radio, Box } from '@razorpay/blade/components';
import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';

import { EMAIL_SETTINGS_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

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
            <Radio
              testID={`email-settings-${EmailLessCheckoutConfigOptions.OPTIONAL}`}
              value={EmailLessCheckoutConfigOptions.OPTIONAL}
            >
              Optional
            </Radio>
            <Radio
              testID={`email-settings-${EmailLessCheckoutConfigOptions.MANDATORY}`}
              value={EmailLessCheckoutConfigOptions.MANDATORY}
            >
              Mandatory
            </Radio>
          </Box>
        </RadioGroup>
      )}
    </>
  );
};

const EmailSettings = ({ blockData }: any) => {
  const { values, handleEmailToggle, handleEmailValueChange } = useCheckoutEditor();
  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_EDITOR_FIELDS.EMAIL].isEnabled}
      feature={CHECKOUT_EDITOR_FIELDS.EMAIL}
      title={EMAIL_SETTINGS_DEFAULT_VALUE.title}
      subTitle={EMAIL_SETTINGS_DEFAULT_VALUE.subTitle}
      toggleHandler={(isChecked) => handleEmailToggle(isChecked)}
      blockData={blockData}
      subSectionName="email-settings"
      isFeature
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
