import React from 'react';
import { Title, Card, CardBody, Heading, Text, Divider, Box } from '@razorpay/blade/components';

export default function OnboardingView({ title, question, questionSubText, children }) {
  return (
    <Card margin="spacing.6">
      <CardBody>
        <Title>{title}</Title>
        <Box marginBottom="spacing.6" />
        <Divider marginBottom="spacing.6" />
        <Heading marginBottom="spacing.2">{question}</Heading>
        <Text type="subdued" marginBottom="spacing.4" size="small">
          {questionSubText}
        </Text>
        {children}
      </CardBody>
    </Card>
  );
}
