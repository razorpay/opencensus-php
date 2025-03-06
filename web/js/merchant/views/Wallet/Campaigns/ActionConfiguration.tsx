import React, { useMemo } from 'react';
import {
  ActionList,
  ActionListItem,
  AutoComplete,
  Box,
  Checkbox,
  Chip,
  ChipGroup,
  Divider,
  Dropdown,
  DropdownOverlay,
  Text,
  TextInput,
  Spinner,
} from '@razorpay/blade/components';
import { Controller, useWatch, useFormContext } from 'react-hook-form';
import { TRIGGER_ACTIONS, NUMBER_TYPES } from './constants';
import { Attribute, Wallet, CreditTypeEnum } from './types';
import { formatAmount } from 'common/utils/rzp-utils';

interface ActionConfigurationProps {
  availableWallets?: Array<Wallet>;
  allAttributes: Record<string, Attribute>;
  selectedEventName: string;
  viewOnly?: boolean;
}

//Currenlty only supports credit wallet action
const ActionConfiguration = ({
  viewOnly,
  allAttributes,
  availableWallets,
  selectedEventName,
}: ActionConfigurationProps) => {
  const { control, setValue } = useFormContext();
  const [
    watchedTriggerAction,
    watchedCreditType,
    watchedTriggerEvent,
    watchedSelectedWallet,
    watchedNoMaxLimit,
  ] = useWatch({
    control,
    name: ['triggerAction', 'creditType', 'triggerEvent', 'selectedWallet', 'noMaxLimit'],
  });

  const selectedWallet = availableWallets?.find((wallet) => wallet.id === watchedSelectedWallet);

  const AVAILABLE_CONFIG_ATTRIBUTES = useMemo(
    () =>
      Object.entries(allAttributes)
        .filter(([_, value]) => NUMBER_TYPES.includes(value.type))
        .reduce((acc, [key, value]) => {
          acc[key] = value;
          return acc;
        }, {}),
    [allAttributes],
  );

  return (
    <Box borderColor="surface.border.gray.subtle" borderRadius="large" borderWidth="thin">
      <Box display="flex" alignItems="baseline" margin="spacing.4">
        <Controller
          name="triggerAction"
          control={control}
          render={({ field, fieldState }) => (
            <Dropdown selectionType="single">
              <AutoComplete
                {...field}
                label=""
                placeholder="Select action"
                onChange={({ values }) => {
                  setValue(field.name, values[0], { shouldValidate: true });
                }}
                errorText={fieldState.error?.message}
                validationState={fieldState.error ? 'error' : 'none'}
                isDisabled={viewOnly}
                size="large"
              />
              <DropdownOverlay>
                <ActionList>
                  {Object.keys(TRIGGER_ACTIONS).map((action) => (
                    <ActionListItem
                      key={action}
                      value={action}
                      title={TRIGGER_ACTIONS[action].label}
                    />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          )}
        />
        <Controller
          name="selectedWallet"
          control={control}
          render={({ field, fieldState }) => (
            <Dropdown selectionType="single" marginLeft="spacing.4">
              <AutoComplete
                {...field}
                label=""
                placeholder="Select wallet"
                onChange={({ values }) => {
                  setValue(field.name, values[0], { shouldValidate: true });
                }}
                errorText={fieldState.error?.message}
                validationState={fieldState.error ? 'error' : 'none'}
                isDisabled={viewOnly}
                size="large"
              />
              <DropdownOverlay>
                {availableWallets && availableWallets.length > 0 ? (
                  <ActionList>
                    {availableWallets.map((wallet) => (
                      <ActionListItem key={wallet.id} value={wallet.id} title={wallet.name} />
                    ))}
                  </ActionList>
                ) : (
                  <Box
                    display="flex"
                    flex={1}
                    padding="spacing.2"
                    justifyContent="center"
                    alignItems="center"
                  >
                    <Spinner accessibilityLabel="loading" />
                  </Box>
                )}
              </DropdownOverlay>
            </Dropdown>
          )}
        />
      </Box>
      {watchedTriggerAction && watchedSelectedWallet ? (
        <>
          <Divider
            variant="muted"
            thickness="thin"
            dividerStyle="dashed"
            orientation="horizontal"
            marginBottom="spacing.4"
          />
          <Box
            display="flex"
            flexDirection="column"
            borderBottomWidth="thin"
            borderBottomColor="surface.border.gray.muted"
            paddingLeft="spacing.4"
          >
            <Controller
              name="creditType"
              control={control}
              render={({ field, fieldState }) => (
                <ChipGroup
                  value={field.value}
                  label="Credit Type"
                  labelPosition="left"
                  onChange={({ values }) => {
                    field.onChange(values[0]);
                  }}
                  errorText={fieldState.error?.message}
                  validationState={fieldState.error ? 'error' : 'none'}
                  isDisabled={viewOnly}
                >
                  <Chip value={CreditTypeEnum.FLAT}>Flat</Chip>
                  <Chip value={CreditTypeEnum.PERCENTAGE}>Percentage</Chip>
                </ChipGroup>
              )}
            />
          </Box>
          <Box backgroundColor="surface.background.gray.moderate" paddingY="spacing.4">
            <Box display="flex" flex={1} paddingX="spacing.4" alignItems="baseline">
              <Controller
                name="creditAmount"
                control={control}
                render={({ field, fieldState }) => (
                  <TextInput
                    {...field}
                    label="Credit Amount"
                    labelPosition="left"
                    suffix={watchedCreditType === CreditTypeEnum.PERCENTAGE ? '%' : undefined}
                    onChange={({ value }) => {
                      setValue(field.name, value ?? '', { shouldValidate: true });
                    }}
                    errorText={fieldState.error?.message}
                    validationState={fieldState.error ? 'error' : 'none'}
                    type="number"
                    isDisabled={viewOnly}
                    helpText={
                      watchedCreditType === CreditTypeEnum.FLAT && field.value
                        ? `Equivalent to ${formatAmount(field.value, true, 'INR')}`
                        : undefined
                    }
                  />
                )}
              />
              {watchedCreditType === CreditTypeEnum.FLAT ? (
                <Text marginLeft="spacing.4">{selectedWallet?.name ?? ''}</Text>
              ) : null}
              {watchedCreditType === CreditTypeEnum.PERCENTAGE ? (
                <>
                  <Text marginX="spacing.4">of</Text>
                  <Box display="flex" flex={1}>
                    <TextInput
                      isDisabled
                      value={selectedEventName}
                      label=""
                      marginRight="spacing.3"
                    />
                    <Controller
                      name="triggerActionAttribute"
                      control={control}
                      render={({ field, fieldState }) => (
                        <Dropdown selectionType="single">
                          <AutoComplete
                            {...field}
                            label=""
                            placeholder="Select attribute"
                            onChange={({ values }) => {
                              setValue(field.name, values[0], {
                                shouldValidate: true,
                              });
                            }}
                            errorText={fieldState.error?.message}
                            validationState={fieldState.error ? 'error' : 'none'}
                            isDisabled={viewOnly}
                          />
                          <DropdownOverlay>
                            <ActionList>
                              {Object.keys(AVAILABLE_CONFIG_ATTRIBUTES).map((attribute) => (
                                <ActionListItem
                                  key={attribute}
                                  value={attribute}
                                  title={attribute}
                                />
                              ))}
                            </ActionList>
                          </DropdownOverlay>
                        </Dropdown>
                      )}
                    />
                  </Box>
                </>
              ) : null}
            </Box>
            {watchedCreditType === CreditTypeEnum.PERCENTAGE ? (
              <Box display="flex" paddingX="spacing.4" marginTop="spacing.6">
                <Box display="flex" alignItems="baseline">
                  <Controller
                    name="maxCredit"
                    control={control}
                    render={({ field, fieldState }) => (
                      <TextInput
                        {...field}
                        label="Max credit"
                        labelPosition="left"
                        onChange={({ value }) => {
                          setValue(field.name, value ?? '', { shouldValidate: true });
                        }}
                        errorText={fieldState.error?.message}
                        validationState={fieldState.error ? 'error' : 'none'}
                        type="number"
                        isDisabled={watchedNoMaxLimit || viewOnly}
                        helpText={
                          field.value
                            ? `Equivalent to ${formatAmount(field.value, true, 'INR')}`
                            : undefined
                        }
                      />
                    )}
                  />
                  <Text marginLeft="spacing.4" marginRight="spacing.6">
                    {selectedWallet?.name ?? ''}
                  </Text>
                  <Controller
                    name="noMaxLimit"
                    control={control}
                    render={({ field, fieldState }) => {
                      //Filter out value, not needed for Checkbox, plus causing issue as Blade's checkbox is accepting string as value but we are storing boolean
                      const { value, ...rest } = field;
                      return (
                        <Checkbox
                          {...rest}
                          isChecked={field.value}
                          errorText={fieldState.error?.message}
                          validationState={fieldState.error ? 'error' : 'none'}
                          onChange={({ isChecked }) => {
                            field.onChange(isChecked);
                            setValue('maxCredit', '', { shouldValidate: true });
                          }}
                          isDisabled={viewOnly}
                        >
                          No Max Limit
                        </Checkbox>
                      );
                    }}
                  />
                </Box>
              </Box>
            ) : null}
          </Box>
        </>
      ) : null}
    </Box>
  );
};

export default ActionConfiguration;
