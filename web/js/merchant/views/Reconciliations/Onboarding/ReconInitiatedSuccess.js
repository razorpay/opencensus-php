import React from 'react';
import {
  Title,
  Card,
  CardBody,
  Text,
  Divider,
  Box,
  Button,
  CheckCircleIcon,
  LoaderIcon,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { compose } from 'redux';

import { getSuccessMessage } from './constants';

const ReconInitiatedSuccess = (props) => {
  const navigate = useNavigate();
  const goToDashboard = () => {
    navigate('/reconciliations/dashboard');
  };

  const successMessage = getSuccessMessage(props.countryCode);

  return (
    <Card margin="spacing.6">
      <CardBody>
        <Title>
          <CheckCircleIcon color="brand.secondary.500" />{' '}
          {props.isConfigCreation ? 'Process Setup completed' : 'New Reconciliation Initiated'}
        </Title>
        <Box marginBottom="spacing.6" />
        <Divider marginBottom="spacing.6" />
        <Box width="480px">
          <Box
            backgroundColor="brand.primary.300"
            display="flex"
            borderRadius="max"
            paddingY="spacing.2"
            paddingX="spacing.4"
            marginBottom="spacing.2"
            alignItems="center"
            width="fit-content"
          >
            <LoaderIcon size="small" color="brand.primary.500" marginRight="spacing.2" />
            <Text size="small" color="brand.primary.500">
              Processing
            </Text>
          </Box>
          <Text type="subdued" marginBottom="spacing.4" size="medium">
            {props.isConfigCreation ? successMessage.config : successMessage.runs}
          </Text>
          <Box display="flex" justifyContent="flex-end" marginTop="spacing.6">
            <Button marginRight="spacing.4" onClick={goToDashboard}>
              Go To Dashboard
            </Button>
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};

export default compose(
  connect((state) => ({
    countryCode: state.session.user?.merchant?.country_code,
  })),
)(ReconInitiatedSuccess);
