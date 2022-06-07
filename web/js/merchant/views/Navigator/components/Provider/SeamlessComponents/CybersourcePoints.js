import React from 'react';

export const CybersourcePoints = () => {
  const CYBERSOURCE_POINTS = [
    'Write to your relationship manager at axis, hdfc or yes bank and mention that you are using Optimizer as the technology partner to handle sensitive card data.',
    'Copy us in the email and we can share the required documentation (if any).',
    'Once account is created at cybersource procure credentials for your account.',
    [
      'Once the configuration is completed you may test a transaction using the below card.',
      <ol type="a" key="innerList">
        <li>Card Number- 4000 0000 0000 0002</li>
        <li>Expiry- Any future date</li>
        <li>CVV- 123</li>
      </ol>,
    ],
    'You should be able to see the above transaction in your Cybersource dashboard too.',
  ];
  return (
    <ol>
      {CYBERSOURCE_POINTS.map((point, index) => (
        <li key={index}>{point}</li>
      ))}
    </ol>
  );
};
