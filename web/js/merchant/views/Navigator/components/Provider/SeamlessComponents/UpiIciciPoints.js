import React from 'react';

export const UpiIciciPoints = () => {
  const ICICI_POINTS = [
    'Ensure that you procure a vpa from ICICI bank for your account.',
    'Get the following details from your account manager - Bank MID, Payee VPA, Channel Code, Gateway Reference Id, Checksum Key.',
  ];
  return (
    <ol>
      {ICICI_POINTS.map((point, index) => (
        <li key={index}>{point}</li>
      ))}
    </ol>
  );
};
