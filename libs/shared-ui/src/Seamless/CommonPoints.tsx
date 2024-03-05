import React from 'react';
export const CommonPoints = ({ gatewayName }: { gatewayName: string }): JSX.Element => {
  return (
    <>
      <li>
        Write to your {gatewayName} relationship manager asking to enable seamless mode for your
        account. Mention that you are using Razorpay as the technology company to handle sensitive
        card data.
      </li>
      <li>Copy Razorpay in the email and we will provide the supporting document from our end.</li>
      <li>{gatewayName} will enable seamless on your account.</li>
    </>
  );
};
