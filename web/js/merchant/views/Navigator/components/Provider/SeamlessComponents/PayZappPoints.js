import React from 'react';

export const PayZappPoints = () => {
  const PAYZAPP_POINTS = [
    'Please reach out to your HDFC bank POC to procure terminal and necessary details like TID, MID to configure the terminal.',
  ];
  return (
    <ol>
      {PAYZAPP_POINTS.map((point, index) => (
        <li key={index}>{point}</li>
      ))}
    </ol>
  );
};
