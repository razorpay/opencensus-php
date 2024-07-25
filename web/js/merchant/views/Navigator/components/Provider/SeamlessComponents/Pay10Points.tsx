import React from 'react';

export const Pay10Points = (): JSX.Element => {
  return (
    <ol type="1">
      <li>For going live with Pay10, procure PayID, salt, and Merchant Hosted Encryption Key.</li>
      <li>
        Additionally, get the following flags enabled at pay10's end based on the your requirements:
        <ul>
          <li>Merchant Hosted</li>
          <li>S2S</li>
        </ul>
      </li>
    </ol>
  );
};
