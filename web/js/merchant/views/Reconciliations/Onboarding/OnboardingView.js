import React from 'react';
import { Card, CardBody, Heading, Text, Divider, Box } from '@razorpay/blade/components';

export default function OnboardingView({ title, question, questionSubText, children }) {
  return (
    <Card margin="spacing.6">
      <CardBody>
        <Heading size="large">{title}</Heading>
        <Box marginBottom="spacing.6" />
        <Divider marginBottom="spacing.6" />
        <Text marginBottom="spacing.2" size="large">
          {question}
        </Text>
        <Text marginBottom="spacing.4" size="small" color="surface.text.gray.muted">
          {questionSubText}
        </Text>
        {children}
      </CardBody>
    </Card>
  );
}
