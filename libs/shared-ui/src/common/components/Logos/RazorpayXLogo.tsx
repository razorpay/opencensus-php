import React from 'react';

const logoMap = {
  light: 'RazorpayX-logo-light.svg',
  dark: 'RazorpayX-logo.svg',
};

export const RazorpayXLogo = ({
  colorScheme = 'light',
}: {
  colorScheme?: 'light' | 'dark';
}): React.ReactElement => {
  const fileName = logoMap[colorScheme] || logoMap.light;

  return (
    <img
      src={`https://x.razorpay.com/dist/assets/img/${fileName}`}
      height="24"
      width="116"
      alt="Xlogo"
    />
  );
};

export default RazorpayXLogo;
