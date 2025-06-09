import React, { useState } from 'react';
import { connect } from 'react-redux';
import { Text, Card, CardBody, Box, Button, TextArea, Link } from '@razorpay/blade/components';
import { Star } from '@dashboards/payments/views/MagicCheckout/MagicDashboard/SupportAndFeedback/components/styled';
import { showNotification } from 'merchant_common/reducers/notifications';
import { submitFeedbackAndRating } from 'merchant/views/MagicCheckout/MagicDashboard/SupportAndFeedback/components/api';

const POSITIVE_RATING_THRESHOLD = 4;
const FEEDBACK_MESSAGES = {
  positive: 'Excellent! We’re glad you love it.',
  negative: 'We apologize for the poor experience. Please let us know what went wrong.',
};

const FeedbackForm = ({ user }) => {
  const [rating, setRating] = useState(0);
  const [hoverRating, setHoverRating] = useState(0);
  const [feedback, setFeedback] = useState('');

  const resetFeedbackForm = () => {
    setRating(0);
    setFeedback('');
  };

  const handleSubmit = async () => {
    try {
      await submitFeedbackAndRating({
        rating,
        feedback,
        merchant_id: user.merchant.id,
        merchant_name: user.merchant.name,
      });
      showNotification({
        type: 'success',
        message: 'Feedback submitted successfully',
      });
      resetFeedbackForm();
    } catch (error) {
      showNotification({
        type: 'error',
        message: 'Something went wrong',
      });
    }
  };

  return (
    <Card margin="spacing.7">
      <CardBody>
        <Text weight="semibold">Share your feedback</Text>
        <Text marginTop="spacing.2" color="surface.text.gray.muted">
          How would you rate your experience with Razorpay?
        </Text>

        <Box display="flex" alignItems="center" justifyContent="center" marginTop="spacing.6">
          {[1, 2, 3, 4, 5].map((value) => (
            <Star
              key={value}
              role="button"
              aria-label={`Rate ${value} out of 5`}
              onClick={() => setRating(value)}
              onMouseEnter={() => setHoverRating(value)}
              onMouseLeave={() => setHoverRating(0)}
              active={(hoverRating === 0 && value <= rating) || value <= hoverRating}
            >
              &#9733;
            </Star>
          ))}
        </Box>

        {rating > 0 && (
          <>
            <Text marginTop="spacing.3" color="surface.text.gray.muted" textAlign="center">
              {rating >= POSITIVE_RATING_THRESHOLD
                ? FEEDBACK_MESSAGES.positive
                : FEEDBACK_MESSAGES.negative}
            </Text>

            {rating >= POSITIVE_RATING_THRESHOLD ? (
              <Link
                href="https://apps.shopify.com/razorpay-checkout#adp-reviews"
                target="_blank"
                display="flex"
                marginTop="spacing.3"
                placeSelf="center"
              >
                Help us by rating us on the Shopify app store
              </Link>
            ) : (
              <TextArea
                label="Please give us detailed feedback on how we can improve"
                placeholder="Type your feedback here"
                value={feedback}
                onChange={(e) => setFeedback(e.value || '')}
                marginTop="spacing.4"
              />
            )}

            <Box display="flex" justifyContent="space-between" marginTop="spacing.6">
              <Button variant="tertiary" marginRight="spacing.4" onClick={resetFeedbackForm}>
                Cancel
              </Button>
              <Button variant="primary" onClick={handleSubmit}>
                Submit Feedback
              </Button>
            </Box>
          </>
        )}
      </CardBody>
    </Card>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(FeedbackForm);
