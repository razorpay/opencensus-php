import {
  ActionList,
  ActionListItem,
  Box,
  Checkbox,
  Divider,
  Dropdown,
  DropdownOverlay,
  RupeeIcon,
  SelectInput,
  Text,
  TextInput,
} from '@razorpay/blade/components';
import React from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { USAGE_LIMIT_DURATION_PRESETS } from './constants';

const CampaignLimits = ({ viewOnly }: { viewOnly: boolean }) => {
  const { control, setValue } = useFormContext();

  const [
    watchedCampaignLimitAmountEnabled,
    watchedCampaignLimitActionsEnabled,
    watchedUserLimitAmountEnabled,
    watchedUserLimitActionsEnabled,
  ] = useWatch({
    control,
    name: [
      'campaignLimitAmountEnabled',
      'campaignLimitActionsEnabled',
      'userLimitAmountEnabled',
      'userLimitActionsEnabled',
    ],
  });
  return (
    <>
      {viewOnly &&
      !watchedCampaignLimitAmountEnabled &&
      !watchedCampaignLimitActionsEnabled ? null : (
        <>
          <Divider
            dividerStyle="dashed"
            orientation="horizontal"
            thickness="thin"
            marginY="spacing.7"
          />
          <Text
            variant="body"
            size="medium"
            weight="semibold"
            color="surface.text.gray.subtle"
            marginBottom="spacing.2"
          >
            Campaign Limits
          </Text>
          <Text
            variant="caption"
            size="small"
            color="surface.text.gray.muted"
            marginBottom="spacing.5"
          >
            These limits apply to the entire campaign. When breached, the campaign will pause, and
            no further rewards will be credited.
          </Text>
          <Box display="flex">
            {viewOnly && !watchedCampaignLimitAmountEnabled ? null : (
              <Box
                display="flex"
                flex={1}
                flexDirection="column"
                borderRadius="large"
                borderColor="surface.border.gray.muted"
                marginRight="spacing.3"
              >
                <Box backgroundColor="surface.background.gray.moderate" padding="spacing.4">
                  <Controller
                    name="campaignLimitAmountEnabled"
                    control={control}
                    render={({ field }) => (
                      <Checkbox
                        {...field}
                        isChecked={field.value}
                        onChange={({ isChecked }) => {
                          field.onChange(isChecked);
                        }}
                        isDisabled={viewOnly}
                      >
                        Limit total amount credited
                      </Checkbox>
                    )}
                  />
                </Box>

                <Box padding="spacing.4">
                  <Controller
                    name="campaignLimitAmount"
                    control={control}
                    render={({ field, fieldState }) => (
                      <TextInput
                        {...field}
                        label="Max amount"
                        labelPosition="left"
                        leadingIcon={RupeeIcon}
                        placeholder="Amount"
                        marginBottom="spacing.4"
                        errorText={fieldState.error?.message}
                        validationState={fieldState.error ? 'error' : 'none'}
                        onChange={({ value }) => {
                          setValue(field.name, value, { shouldValidate: true });
                        }}
                        isDisabled={!watchedCampaignLimitAmountEnabled || viewOnly}
                      />
                    )}
                  />
                  <Controller
                    name="campaignLimitAmountPeriod"
                    control={control}
                    render={({ field, fieldState }) => (
                      <Dropdown selectionType="single">
                        <SelectInput
                          {...field}
                          label="Applies"
                          placeholder="Duration"
                          isDisabled={!watchedCampaignLimitAmountEnabled || viewOnly}
                          labelPosition="left"
                          onChange={({ values }) => {
                            setValue(field.name, values[0], { shouldValidate: true });
                          }}
                          errorText={fieldState.error?.message}
                          validationState={fieldState.error ? 'error' : 'none'}
                        />
                        <DropdownOverlay>
                          <ActionList>
                            {USAGE_LIMIT_DURATION_PRESETS.map((duration) => (
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
              </Box>
            )}

            {viewOnly && !watchedCampaignLimitActionsEnabled ? null : (
              <Box
                display="flex"
                flex={1}
                flexDirection="column"
                borderRadius="large"
                borderColor="surface.border.gray.muted"
              >
                <Box backgroundColor="surface.background.gray.moderate" padding="spacing.4">
                  <Controller
                    name="campaignLimitActionsEnabled"
                    control={control}
                    render={({ field }) => (
                      <Checkbox
                        {...field}
                        isChecked={field.value}
                        onChange={({ isChecked }) => {
                          field.onChange(isChecked);
                        }}
                        isDisabled={viewOnly}
                      >
                        Limit no. of actions performed
                      </Checkbox>
                    )}
                  />
                </Box>
                <Box padding="spacing.4">
                  <Controller
                    name="campaignLimitActions"
                    control={control}
                    render={({ field, fieldState }) => (
                      <TextInput
                        {...field}
                        label="Max actions"
                        labelPosition="left"
                        placeholder="No. of"
                        suffix="times"
                        marginBottom="spacing.4"
                        errorText={fieldState.error?.message}
                        validationState={fieldState.error ? 'error' : 'none'}
                        onChange={({ value }) => {
                          setValue(field.name, value, { shouldValidate: true });
                        }}
                        isDisabled={!watchedCampaignLimitActionsEnabled || viewOnly}
                      />
                    )}
                  />
                  <Controller
                    name="campaignLimitActionsPeriod"
                    control={control}
                    render={({ field, fieldState }) => (
                      <Dropdown selectionType="single">
                        <SelectInput
                          {...field}
                          label="Applies"
                          placeholder="Duration"
                          isDisabled={!watchedCampaignLimitActionsEnabled || viewOnly}
                          labelPosition="left"
                          onChange={({ values }) => {
                            setValue(field.name, values[0], { shouldValidate: true });
                          }}
                          errorText={fieldState.error?.message}
                          validationState={fieldState.error ? 'error' : 'none'}
                        />
                        <DropdownOverlay>
                          <ActionList>
                            {USAGE_LIMIT_DURATION_PRESETS.map((duration) => (
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
              </Box>
            )}
          </Box>
        </>
      )}
      {viewOnly && !watchedUserLimitAmountEnabled && !watchedUserLimitActionsEnabled ? null : (
        <>
          <Divider
            dividerStyle="dashed"
            orientation="horizontal"
            thickness="thin"
            marginY="spacing.7"
          />
          <Text
            variant="body"
            size="medium"
            weight="semibold"
            color="surface.text.gray.subtle"
            marginBottom="spacing.2"
          >
            User Limits
          </Text>
          <Text
            variant="caption"
            size="small"
            color="surface.text.gray.muted"
            marginBottom="spacing.5"
          >
            These limits apply to individual users. When breached, the campaign will stop crediting
            rewards to that user.
          </Text>
          <Box display="flex">
            {viewOnly && !watchedUserLimitAmountEnabled ? null : (
              <Box
                display="flex"
                flex={1}
                flexDirection="column"
                borderRadius="large"
                borderColor="surface.border.gray.muted"
                marginRight="spacing.3"
              >
                <Box backgroundColor="surface.background.gray.moderate" padding="spacing.4">
                  <Controller
                    name="userLimitAmountEnabled"
                    control={control}
                    render={({ field }) => (
                      <Checkbox
                        {...field}
                        isChecked={field.value}
                        onChange={({ isChecked }) => {
                          field.onChange(isChecked);
                        }}
                        isDisabled={viewOnly}
                      >
                        Limit total amount credited per user
                      </Checkbox>
                    )}
                  />
                </Box>
                <Box padding="spacing.4">
                  <Controller
                    name="userLimitAmount"
                    control={control}
                    render={({ field, fieldState }) => (
                      <TextInput
                        {...field}
                        label="Max amount"
                        labelPosition="left"
                        leadingIcon={RupeeIcon}
                        placeholder="Amount"
                        marginBottom="spacing.4"
                        errorText={fieldState.error?.message}
                        validationState={fieldState.error ? 'error' : 'none'}
                        onChange={({ value }) => {
                          setValue(field.name, value, { shouldValidate: true });
                        }}
                        isDisabled={!watchedUserLimitAmountEnabled || viewOnly}
                      />
                    )}
                  />
                  <Controller
                    name="userLimitAmountPeriod"
                    control={control}
                    render={({ field, fieldState }) => (
                      <Dropdown selectionType="single">
                        <SelectInput
                          {...field}
                          label="Applies"
                          placeholder="Duration"
                          isDisabled={!watchedUserLimitAmountEnabled || viewOnly}
                          labelPosition="left"
                          onChange={({ values }) => {
                            setValue(field.name, values[0], { shouldValidate: true });
                          }}
                          errorText={fieldState.error?.message}
                          validationState={fieldState.error ? 'error' : 'none'}
                        />
                        <DropdownOverlay>
                          <ActionList>
                            {USAGE_LIMIT_DURATION_PRESETS.map((duration) => (
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
              </Box>
            )}
            {viewOnly && !watchedUserLimitActionsEnabled ? null : (
              <Box
                display="flex"
                flex={1}
                flexDirection="column"
                borderRadius="large"
                borderColor="surface.border.gray.muted"
              >
                <Box backgroundColor="surface.background.gray.moderate" padding="spacing.4">
                  <Controller
                    name="userLimitActionsEnabled"
                    control={control}
                    render={({ field }) => (
                      <Checkbox
                        {...field}
                        isChecked={field.value}
                        onChange={({ isChecked }) => {
                          field.onChange(isChecked);
                        }}
                        isDisabled={viewOnly}
                      >
                        Limit no. of actions per user
                      </Checkbox>
                    )}
                  />
                </Box>
                <Box padding="spacing.4">
                  <Controller
                    name="userLimitActions"
                    control={control}
                    render={({ field, fieldState }) => (
                      <TextInput
                        {...field}
                        label="Max actions"
                        labelPosition="left"
                        placeholder="No. of"
                        suffix="times"
                        marginBottom="spacing.4"
                        errorText={fieldState.error?.message}
                        validationState={fieldState.error ? 'error' : 'none'}
                        onChange={({ value }) => {
                          setValue(field.name, value, { shouldValidate: true });
                        }}
                        isDisabled={!watchedUserLimitActionsEnabled || viewOnly}
                      />
                    )}
                  />
                  <Controller
                    name="userLimitActionsPeriod"
                    control={control}
                    render={({ field, fieldState }) => (
                      <Dropdown selectionType="single">
                        <SelectInput
                          {...field}
                          label="Applies"
                          placeholder="Duration"
                          isDisabled={!watchedUserLimitActionsEnabled || viewOnly}
                          labelPosition="left"
                          onChange={({ values }) => {
                            setValue(field.name, values[0], { shouldValidate: true });
                          }}
                          errorText={fieldState.error?.message}
                          validationState={fieldState.error ? 'error' : 'none'}
                        />
                        <DropdownOverlay>
                          <ActionList>
                            {USAGE_LIMIT_DURATION_PRESETS.map((duration) => (
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
              </Box>
            )}
          </Box>
        </>
      )}
    </>
  );
};

export default CampaignLimits;
