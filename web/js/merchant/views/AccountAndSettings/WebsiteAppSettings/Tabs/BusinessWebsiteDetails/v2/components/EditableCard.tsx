import React from 'react';
import {
  Card,
  CardBody,
  Box,
  Badge,
  AlertCircleIcon,
  RadioGroup,
  Radio,
  TextInput,
  Button,
  Text,
  CheckIcon,
  Link,
  IconButton,
  EditIcon,
  Theme,
} from '@razorpay/blade/components';
import styled from 'styled-components';

import { PolicyPagesSelection, ValidationState, WebsitePolicyPages } from '../types';
import { track } from '../tracking';
import { getWebsiteCount } from '../utils';
import { isPolicyPageCreatedByRazorpay } from './utils';

const StyledRadioGroup = styled.div(
  ({ theme }: { theme: Theme }) => `
  & [role="radiogroup"] > div > div {
    flex-direction: row;
    gap: ${theme.spacing[4]}px;

    div {
      margin-bottom: 0;
    }
  }
`,
);
const CardWrapper = styled.div<{ isActive: boolean }>(
  ({ theme, isActive }) => `
  & > div {
    border: 1px solid ${isActive ? theme.colors.surface.border.primary.normal : 'none'};
    & > div {
      background: ${isActive ? theme.colors.surface.background.primary.subtle : 'unset'};
    }
  }
`,
);

export default function EditableCard({
  page,
  activeField,
  setActiveField,
  title,
  radioValue,
  setFormState,
  onChange,
  valid,
  value,
  handleFocusOnNext,
  Icon,
}) {
  const isActiveField = activeField === page;

  const getTag = (): React.ReactElement | null => {
    if (radioValue === undefined || isActiveField) {
      return (
        <Badge color="notice" emphasis="subtle" icon={AlertCircleIcon} size="medium">
          Missing
        </Badge>
      );
    } else if (radioValue === PolicyPagesSelection.YES && !isActiveField) {
      return null;
    } else if (radioValue === PolicyPagesSelection.NO && !isActiveField) {
      return (
        <Badge color="information" emphasis="subtle" icon={CheckIcon} size="medium">
          Will be created by Razorpay
        </Badge>
      );
    }
    return null;
  };

  const isValidInput =
    (radioValue === PolicyPagesSelection.YES && !!value) || radioValue === PolicyPagesSelection.NO;
  const isError = valid === ValidationState.ERROR;
  const isRadioSelected = radioValue !== undefined;
  const isEditing = !isValidInput || isActiveField || isError;
  const commonAnalyticsProperties = {
    policyPageName: title,
    websiteCount: getWebsiteCount(window.rzp_user),
  };

  return (
    <CardWrapper
      key={page}
      isActive={isActiveField}
      onFocus={() => setActiveField(page as WebsitePolicyPages)}
    >
      <Card elevation="none" padding="spacing.5" marginY="spacing.3">
        <CardBody>
          <Box display="flex" gap="spacing.4">
            <Icon color="surface.icon.gray.normal" size="large" />
            <Box display="inline-flex" gap="spacing.4" flexDirection="column" flex={1}>
              <Box display="inline-flex" gap="spacing.3">
                <Text
                  variant="body"
                  size="medium"
                  weight="semibold"
                  color="surface.text.gray.normal"
                >
                  {title}
                </Text>
                {getTag()}
              </Box>
              {isEditing ? (
                <>
                  <StyledRadioGroup>
                    <RadioGroup
                      helpText=""
                      label=""
                      name={page}
                      value={radioValue}
                      validationState={
                        isError && !isRadioSelected ? ValidationState.ERROR : ValidationState.NONE
                      }
                      errorText="Please select an option"
                      onChange={({ name, value }: { name: any; value: any }) => {
                        setFormState((formState) => {
                          return {
                            ...formState,
                            [name]: {
                              ...formState[name],
                              radioValue: value as PolicyPagesSelection,
                            },
                          };
                        });
                        // on clicking no, directly go the next step (As there is no continue button)
                        if (value === PolicyPagesSelection.NO) {
                          handleFocusOnNext(page);
                        }
                        track({
                          objectName: 'Policy Page Toggle Option',
                          properties: {
                            ...commonAnalyticsProperties,
                            toggleRZPCreate:
                              value === PolicyPagesSelection.NO
                                ? PolicyPagesSelection.YES
                                : PolicyPagesSelection.NO,
                          },
                        });
                      }}
                    >
                      <Radio value={PolicyPagesSelection.YES} testID="merchant-link">
                        Yes I have the link for this
                      </Radio>
                      <Radio value={PolicyPagesSelection.NO} testID="create-via-rzp">
                        No, create this page for me
                      </Radio>
                    </RadioGroup>
                  </StyledRadioGroup>

                  {radioValue === PolicyPagesSelection.YES &&
                    (activeField === page || (isError && isRadioSelected)) && (
                      <>
                        <TextInput
                          label=""
                          labelPosition="top"
                          name={page}
                          onChange={onChange}
                          type="url"
                          validationState={
                            isError && isRadioSelected
                              ? ValidationState.ERROR
                              : ValidationState.NONE
                          }
                          value={value}
                          errorText="Enter valid website link"
                          key={page}
                          testID="webpage-link"
                        />
                        <Button
                          variant="primary"
                          alignSelf="flex-start"
                          onClick={() => {
                            handleFocusOnNext(page);
                            track({
                              objectName: 'Policy Page Save Option',
                              properties: {
                                ...commonAnalyticsProperties,
                                policyPageUrl: value,
                              },
                            });
                          }}
                        >
                          Save and continue
                        </Button>
                      </>
                    )}
                </>
              ) : (
                <Box display="flex" justifyContent="space-between">
                  {radioValue === PolicyPagesSelection.YES ? (
                    <Link variant="button">{value}</Link>
                  ) : (
                    <Text
                      color="surface.text.gray.muted"
                      variant="body"
                      size="medium"
                      weight="medium"
                    >
                      Not Provided
                    </Text>
                  )}
                  <IconButton
                    icon={EditIcon}
                    accessibilityLabel="Close"
                    onClick={() => setActiveField(page)}
                  />
                </Box>
              )}
            </Box>
          </Box>
        </CardBody>
      </Card>
    </CardWrapper>
  );
}

export function CardDetails({ title, value, Icon }) {
  const isCreatedByRazorpay = isPolicyPageCreatedByRazorpay(value);

  return (
    <Card elevation="none">
      <CardBody>
        <Box display="flex" gap="spacing.4">
          <Icon color="surface.icon.gray.normal" size="large" />
          <Box display="inline-flex" gap="spacing.5" flexDirection="column" flex={1}>
            <Box display="inline-flex" gap="spacing.3">
              <Text variant="body" size="medium" weight="semibold" color="surface.text.gray.normal">
                {title}
              </Text>
              <Badge color="positive" emphasis="subtle" icon={CheckIcon} size="medium">
                {isCreatedByRazorpay ? 'Created by Razorpay' : 'Verified'}
              </Badge>
            </Box>
            <Box display="flex" justifyContent="space-between">
              <Link variant="button">{value}</Link>
            </Box>
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
}
