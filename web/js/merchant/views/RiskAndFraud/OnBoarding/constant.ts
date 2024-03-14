export const getFeaturesData = (businessName) => [
  {
    icon: '/dist/css/assets/risk-analytics/risk-guide-1.svg',
    title: 'What are frauds and disputes?',
    desc: `Frauds occur when unauthorized transactions are made with a lost, stolen, compromised or counterfeit card/number. A dispute occurs when an account owner contacts their bank to contest a payment to you for a number of possible reasons.`,
  },
  {
    icon: '/dist/css/assets/risk-analytics/risk-guide-2.svg',
    title: 'What does it mean for you?',
    desc: 'Financial losses, damage to reputation, increase in operational costs, increased fraud-prevention costs, and high chargeback/dispute ratios are a few adverse effects of frauds and disputes.',
  },
  {
    icon: '/dist/css/assets/risk-analytics/risk-guide-3.svg',
    title: `What can you do on ${businessName}?`,
    desc: 'You can identify patterns in disputes or frauds by looking at the highest fraud and dispute contributors. You can also download a lit of all frauds/disputes to further analyse the data and reach out in case you want to set up blacklists.',
  },
];

export const FEATURES_LINKS = [
  {
    label: 'Know more',
    url: 'https://razorpay.com/docs/risk-visibility/',
  },
];
