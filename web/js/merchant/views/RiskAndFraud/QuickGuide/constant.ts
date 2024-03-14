export const getQuickGuideData = (businessName) => [
  {
    title: '1. What are frauds and disputes?',
    content:
      'Frauds occur when unauthorized transactions are made with a lost, stolen, compromised or counterfeit card/number. A dispute occurs when a cardholder questions your payment with their card issuer.',
  },
  {
    title: '2. What does it mean for you?',
    content:
      'Financial losses, damage to reputation, increase in operational costs, increased fraud-prevention costs, and high chargeback/dispute ratios are a few adverse effects of frauds and disputes.',
  },
  {
    title: `3. What can you do on ${businessName}?`,
    content:
      'You can identify patterns in disputes or frauds by looking at the highest fraud and dispute contributors. You can also download a lit of all frauds/disputes to further analyse the data and reach out in case you want to set up blacklists.',
  },
];

export const IMAGE_PATH = {
  QUICK_GUIDE_BANNER: '/dist/css/assets/risk-analytics/risk-quick-guide.png',
  QUICK_GUIDE_ICON: '/dist/css/assets/risk-analytics/risk-quick-guide-icon.svg',
};
