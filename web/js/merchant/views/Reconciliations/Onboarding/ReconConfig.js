import React from 'react';
import {
  Card,
  CardBody,
  Radio,
  RadioGroup,
  Box,
  Text,
  ArrowRightIcon,
  Button,
} from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import OnboardingView from './OnboardingView';

export default function ReconConfig({ product, handleCtaClick, isConfigCreation }) {
  const [selectedReconType, setSelectedReconType] = React.useState('');
  const reconTypes = product?.reconTypes ? Object.keys(product?.reconTypes) : [];
  const filteredReconTypes = reconTypes.filter((type) => {
    const reconType = product?.reconTypes[type];
    return reconType.file_config.some((file) => file.show_upload);
  });
  const navigate = useNavigate();
  return (
    <OnboardingView
      title={
        isConfigCreation
          ? `Setup reconciliation process for ${product?.header}`
          : 'New Reconciliation'
      }
      question="What do you want to reconcile?"
      questionSubText={
        isConfigCreation
          ? `Select a recon process for ${product?.header}`
          : 'Select a configuration for reconciliation'
      }
    >
      <Box width="460px">
        <RadioGroup name="select-recon-type" onChange={({ value }) => setSelectedReconType(value)}>
          {filteredReconTypes.map((type) => {
            const data = product.reconTypes[type];
            return (
              <Card key={data.header} padding="spacing.4" elevation="none">
                <CardBody>
                  <Radio
                    value={type}
                    helpText={
                      <Text size="small" color="surface.text.gray.muted">
                        {data.description}
                      </Text>
                    }
                  >
                    <Text weight="semibold" marginBottom="spacing.2">
                      {data.header}
                    </Text>
                  </Radio>
                </CardBody>
              </Card>
            );
          })}
        </RadioGroup>
        <Box display="flex" justifyContent="flex-end" marginTop="spacing.6">
          {isConfigCreation ? (
            <Button
              variant="secondary"
              marginRight="spacing.4"
              onClick={() => navigate('/reconciliations/create-config/2')}
            >
              Back
            </Button>
          ) : null}
          <Button
            iconPosition="right"
            icon={ArrowRightIcon}
            onClick={() => handleCtaClick(selectedReconType)}
            isDisabled={!selectedReconType}
          >
            Proceed
          </Button>
        </Box>
      </Box>
    </OnboardingView>
  );
}
