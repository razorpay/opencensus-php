import React from 'react';
import {
  Box,
  Text,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';
import { COMMON_Z_INDEX } from 'common/constant';
import IntoView from 'common/ui/IntoView';
import TextHighlighter from 'common/ui/TextHighlighter';
import { useCheckoutConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context';
import { CHECKOUT_LANG } from 'merchant/views/Settings/Configuration/deeplink-constants';

const languageOptions = [
  { name: 'English', code: 'en' },
  { name: 'Bengali', code: 'ben' },
  { name: 'Hindi', code: 'hi' },
  { name: 'Marathi', code: 'mar' },
  { name: 'Gujarati', code: 'guj' },
  { name: 'Tamil', code: 'tam' },
  { name: 'Telugu', code: 'tel' },
];

const LocaleSettings = () => {
  const { values, handleLocaleChange } = useCheckoutConfig();

  const handleSelectChange = (evt: { values: string[] }) => {
    handleLocaleChange(evt.values[0]);
  };

  return (
    <IntoView hashedWith={CHECKOUT_LANG}>
      <Box display="flex" flexDirection="column" gap="spacing.2">
        <Box>
          <Text weight="semibold" color="surface.text.gray.subtle">
            <TextHighlighter hashedWith={CHECKOUT_LANG}>Default Language</TextHighlighter>
          </Text>
          <Text color="surface.text.gray.muted">
            Default language will be used on the Checkout page if customer doesn&apos;t specify a
            language.
          </Text>
        </Box>
        <Dropdown>
          <SelectInput
            label=""
            placeholder=""
            value={values.locale.languageCode}
            onChange={handleSelectChange}
          />
          <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
            <ActionList>
              {languageOptions.map((config) => (
                <ActionListItem key={config.code} title={config.name} value={config.code} />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </Box>
    </IntoView>
  );
};

export default LocaleSettings;
