import React, { useContext, useMemo, useState } from 'react';
import {
  Box,
  Heading,
  Text,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  AutoComplete,
  Divider,
  PlusIcon,
  Link,
  ArrowDownIcon,
  Spinner,
} from '@razorpay/blade/components';
import {
  Controller,
  UseFieldArrayAppend,
  UseFieldArrayRemove,
  UseFieldArrayUpdate,
  useWatch,
  useFormContext,
} from 'react-hook-form';
import { useQuery } from '@tanstack/react-query';
import AttributeRow from './AttributeRow';
import { normalizeEvents } from './utils';
import ActionConfiguration from './ActionConfiguration';
import { AttributeFormField, FormData } from './types';
import { fetchCampaignTriggerEvents, fetchCampaignWallets } from '../queries';
import { ModeT } from 'common/services/mode';

interface TriggersAndActionsProps {
  fields: AttributeFormField[];
  append: UseFieldArrayAppend<FormData, 'triggerAttributes'>;
  update: UseFieldArrayUpdate<FormData, 'triggerAttributes'>;
  remove: UseFieldArrayRemove;
  viewOnly?: boolean;
  mode: ModeT;
}

const TriggersAndActions = ({
  fields,
  append,
  remove,
  update,
  viewOnly,
  mode,
}: TriggersAndActionsProps) => {
  const [actionAdded, setActionAdded] = useState(false);
  const { control, setValue } = useFormContext();

  const [watchedTriggerEvent, watchedTriggerEventField, watchedTriggerActionField] = useWatch({
    control,
    name: ['triggerEvent', 'triggerEvent', 'triggerAction'],
  });

  const {
    isLoading: triggerEventsLoading,
    data: triggerEventsData,
    error: triggerEventsError,
  } = useQuery({
    queryKey: ['wallet:campaign:triggerEvents', mode],
    queryFn: () => fetchCampaignTriggerEvents({ mode }),
  });

  const { data: availableWalletsData } = useQuery({
    queryKey: ['wallet:campaign:availableWallets', mode],
    queryFn: () => fetchCampaignWallets({ mode }),
  });

  //all attributes for all events
  const normalizeAttributes = useMemo(() => {
    if (!triggerEventsData) return {};
    return normalizeEvents(triggerEventsData);
  }, [triggerEventsData]);

  const selectedEventName = watchedTriggerEvent
    ? normalizeAttributes[watchedTriggerEvent].name ?? ''
    : '';

  //all attributes for selected event
  const allAttributes = normalizeAttributes[watchedTriggerEvent]?.attributes || {};

  //all attributes minus the ones already selected
  const availableAttributes = useMemo(() => {
    const newlyAvailableAttributes = { ...allAttributes };
    fields.forEach((field) => {
      delete newlyAvailableAttributes[field.field];
    });
    return newlyAvailableAttributes;
  }, [allAttributes, fields]);

  const handleAddAttribute = () => {
    append({ field: '', operator: '', value: '', type: undefined } as AttributeFormField);
  };

  const handleRemoveAttribute = (index: number) => {
    remove(index);
  };

  return (
    <Box display="flex" flex={1} flexDirection="column">
      <Heading
        size="small"
        weight="semibold"
        color="surface.text.gray.normal"
        marginBottom="spacing.6"
      >
        Trigger & Action
      </Heading>
      <Box display="flex" alignItems="center">
        <Box
          display="flex"
          height="spacing.7"
          width="spacing.7"
          borderRadius="max"
          backgroundColor="surface.background.cloud.intense"
          justifyContent="center"
          alignItems="center"
        >
          <Text
            variant="body"
            size="medium"
            weight="semibold"
            color="surface.text.staticWhite.normal"
          >
            A
          </Text>
        </Box>
        <Heading size="small" weight="semibold" marginX="spacing.3">
          If this happens
        </Heading>
        <Text variant="caption" size="small" color="surface.text.gray.muted">
          Trigger
        </Text>
      </Box>
      <Box
        borderColor="surface.border.gray.subtle"
        borderRadius="large"
        borderWidth="thin"
        marginTop="spacing.7"
      >
        <Box display="flex" alignItems="baseline" margin="spacing.4">
          <Controller
            name="triggerEvent"
            control={control}
            render={({ field, fieldState }) => (
              <Dropdown selectionType="single">
                <AutoComplete
                  {...field}
                  label=""
                  placeholder="Select event"
                  onChange={({ values }) => {
                    setValue(field.name, values[0], { shouldValidate: true });
                    remove();
                  }}
                  errorText={
                    fieldState.isValidating || !fieldState.invalid || !fieldState.error?.message
                      ? undefined
                      : fieldState.error?.message
                  }
                  validationState={fieldState.error ? 'error' : 'none'}
                  isDisabled={viewOnly}
                  size="large"
                />
                <DropdownOverlay>
                  {triggerEventsLoading || triggerEventsError || !triggerEventsData ? (
                    <Box
                      display="flex"
                      flex={1}
                      padding="spacing.2"
                      justifyContent="center"
                      alignItems="center"
                    >
                      <Spinner accessibilityLabel="loading" />
                    </Box>
                  ) : (
                    <ActionList>
                      {triggerEventsData.map((event) => (
                        <ActionListItem key={event.id} value={event.id} title={event.name} />
                      ))}
                    </ActionList>
                  )}
                </DropdownOverlay>
              </Dropdown>
            )}
          />

          <Text variant="body" size="large" marginLeft="spacing.4" color="surface.text.gray.subtle">
            is triggered
          </Text>
        </Box>
        {watchedTriggerEventField ? (
          <>
            <Divider
              variant="muted"
              thickness="thin"
              orientation="horizontal"
              dividerStyle="dashed"
            />
            {fields.length > 0 ? (
              <>
                <Divider
                  variant="muted"
                  thickness="thin"
                  orientation="horizontal"
                  dividerStyle="dashed"
                />
                <Box
                  display="flex"
                  flex={1}
                  backgroundColor="surface.background.gray.moderate"
                  alignItems="center"
                  justifyContent="space-between"
                  paddingY="spacing.3"
                  paddingX="spacing.4"
                >
                  <Text
                    variant="body"
                    size="small"
                    weight="semibold"
                    color="surface.text.gray.muted"
                  >
                    With Attributes
                  </Text>
                  <Text variant="caption" size="small" color="surface.text.gray.muted">
                    All inputs are case sensitive
                  </Text>
                </Box>
              </>
            ) : null}
            {fields.map((field, index: number) => (
              <AttributeRow
                field={field}
                key={field.id}
                index={index}
                onRemoveClick={() => handleRemoveAttribute(index)}
                availableAttributes={availableAttributes}
                allAttributes={allAttributes}
                update={update}
                viewOnly={viewOnly}
              />
            ))}
            {viewOnly || fields.length === 4 ? null : (
              <Box
                borderTopWidth="thin"
                borderTopColor="surface.border.gray.muted"
                backgroundColor="surface.background.gray.moderate"
                padding="spacing.4"
              >
                <Link
                  icon={PlusIcon}
                  variant="anchor"
                  color="neutral"
                  size="medium"
                  onClick={handleAddAttribute}
                >
                  Add Attributes
                </Link>
              </Box>
            )}
          </>
        ) : null}
      </Box>
      {viewOnly ? (
        <Box marginBottom="spacing.6" />
      ) : (
        <Box marginY="spacing.6">
          <ArrowDownIcon size="2xlarge" color="surface.icon.gray.muted" />
        </Box>
      )}
      <Box display="flex" alignItems="center" marginBottom="spacing.7">
        <Box
          display="flex"
          height="spacing.7"
          width="spacing.7"
          borderRadius="max"
          backgroundColor="surface.background.cloud.intense"
          justifyContent="center"
          alignItems="center"
        >
          <Text
            variant="body"
            size="medium"
            weight="semibold"
            color="surface.text.staticWhite.normal"
          >
            B
          </Text>
        </Box>
        <Heading size="small" weight="semibold" marginX="spacing.3">
          Do this
        </Heading>
        <Text variant="caption" size="small" color="surface.text.gray.muted">
          Action
        </Text>
      </Box>
      {actionAdded || watchedTriggerActionField || viewOnly ? (
        <ActionConfiguration
          availableWallets={availableWalletsData}
          allAttributes={allAttributes}
          selectedEventName={selectedEventName}
          viewOnly={viewOnly}
        />
      ) : (
        <Box
          height="72px"
          width="100%"
          borderRadius="large"
          borderWidth="thin"
          borderColor="surface.border.gray.subtle"
          borderStyle="dashed"
          display="flex"
          justifyContent="center"
          alignItems="center"
        >
          <Link
            icon={PlusIcon}
            variant="anchor"
            color="neutral"
            size="medium"
            onClick={() => setActionAdded(true)}
          >
            Action
          </Link>
        </Box>
      )}
    </Box>
  );
};

export default TriggersAndActions;
