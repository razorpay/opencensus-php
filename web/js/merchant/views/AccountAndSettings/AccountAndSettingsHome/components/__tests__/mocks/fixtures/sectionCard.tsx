export const defaultProps = {
  title: 'Payment methods',
  icon: 'i-payment-methods',
  iconBackground: 'linear-gradient(161.88deg, #30c5d8 18.69%, #1566f1 90.37%)',
  isMobile: true,
  subSections: [
    {
      title: 'Cards',
      href: '/payment-methods?instrument=card',
    },
    {
      title: 'UPI/QR',
      href: '/payment-methods?instrument=upi',
    },
    {
      title: 'Netbanking',
      href: '/payment-methods?instrument=netbanking',
    },
    {
      title: 'EMI',
      href: '/payment-methods?instrument=emi',
    },
    {
      title: 'Wallet',
      href: '/payment-methods?instrument=wallet',
    },
    {
      title: 'Pay Later',
      href: '/payment-methods?instrument=paylater',
    },
    {
      title: 'International payments',
      href: '/payment-methods?instrument=international',
    },
  ],
};
