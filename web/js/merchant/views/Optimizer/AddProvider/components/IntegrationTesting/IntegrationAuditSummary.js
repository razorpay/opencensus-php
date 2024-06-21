import React, { useEffect } from 'react';
import { Box, Heading, Accordion, AccordionItem, Text } from '@razorpay/blade/components';
import isEmpty from 'lodash/isEmpty';

import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

import {
  PaymentFailureAlert,
  PaymentWebhookFailureAlert,
  RefundFailureAlert,
} from './FailureAlerts';
import { InstrumentCoverage } from './InstrumentCoverage';
import { TestingResult } from './TestingResult';
import { getInstrumentCoverageTabsList } from './utils';

export const IntegrationAuditSummary = ({
  currency,
  amount,
  gateway,
  integrationType,
  paymentError,
  isPaymentSuccessfull,
  isWebhookFailure,
  refundResult,
  razorpayCoverage,
  gatewayCoverage,
}) => {
  useEffect(() => {
    trackOptimizerEvents({
      objectName: 'integration audit summary',
      actionName: 'loaded',
      properties: {
        gateway,
        payment_status: isPaymentSuccessfull,
        webhook_failure: isWebhookFailure,
        payment_error: paymentError,
        refund_result: refundResult,
        gateway_coverage: gatewayCoverage,
        razorpay_coverage: razorpayCoverage,
      },
      screen: 'Optimizer Integration Testing',
    });
  });
  return (
    <Box display="flex" flexDirection="column" padding="spacing.8">
      <Heading size="medium">Integration audit summary</Heading>
      <Box width="44rem">
        <Accordion marginTop="spacing.4">
          <AccordionItem title={<Text>Payment testing</Text>}>
            <TestingResult
              type="payment"
              currency={currency}
              amount={amount}
              success={isPaymentSuccessfull && !isWebhookFailure}
              webhookFailure={isWebhookFailure}
            />
            {isWebhookFailure && <PaymentWebhookFailureAlert gateway={gateway} />}
            {!isPaymentSuccessfull && !isWebhookFailure && (
              <PaymentFailureAlert paymentError={paymentError} />
            )}
          </AccordionItem>
        </Accordion>
        {!isEmpty(refundResult) && (
          <Accordion>
            <AccordionItem title={<Text>Refund testing</Text>}>
              <TestingResult
                type="refund"
                currency={currency}
                amount={amount}
                success={refundResult?.refund_success}
              />
              {!refundResult?.refund_success && (
                <RefundFailureAlert gateway={gateway} integrationType={integrationType} />
              )}
            </AccordionItem>
          </Accordion>
        )}
        <Accordion>
          <AccordionItem title={<Text>Instrument coverage</Text>}>
            <InstrumentCoverage
              tabs={getInstrumentCoverageTabsList(razorpayCoverage, gatewayCoverage)}
              razorpayCoverage={razorpayCoverage}
              gateway={gateway}
              gatewayCoverage={gatewayCoverage}
            />
          </AccordionItem>
        </Accordion>
      </Box>
    </Box>
  );
};
