import React from 'react';

import {
  Badge,
  Box,
  Button,
  Link,
  Text,
  TextInput,
  ThumbsDownIcon,
  ThumbsUpIcon,
} from '@razorpay/blade/components';
import {
  LeftWrapper,
  TopWrapper,
  Wrapper,
} from 'merchant/views/Settings/Configuration/components/Configuration/styled';

import { LineItemsProps } from 'merchant/views/Settings/Configuration/components/Configuration/types';
import { showNotification } from 'merchant_common/reducers/notifications';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

const LineItems: React.FC<LineItemsProps> = ({
  title,
  subTitle,
  rightChildren,
  extraItems,
  blockData,
  showFeedback,
}) => {
  const [feedback, setFeedback] = React.useState('');
  const [submittedFeedbackType, setSubmittedFeedbackType] = React.useState('not_submitted');
  const { handleFeedbackSubmit } = useCheckoutEditor();
  const isNewTag = blockData?.tags?.some((tag) => tag.tag === 'new');
  if (!showFeedback && submittedFeedbackType !== 'not_submitted') {
    setSubmittedFeedbackType('not_submitted');
  }
  const handleSubmit = async ({ feedback, id, type }) => {
    try {
      await handleFeedbackSubmit({
        feedback,
        block_id: id,
        type,
      });
      blockData.is_feedback_taken = true;
      showNotification({ type: 'success', message: 'Feedback added successfully' });
    } catch (error) {
      const { errors, message } = error as { errors: string[]; message: string };
      showNotification({ type: 'error', message: errors?.[0] ?? message });
    }
  };
  return (
    <Wrapper>
      <TopWrapper>
        <LeftWrapper>
          <Text weight="medium" color="surface.text.gray.normal" variant="body" size="medium">
            {title}
            {isNewTag && (
              <Badge size="small" color={'positive'} emphasis={'intense'} marginLeft={'8px'}>
                New
              </Badge>
            )}
          </Text>
          <Text color="surface.text.gray.muted" variant="body" size="small" weight="regular">
            {subTitle}
          </Text>
        </LeftWrapper>
        {rightChildren}
      </TopWrapper>
      {extraItems}
      {showFeedback && !blockData?.is_feedback_taken && (
        <Box
          backgroundColor={'surface.background.gray.subtle'}
          padding={'spacing.3'}
          display={submittedFeedbackType === 'not_submitted' ? 'flex' : 'block'}
          justifyContent={'space-between'}
          alignItems={'center'}
          borderRadius={'medium'}
        >
          {submittedFeedbackType === 'not_submitted' ? (
            <>
              <Text color="surface.text.gray.muted" variant="body" size="small" weight="regular">
                Did you like this feature?
              </Text>
              <Box gap={'spacing.3'} display={'flex'}>
                <Button
                  variant="primary"
                  color="positive"
                  icon={ThumbsUpIcon}
                  onClick={() => {
                    setSubmittedFeedbackType('positive');
                  }}
                />
                <Button
                  variant="primary"
                  color="negative"
                  icon={ThumbsDownIcon}
                  onClick={() => {
                    setSubmittedFeedbackType('negative');
                  }}
                />
              </Box>
            </>
          ) : (
            <Box display={'flex'} alignItems={'center'} gap={'spacing.3'} width={'100%'}>
              <Box width={'100%'}>
                <TextInput
                  label=""
                  placeholder={
                    submittedFeedbackType === 'positive'
                      ? 'Tell us what you liked'
                      : 'Tell us what you did not like'
                  }
                  onChange={({ value }) => {
                    setFeedback(value || '');
                  }}
                />
              </Box>
              <Link
                variant="button"
                isDisabled={feedback.length === 0}
                onClick={() => {
                  handleSubmit({ feedback, id: blockData.id, type: submittedFeedbackType });
                }}
              >
                Submit
              </Link>
            </Box>
          )}
        </Box>
      )}
    </Wrapper>
  );
};

export default LineItems;
