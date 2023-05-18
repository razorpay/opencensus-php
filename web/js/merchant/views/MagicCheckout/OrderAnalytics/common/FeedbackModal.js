import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { closeModal } from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  Box,
  CheckboxGroup,
  Checkbox,
  TextArea,
  Button,
  Heading,
} from '@razorpay/blade/components';

const OPTIONS = ['Conversion Metrics', 'User Sessions', 'Returning Customers', 'Other'];

const FeedbackModal = ({ showNotification, closeModal }) => {
  const [answers, setAnswers] = useState([]);
  const handleSubmit = () => {
    if (!answers.length) {
      showNotification({
        type: 'error',
        message: 'Select atleast one option from the list',
      });
      return;
    }
    showNotification({
      type: 'success',
      message: 'Thank you for sharing your feedback!',
    });
    closeModal();
  };

  const handleChange = ({ values }) => setAnswers(values);

  return (
    <div className="feedback-modal">
      <ModalHeader title="Share Feedback" extraClass="no-padding" onCloseClick={closeModal} />
      <Box padding="spacing.7">
        <Heading>What widget would you like to see added next?</Heading>
        <CheckboxGroup name="feedback" onChange={handleChange}>
          {OPTIONS.map((opt, idx) => (
            <Checkbox key={idx} value={opt}>
              {opt}
            </Checkbox>
          ))}
        </CheckboxGroup>
        {answers.includes('Other') ? (
          <Box marginTop="spacing.2">
            <TextArea placeholder="Tell us more" maxCharacters={100} />
          </Box>
        ) : null}
        <Box marginTop="spacing.6" display="flex" justifyContent="flex-end">
          <Button variant="primary" onClick={handleSubmit}>
            Submit
          </Button>
        </Box>
      </Box>
    </div>
  );
};
const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(FeedbackModal);
