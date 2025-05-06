import React, { useState, useEffect } from 'react';
import {
  Box,
  Heading,
  Text,
  Button,
  RadioGroup,
  Radio,
  TextArea,
  IconButton,
  CloseIcon,
  Divider,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { toggleHelpWidget as toggleHelpWidgetAction } from 'merchant/reducers/session';
import {
  getItemFromLocalStorage,
  setItemInLocalStorage,
  useDebounce,
  DASHBOARD_ZINDEX_MAP,
} from '@libs/shared-utils';

import {
  trackFeedbackInput,
  trackTaskCompletion,
  trackRating,
  trackSubmit,
  rtuxFeedbackFormKeys,
  trackDismiss,
  RATING_OPTIONS,
  RatingOption,
  trackFeedbackFormLoad,
} from './utils';

export const RtuxFeedbackForm = ({ toggleHelpWidget }): React.ReactElement | null => {
  const [showWidget, setShowWidget] = useState(false);
  const [rating, setRating] = useState<number | null>(null);
  const [taskCompletion, setTaskCompletion] = useState<string | null>(null);
  const [feedback, setFeedback] = useState<string | null>(null);

  const debouncedFeedbackTrackCall = useDebounce(trackFeedbackInput, 2500);

  useEffect(() => {
    const showFeedbackForm = getItemFromLocalStorage(rtuxFeedbackFormKeys.showWidget);
    if (showFeedbackForm === 'true') {
      toggleHelpWidget?.({ showWidget: false });
      setShowWidget(true);
    }
  }, []);

  const handleRating = (value: number) => {
    setRating(value);
    trackRating(value);
  };

  const handleTaskCompletion = (value: string) => {
    setTaskCompletion(value);
    trackTaskCompletion(value);
  };

  const handleFeedback = (value: string) => {
    setFeedback(value);
    debouncedFeedbackTrackCall(value);
  };

  const handleSubmitAndDismiss = ({ isSubmit = false }) => {
    const data = { rating, taskCompletion, feedback };
    if (isSubmit) {
      trackSubmit(data);
    } else {
      trackDismiss(data);
    }
    setItemInLocalStorage(rtuxFeedbackFormKeys.showWidget, 'false');
    setShowWidget(false);
    toggleHelpWidget?.({ showWidget: true });
  };

  useEffect(() => {
    if (showWidget) {
      trackFeedbackFormLoad();
    }
  }, [showWidget]);

  if (!showWidget) return null;

  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      width="400px"
      position="fixed"
      right="spacing.6"
      bottom="spacing.5"
      zIndex={DASHBOARD_ZINDEX_MAP.modal}
      elevation="midRaised"
    >
      <Box display="flex" justifyContent="space-between" alignItems="center" padding="spacing.5">
        <Heading size="small" weight="semibold">
          Help us improve
        </Heading>
        <IconButton
          icon={CloseIcon}
          size="medium"
          accessibilityLabel="Close"
          onClick={() => handleSubmitAndDismiss({ isSubmit: false })}
        />
      </Box>
      <Divider />
      <Box padding="spacing.5">
        <Box>
          <Text size="small" weight="semibold" color="surface.text.gray.subtle">
            Hi there! 👋 Please rate your overall experience with the new dashboard today.
          </Text>
          <Box
            borderWidth="thin"
            borderColor="surface.border.gray.subtle"
            borderRadius="medium"
            marginTop="spacing.3"
            display="flex"
            flexDirection="row"
          >
            {RATING_OPTIONS.map((number, idx) => (
              <Box
                key={number}
                width="100px"
                height="40px"
                display="flex"
                justifyContent="center"
                alignItems="center"
                backgroundColor={rating === number ? 'overlay.background.moderate' : 'transparent'}
                borderWidth="thin"
                borderColor="surface.border.gray.subtle"
                borderLeftWidth={idx === 0 ? 'none' : 'thin'}
                borderBottomWidth={'none'}
                borderTopWidth={'none'}
                borderRightWidth={'none'}
              >
                <RatingOption onClick={() => handleRating(number)}>{`${number}`}</RatingOption>
              </Box>
            ))}
          </Box>

          <Box display="flex" justifyContent="space-between" marginTop="spacing.2">
            <Text size="xsmall" weight="semibold" color="surface.text.gray.muted">
              Extremely Dissatisfied
            </Text>
            <Text size="xsmall" weight="semibold" color="surface.text.gray.muted">
              Extremely Satisfied
            </Text>
          </Box>
        </Box>
        <Box marginTop="spacing.5">
          <Text size="small" weight="semibold" color="surface.text.gray.subtle">
            Were you able to successfully complete your intended tasks on the dashboard today?
          </Text>
          <RadioGroup
            name="taskCompletion"
            marginTop="spacing.3"
            onChange={({ value }) => handleTaskCompletion(value)}
          >
            <Radio value="yes">Yes</Radio>
            <Radio value="no">No</Radio>
            <Radio value="partially">Partially</Radio>
          </RadioGroup>
        </Box>

        <Box marginTop="spacing.5">
          <Text size="small" weight="semibold" color="surface.text.gray.subtle">
            Let us know how we can further improve the experience
          </Text>
          <TextArea
            placeholder="Type your feedback here"
            size="medium"
            marginTop="spacing.3"
            accessibilityLabel="Let us know how we can further improve the experience"
            onChange={({ value }) => handleFeedback(value || '')}
          />
        </Box>

        <Box display="flex" justifyContent="flex-end" marginTop="spacing.5">
          <Button
            variant="primary"
            size="small"
            onClick={() => handleSubmitAndDismiss({ isSubmit: true })}
          >
            Submit
          </Button>
        </Box>
      </Box>
    </Box>
  );
};

export default connect(null, { toggleHelpWidget: toggleHelpWidgetAction })(RtuxFeedbackForm);
