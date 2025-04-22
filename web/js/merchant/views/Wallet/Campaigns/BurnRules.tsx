import React from 'react';
import {
  Box,
  Text,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  AutoComplete,
  InfoIcon,
  Tooltip,
  TooltipInteractiveWrapper,
  IconButton,
  TextInput,
  Heading,
  RupeeIcon,
} from '@razorpay/blade/components';
import { Controller, useFormContext } from 'react-hook-form';
import { DURATION_PRESETS } from './constants';

interface BurnRulesProps {
  viewOnly: boolean;
}

const BurnRules = ({ viewOnly }: BurnRulesProps) => {
  const { control, setValue } = useFormContext();

  return (
    <Box display="flex" flex={1} flexDirection="column">
      <Heading
        size="small"
        weight="semibold"
        color="surface.text.gray.normal"
        marginBottom="spacing.6"
      >
        Burn Rules
      </Heading>
      <Box display="flex" marginBottom="spacing.3" alignItems="center">
        <Text
          marginRight="spacing.2"
          variant="body"
          size="medium"
          weight="semibold"
          color="surface.text.gray.subtle"
        >
          Points expire after
        </Text>
        <Tooltip content="Points accumulated by user expire after this time">
          <TooltipInteractiveWrapper>
            <Box display="flex" flex={1} alignItems="center">
              <InfoIcon size="small" color="interactive.icon.gray.subtle" />
            </Box>
          </TooltipInteractiveWrapper>
        </Tooltip>
      </Box>
      <Box display="flex" marginBottom="spacing.7">
        <Box width="62px">
          <Controller
            name="expiryDuration"
            control={control}
            render={({ field, fieldState }) => (
              <TextInput
                {...field}
                label=""
                placeholder="Enter Duration"
                marginRight="spacing.3"
                errorText={fieldState.error?.message}
                validationState={fieldState.error ? 'error' : 'none'}
                onChange={({ value }) => {
                  setValue(field.name, value ?? '', { shouldValidate: true });
                }}
                isDisabled={viewOnly}
              />
            )}
          />
        </Box>
        <Controller
          name="expiryDurationPreset"
          control={control}
          render={({ field, fieldState }) => (
            <Dropdown selectionType="single">
              <AutoComplete
                {...field}
                label=""
                placeholder="Select Duration"
                onChange={({ values }) => {
                  setValue(field.name, values[0], { shouldValidate: true });
                }}
                errorText={fieldState.error?.message}
                validationState={fieldState.error ? 'error' : 'none'}
                isDisabled={viewOnly}
              />
              <DropdownOverlay>
                <ActionList>
                  {DURATION_PRESETS.map((duration) => (
                    <ActionListItem
                      key={duration.value}
                      value={duration.value}
                      title={duration.label}
                    />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          )}
        />
      </Box>
      <Box display="flex" marginBottom="spacing.3" alignItems="center">
        <Text
          marginRight="spacing.2"
          variant="body"
          size="medium"
          weight="semibold"
          color="surface.text.gray.subtle"
        >
          Minimum order value
        </Text>
        <Tooltip content="Minimum order value to use points">
          <TooltipInteractiveWrapper>
            <Box display="flex" flex={1} alignItems="center">
              <InfoIcon size="small" color="interactive.icon.gray.subtle" />
            </Box>
          </TooltipInteractiveWrapper>
        </Tooltip>
      </Box>
      <Box display="flex">
        <Controller
          name="minOrderValue"
          control={control}
          render={({ field, fieldState }) => (
            <TextInput
              {...field}
              label=""
              placeholder="Enter Value"
              marginRight="spacing.2"
              errorText={fieldState.error?.message}
              validationState={fieldState.error ? 'error' : 'none'}
              onChange={({ value }) => {
                setValue(field.name, value ?? '', { shouldValidate: true });
              }}
              isDisabled={viewOnly}
              type="number"
              leadingIcon={RupeeIcon}
            />
          )}
        />
      </Box>
    </Box>
  );
};

export default BurnRules;
