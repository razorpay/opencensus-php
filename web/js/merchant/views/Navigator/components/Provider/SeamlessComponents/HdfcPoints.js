import React from 'react';

export const HdfcPoints = () => {
  const HDFC_POINTS = [
    'Write to your relationship manager at HDFC and mention that you would be processing the transactions through Optimizer.',
    'Copy us in the email and we will share the required documentation from our side.',
    'Your SSL account needs to be converted to Tranportal account.',
    'Ask your account manager to share the following credentials with you: Bank MID, Bank TID, Tranportal Password, ME Code, Payee VPA (for UPI).',
    'There are 14 test scenarios that need to be cleared by HDFC Bank and you need to expose your integration environment to HDFC to test.',
    'Once test cases are cleared they may initiate security testing.',
    'Once HDFC has completed testing they will release the production credentials.',
  ];
  return (
    <ol>
      {HDFC_POINTS.map((point, index) => (
        <li key={index}>{point}</li>
      ))}
    </ol>
  );
};
