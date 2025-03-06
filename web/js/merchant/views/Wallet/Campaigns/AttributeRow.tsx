import React, { useState } from 'react';
import {
  Box,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  AutoComplete,
  Badge,
  IconButton,
  CloseIcon,
  Divider,
  TextInput,
  Text,
  ChipGroup,
  Chip,
} from '@razorpay/blade/components';
import { Controller, UseFieldArrayUpdate, useFormContext } from 'react-hook-form';
import { ATTRIBUTE_TYPE_OPERATORS, COMPARISON_OPERATORS } from './constants';
import { Attribute, AttributeFormField, FormData } from './types';

interface AttributeRowProps {
  key: string;
  availableAttributes: Record<string, Attribute>; //All attributes minus the ones already selected
  allAttributes: Record<string, Attribute>;
  onRemoveClick: () => void;
  field: AttributeFormField;
  index: number;
  update: UseFieldArrayUpdate<FormData, 'triggerAttributes'>;
  viewOnly?: boolean;
}

const AttributeRow = ({
  index,
  onRemoveClick,
  allAttributes,
  availableAttributes,
  field,
  update,
  viewOnly,
}: AttributeRowProps) => {
  const { control, setValue, trigger } = useFormContext();

  // For the row where the attribute is already selected, we need to add the selected attributes to the available attributes
  const attributeList = { ...availableAttributes, [field.field]: allAttributes[field.field] };

  const availableOperators = ATTRIBUTE_TYPE_OPERATORS[attributeList[field.field]?.type];

  return (
    <Box
      display="flex"
      flex={1}
      alignItems="baseline"
      paddingY="spacing.4"
      borderTopWidth="thin"
      borderTopColor="surface.border.gray.muted"
      backgroundColor="surface.background.gray.subtle"
    >
      <Box
        width="50px"
        display="flex"
        justifyContent="center"
        marginX="spacing.4"
        alignSelf="baseline"
      >
        <Badge color="primary" size="large" emphasis="subtle">
          AND
        </Badge>
      </Box>
      <Box display="flex" flex={1}>
        <Controller
          name={`triggerAttributes.${index}.field`}
          control={control}
          render={({ field: fieldInput, fieldState }) => (
            <Dropdown selectionType="single">
              <AutoComplete
                {...fieldInput}
                label=""
                placeholder="Select attribute"
                onChange={({ values }) => {
                  update(index, {
                    field: values[0],
                    operator: '',
                    value: '',
                    type: attributeList[values[0]].type,
                  } as AttributeFormField);
                  trigger(fieldInput.name);
                }}
                errorText={fieldState.error?.message}
                validationState={fieldState.error ? 'error' : 'none'}
                isDisabled={viewOnly}
              />
              <DropdownOverlay width="400px">
                <ActionList>
                  {Object.keys(attributeList).map((attribute: any) => (
                    <ActionListItem key={attribute} value={attribute} title={attribute} />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          )}
        />
      </Box>
      <Box display="flex" flex={1} marginX="spacing.3">
        {field.field ? (
          <Controller
            name={`triggerAttributes.${index}.operator`}
            control={control}
            render={({ field: fieldInput, fieldState }) => (
              <Dropdown selectionType="single">
                <AutoComplete
                  {...fieldInput}
                  label=""
                  placeholder="Select operator"
                  onChange={({ values }) => {
                    if (values[0] === COMPARISON_OPERATORS.IS_BETWEEN) {
                      update(index, {
                        field: field.field,
                        operator: values[0],
                        minValue: '',
                        maxValue: '',
                        type: field.type,
                      } as AttributeFormField);
                    } else {
                      update(index, {
                        field: field.field,
                        operator: values[0],
                        value: '',
                        type: field.type,
                      } as AttributeFormField);
                    }
                    trigger(fieldInput.name);
                  }}
                  errorText={fieldState.error?.message}
                  validationState={fieldState.error ? 'error' : 'none'}
                  isDisabled={viewOnly}
                />
                <DropdownOverlay>
                  <ActionList>
                    {availableOperators?.map((operator: any) => (
                      <ActionListItem
                        key={operator.value}
                        value={operator.value}
                        title={operator.label}
                      />
                    ))}
                  </ActionList>
                </DropdownOverlay>
              </Dropdown>
            )}
          />
        ) : null}
      </Box>
      <Box display="flex" flex={1} flexDirection="column">
        {field.field ? (
          field.operator === COMPARISON_OPERATORS.IS_BETWEEN ? (
            <Box display="flex" flex={1}>
              <Box alignItems="baseline">
                <Controller
                  name={`triggerAttributes.${index}.minValue`}
                  control={control}
                  render={({ field: fieldInput, fieldState }) => (
                    <TextInput
                      {...fieldInput}
                      placeholder="Minimum value"
                      label=""
                      onChange={({ value }) => {
                        setValue(fieldInput.name, value);
                        trigger(fieldInput.name);
                      }}
                      errorText={fieldState.error?.message}
                      validationState={fieldState.error ? 'error' : 'none'}
                      isDisabled={viewOnly}
                    />
                  )}
                />
              </Box>
              <Box paddingX="spacing.3" alignSelf="baseline">
                <Text color="surface.text.gray.subtle">&</Text>
              </Box>
              <Box alignSelf="baseline">
                <Controller
                  name={`triggerAttributes.${index}.maxValue`}
                  control={control}
                  render={({ field: fieldInput, fieldState }) => (
                    <TextInput
                      {...fieldInput}
                      placeholder="Maximum value"
                      label=""
                      onChange={({ value }) => {
                        setValue(fieldInput.name, value);
                        trigger(fieldInput.name);
                      }}
                      errorText={fieldState.error?.message}
                      validationState={fieldState.error ? 'error' : 'none'}
                      isDisabled={viewOnly}
                    />
                  )}
                />
              </Box>
            </Box>
          ) : (
            <Box display="flex" flex={1} alignItems="center">
              <Box>
                <Controller
                  name={`triggerAttributes.${index}.value`}
                  control={control}
                  render={({ field: fieldInput, fieldState }) =>
                    field.operator === COMPARISON_OPERATORS.IS ? (
                      <ChipGroup
                        {...fieldInput}
                        value={fieldInput.value}
                        label=""
                        onChange={({ values }) => {
                          setValue(fieldInput.name, values[0]);
                          trigger(fieldInput.name);
                        }}
                        errorText={fieldState.error?.message}
                        validationState={fieldState.error ? 'error' : 'none'}
                        isDisabled={viewOnly}
                      >
                        <Chip value="yes">Yes</Chip>
                        <Chip value="no">No</Chip>
                      </ChipGroup>
                    ) : (
                      <TextInput
                        {...fieldInput}
                        value={fieldInput.value}
                        placeholder="Enter value"
                        label=""
                        onChange={({ value }) => {
                          setValue(fieldInput.name, value);
                          trigger(fieldInput.name);
                        }}
                        errorText={fieldState.error?.message}
                        validationState={fieldState.error ? 'error' : 'none'}
                        isDisabled={viewOnly}
                      />
                    )
                  }
                />
              </Box>
            </Box>
          )
        ) : null}
      </Box>
      <Divider
        variant="muted"
        thickness="thin"
        orientation="vertical"
        dividerStyle="solid"
        marginLeft="spacing.3"
      />
      <Box width="40px" display="flex" justifyContent="center">
        <IconButton
          icon={CloseIcon}
          onClick={onRemoveClick}
          accessibilityLabel="remove"
          isDisabled={viewOnly}
        />
      </Box>
    </Box>
  );
};

export default AttributeRow;
