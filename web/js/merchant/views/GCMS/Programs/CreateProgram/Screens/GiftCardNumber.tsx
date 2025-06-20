import React, { useMemo } from 'react';
import {
  TextInput,
  RadioGroup,
  Radio,
  Box,
  Text,
  TicketIcon,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';
import {
  GC_CARD_TYPE,
  GC_CARD_TYPE_VALUES,
  POSSIBLE_GIFT_CARD_LENGTHS,
} from 'merchant/views/GCMS/Programs/CreateProgram/constants';
import { generateRandomGiftCardNumber } from '../utils';

export default function GiftCardNumber({ values, errors, touched, onChange }) {
  const giftCardNumber = useMemo(
    () => generateRandomGiftCardNumber(values.card_length, values.prefix, values.card_type),
    [values.card_type, values.prefix, values.card_length],
  );
  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      <Box display="flex" flexDirection="column" gap="spacing.3" marginBottom="spacing.4">
        <Box display="flex" flexDirection="column" gap="spacing.3" width="200px">
          <Dropdown>
            <SelectInput
              label="Card Number Length"
              necessityIndicator="required"
              accessibilityLabel="Selected Card Number Length"
              name="card_length"
              onChange={({ name, values }) => onChange(name, parseInt(values[0]))}
              placeholder="Selected Card Number Length"
              value={values.card_length.toString()}
            />
            <DropdownOverlay zIndex={999999}>
              <ActionList>
                {POSSIBLE_GIFT_CARD_LENGTHS.map((val) => (
                  <ActionListItem title={val.toString()} value={val.toString()} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      </Box>
      <RadioGroup
        label="Card Type"
        necessityIndicator="required"
        isRequired
        size="medium"
        labelPosition="top"
        value={values.card_type}
        onChange={({ name, value }) => {
          onChange(name, value);
        }}
        validationState={touched.card_type && errors.card_type ? 'error' : 'none'}
        errorText={errors?.card_type}
        name="card_type"
        marginBottom="spacing.2"
      >
        {[
          GC_CARD_TYPE[GC_CARD_TYPE_VALUES.NUMERIC],
          GC_CARD_TYPE[GC_CARD_TYPE_VALUES.ALPHANUMERIC],
        ].map((cardType) => (
          <Radio value={cardType.value}>{cardType.title}</Radio>
        ))}
      </RadioGroup>

      <Box display="flex" flexDirection="row" alignItems="flex-end" width="200px">
        <TextInput
          label="Prefix"
          necessityIndicator="optional"
          type="number"
          name="prefix"
          helpText="Maximum 4 characters"
          maxCharacters={4}
          placeholder="Eg. 7000"
          labelPosition="top"
          onChange={({ name, value }) => {
            onChange(name, (value || '').toUpperCase());
          }}
          validationState={touched.prefix && errors?.prefix ? 'error' : 'none'}
          errorText={errors?.prefix}
          value={values.prefix}
          marginRight={'spacing.4'}
        />
      </Box>
      {values.card_type && (
        <Box
          display="flex"
          flexDirection="row"
          gap="spacing.4"
          padding="spacing.4"
          backgroundColor="surface.background.primary.subtle"
          borderColor="surface.border.primary.muted"
          borderRadius="medium"
        >
          <TicketIcon marginTop="spacing.2" color="feedback.icon.information.intense" />
          <Box display="flex" flexDirection="column" gap="spacing.1">
            <Text size="large" weight="semibold">
              {giftCardNumber}
            </Text>
            <Text color="interactive.text.gray.subtle" weight="medium" size="small">
              Example of gift card number based on your configuration
            </Text>
          </Box>
        </Box>
      )}
    </Box>
  );
}
