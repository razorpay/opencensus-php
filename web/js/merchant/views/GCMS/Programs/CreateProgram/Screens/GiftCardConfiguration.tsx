/* eslint-disable consistent-return */
import React, { useState } from 'react';
import {
  TextInput,
  TextArea,
  RadioGroup,
  Radio,
  Box,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Text,
  Chip,
  ChipGroup,
  Link,
  PlusIcon,
  XCircleIcon,
  CheckIcon,
  Button,
  RupeeIcon,
  InfoIcon,
} from '@razorpay/blade/components';
import { getFormattedAmount } from 'newAuth/utils';
import { isNonNegativeIntegerOrEmpty } from 'merchant/views/GCMS/shared/utils';
import { DENOMINATION_TYPE } from 'merchant/views/GCMS/Programs/CreateProgram/constants';

export default ({
  values,
  errors,
  touched,
  onChange,
  fixedDenominationOptions,
  addFixedDenominationOptions,
}) => {
  const [addingDenomination, setAddingDenomination] = useState(false);
  const [newDenomination, setNewDenomination] = useState('');

  function addFixedDenominationValueToList() {
    setAddingDenomination(false);
    addFixedDenominationOptions(newDenomination);
    setNewDenomination('');
  }

  function renderDenominationValues() {
    if (!values.denomination_type) return null;
    switch (values.denomination_type) {
      case DENOMINATION_TYPE.FIXED.value: {
        return (
          <Box display="flex" flexDirection="column" gap="spacing.3">
            <ChipGroup
              accessibilityLabel="Choose one business type from the options below"
              label="Fixed Denomination Gift Card Value"
              name="denomination_values"
              onChange={({ name, values }) => {
                onChange(name, values);
              }}
              validationState={
                touched.denomination_values && errors?.denomination_values ? 'error' : 'none'
              }
              errorText={errors?.denomination_values}
              value={values.denomination_values}
              selectionType="multiple"
            >
              {fixedDenominationOptions.map((value) => (
                <Chip value={value}>{getFormattedAmount(value * 100, true)}</Chip>
              ))}
            </ChipGroup>
            {!addingDenomination ? (
              <Link
                variant="button"
                icon={PlusIcon}
                onClick={() => {
                  setAddingDenomination(true);
                }}
              >
                Add Denomination
              </Link>
            ) : (
              <Box
                display="flex"
                flexDirection="row"
                gap="spacing.2"
                alignItems="flex-end"
                width="100%"
              >
                <Box flexGrow="1">
                  <TextInput
                    label="Denomination"
                    type="number"
                    name="denomination_value"
                    placeholder="200"
                    labelPosition="top"
                    validationState={touched.terms && errors?.terms ? 'error' : 'none'}
                    errorText={errors?.terms}
                    leadingIcon={RupeeIcon}
                    marginBottom="spacing.0"
                    onChange={({ value }) => {
                      isNonNegativeIntegerOrEmpty(value) && setNewDenomination(value);
                    }}
                    value={newDenomination}
                    marginRight={'spacing.4'}
                  />
                </Box>
                <Button
                  icon={CheckIcon}
                  variant="tertiary"
                  size="small"
                  onClick={addFixedDenominationValueToList}
                ></Button>
                <Button
                  icon={XCircleIcon}
                  variant="tertiary"
                  size="small"
                  onClick={() => setAddingDenomination(false)}
                ></Button>
              </Box>
            )}
          </Box>
        );
      }
      case DENOMINATION_TYPE.CUSTOMIZABLE.value: {
        return (
          <Box display="flex" flexDirection="column">
            <Box display="flex" flexDirection="row" alignItems="flex-end">
              <TextInput
                isRequired
                necessityIndicator="required"
                label="Denomination Range"
                type="number"
                name="denomination_from"
                placeholder="0"
                labelPosition="top"
                validationState={
                  touched.denomination_values && errors?.denomination_values ? 'error' : 'none'
                }
                marginBottom="spacing.0"
                leadingIcon={RupeeIcon}
                onChange={({ value }: { value: string }) => {
                  isNonNegativeIntegerOrEmpty(value) &&
                    onChange('denomination_values', { ...values.denomination_values, from: value });
                }}
                value={values.denomination_values.from}
              />
              <Text marginX="spacing.4" marginBottom={'spacing.3'}>
                to
              </Text>
              <TextInput
                isRequired
                necessityIndicator="required"
                accessibilityLabel="denomination range to"
                type="number"
                name="denomination_to"
                placeholder="0"
                labelPosition="top"
                validationState={
                  touched.denomination_values && errors?.denomination_values ? 'error' : 'none'
                }
                leadingIcon={RupeeIcon}
                // errorText={errors?.denomination_values}
                onChange={({ value }: { value: string }) => {
                  isNonNegativeIntegerOrEmpty(value) &&
                    onChange('denomination_values', { ...values.denomination_values, to: value });
                }}
                value={values.denomination_values.to}
              />
            </Box>
            {touched.denomination_values && errors?.denomination_values && (
              <Text marginTop={'4px'} color="feedback.text.negative.intense" size="xsmall">
                <InfoIcon color="feedback.icon.negative.intense" size="xsmall" />
                <i>{' ' + errors.denomination_values}</i>
              </Text>
            )}
          </Box>
        );
      }
    }
  }

  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      <RadioGroup
        label="Denomination Type"
        necessityIndicator="required"
        isRequired={true}
        size="medium"
        labelPosition="top"
        value={values.denomination_type}
        onChange={({ name, value }) => {
          switch (value) {
            case DENOMINATION_TYPE.CUSTOMIZABLE.value: {
              onChange('denomination_values', { from: '', to: '' }, false);

              break;
            }
            case DENOMINATION_TYPE.FIXED.value: {
              onChange('denomination_values', [], false);
              break;
            }
          }
          onChange(name, value);
        }}
        validationState={touched.denomination_type && errors.denomination_type ? 'error' : 'none'}
        errorText={errors?.denomination_type}
        name="denomination_type"
        marginBottom="spacing.2"
      >
        {[DENOMINATION_TYPE.CUSTOMIZABLE, DENOMINATION_TYPE.FIXED].map((denomination) => (
          <Radio value={denomination.value} helpText={denomination.helpText}>
            {denomination.title}
          </Radio>
        ))}
      </RadioGroup>
      {renderDenominationValues()}

      <Box display="flex" flexDirection="row" alignItems="flex-end">
        <TextInput
          isRequired
          necessityIndicator="required"
          label="Gift Card Validity"
          type="number"
          name="validity_quantity"
          placeholder="0"
          labelPosition="top"
          onChange={({ name, value }) => {
            isNonNegativeIntegerOrEmpty(value) && onChange(name, value);
          }}
          validationState={
            touched.validity_quantity && errors?.validity_quantity ? 'error' : 'none'
          }
          errorText={errors?.validity_quantity}
          value={values.validity_quantity}
          marginRight={'spacing.4'}
        />
        <Dropdown
          marginBottom={touched.validity_quantity && errors?.validity_quantity ? '20px' : '0px'}
        >
          <SelectInput
            accessibilityLabel="Select Validity"
            name="validity_span"
            onChange={({ name, values }) => onChange(name, values[0])}
            placeholder="Select Validity"
            value={values.validity_span}
            errorText={errors?.validity_span}
            validationState={touched.validity_span && errors?.validity_span ? 'error' : 'none'}
          />
          <DropdownOverlay zIndex={999999}>
            <ActionList>
              <ActionListItem title="Hours" value="hour" />
              <ActionListItem title="Days" value="day" />
              <ActionListItem title="Months" value="month" />
              <ActionListItem title="Years" value="year" />
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </Box>
      <RadioGroup
        label="Pin Required"
        necessityIndicator="required"
        isRequired={true}
        size="medium"
        labelPosition="top"
        name="pin"
        value={values.pin}
        onChange={({ name, value }) => onChange(name, value)}
        validationState={touched.pin && errors?.pin ? 'error' : 'none'}
        errorText={errors?.pin}
      >
        <Radio value="yes">Yes</Radio>
        <Radio value="no">No</Radio>
      </RadioGroup>

      <TextArea
        necessityIndicator="none"
        label="Steps to Redeem"
        labelPosition="top"
        name="steps_to_redeem"
        placeholder="Provide instructions fo customers to redeem their gift cards."
        onChange={({ name, value }) => {
          onChange(name, value);
        }}
        value={values.steps_to_redeem}
      />
      <TextArea
        necessityIndicator="none"
        label="Terms and Conditions"
        labelPosition="top"
        name="terms_and_conditions"
        placeholder="Add rules to policies applicable to the gift card program"
        validationState={
          touched.terms_and_conditions && errors?.terms_and_conditions ? 'error' : 'none'
        }
        errorText={errors?.terms_and_conditions}
        onChange={({ name, value }) => {
          onChange(name, value);
        }}
        value={values.terms_and_conditions}
      />
    </Box>
  );
};
