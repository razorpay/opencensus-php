import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const defaultProps = {
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
    {
      title: 'Meal Card/Sodexo',
      href: '/payment-methods?instrument=meal-card',
    },
  ],
};

const pricingSectionProps = {
  title: 'Pricing',
  icon: 'i-zap',
  iconBackground: 'linear-gradient(126deg, #C8BFFF 9.01%, #553EDF 98.6%)',
  subSections: [
    {
      title: 'Pricing Plans',
      href: ROUTES_INFO.PRICING_PLANS,
      isNew: true,
    },
  ],
};

export { defaultProps, pricingSectionProps };
