import { Box, Link, Text } from '@razorpay/blade/components';
import React from 'react';

const MessageBody = {
  settlement: {
    label: 'No Settlements found!',
    action: {
      label: 'Settlements Guide',
      link: 'https://razorpay.com/settlement/',
    },
  },
  payment: {
    label: 'Start collecting payments to get settlements ',
    action: {
      label: 'Payments Guide',
      link: 'https://razorpay.com/docs/payments/payments/dashboard/',
    },
  },
};

const NoSettlement = ({ colSpan }: { colSpan: number }): JSX.Element => {
  const errorMessage = MessageBody.settlement;
  return (
    <tr>
      <td colSpan={colSpan}>
        <Box
          display="flex"
          flexDirection="column"
          alignItems="center"
          gap="spacing.2"
          margin={{
            base: ['spacing.7', 'spacing.0'],
            m: ['spacing.8', 'spacing.0'],
          }}
        >
          <Text size="medium" weight="bold">
            {errorMessage.label}
          </Text>
          {errorMessage.action ? (
            <Link href={errorMessage.action.link} target="_blank">
              {errorMessage.action.label}
            </Link>
          ) : null}
        </Box>
      </td>
    </tr>
  );
};

export default NoSettlement;
