const TNC_DETAILS_CREDIT_LINK = `${window.RAZORPAY_WEBSITE}/docs/payments/dashboard/account-settings/credits/#amount-credits`;
const TNC_DETAILS_PRICING_LINK = `${window.RAZORPAY_WEBSITE}/pricing/`;

interface TncContentType {
  text: string;
  link?: {
    label: string;
    url: typeof TNC_DETAILS_CREDIT_LINK | typeof TNC_DETAILS_PRICING_LINK;
  };
}

const LS_LABELS = {
  NOT_INTERESTED: 'NOT_INTERESTED',
  IMPRESSION_COUNT: 'IMPRESSION_COUNT',
  LAST_IMPRESSION_WITHIN_INTERVAL: 'LAST_IMPRESSION_WITHIN_INTERVAL',
};

/** Time interval wihtin which the asset should'nt be shown */
const IMPRESSION_TIME_INTERVAL = 24 * 60 * 60 * 1000;
const TOUCH_SPEED = 5;

const TNC_CONTENT: Array<TncContentType> = [
  {
    text: 'Amount Credits will be utilized towards the transactions made by your customers as per the terms provided',
    link: {
      label: ' here.',
      url: TNC_DETAILS_CREDIT_LINK,
    },
  },
  {
    text: 'Razorpay shall allow you to subscribe to a Pricing Subscription Plan at its sole discretion.',
  },
  {
    text: 'Amount Credits shall only be applicable to transactions made by customers via domestic payment instruments including UPI, Net Banking, Domestics Wallets, and credit/debit cards supported by Visa, Rupay and Mastercard. All other transactions made via unsupported payment instruments under the Pricing Subscription Plan, including but not limited to International Cards, Cross Border payments, Corporate Credit Cards, AMEX, Diners, Cardless EMI and Pay Later shall be charged at applicable rates.',
  },
  {
    text: 'Applicable transaction charges shall apply for any usage beyond the monthly quota of credits.',
  },
  {
    text: 'GST will be applicable on all subscription fees paid by you as per your subscribed Pricing Subscription Plan. Upon the expiry of your chosen Pricing Subscription Plan, applicable transaction fees and GST will be charged for each transaction.',
  },
  {
    text: 'You can only subscribe to one Pricing Subscription Plan for one business entity/PAN Card.',
  },
  {
    text: 'Masterclass consultant sessions will happen quarterly or half-yearly on request. Razorpay will specify the process to request a Masterclass session after the Razorpay subscription plan is confirmed.',
  },
  {
    text: 'Razorpay reserves the right to modify their subscription benefits/offers by sending a 7 (seven) day intimation email to your business’s registered email id. Any free credits offered to you under a ​​“Pricing Subscription Plan” which is no longer supported will be honoured until the end of that Pricing Subscription Plan. Razorpay reserves the right to add or remove any elements of Premium/Elite support services. Priority/Dedicated Account Management services may be subject to disruptions during the service period due to resource unavailability or other unforeseen reasons.',
  },
  {
    text: 'A Pricing Subscription Plan will be valid for the duration of the Pricing Subscription Plan as specified on the merchant dashboard provided to you by Razorpay.',
  },
  {
    text: 'A Pricing Subscription Plan should be renewed within 5 (five) days of the expiry of the previous Subscription Plan. Non-payment will result in plan expiry of the Pricing Subscription Plan.',
  },
  {
    text: 'You understand and agree that Pricing Subscription Plan cannot be terminated before the expiry of the subscribed Pricing Subscription Plan.',
  },
  {
    text: 'Any updates to the Pricing Subscription Plan, pricing, and settlement timeline upon expiry of a Pricing Subscription Plan, or subscription to a new Pricing Subscription Plan may take up to 48-72 working hours to reflect on your merchant dashboard.',
  },
  {
    text: 'If for any reason, your merchant account is flagged or your settlements are paused in accordance with our internal risk assessment mechanism, you may reach out to us via Support Ticket to receive a prorated refund of the Pricing Subscription Plan fees paid by you.',
  },
  {
    text: 'If the subscription to a Pricing Subscription Plan expires, the standard pricing plan shall automatically and immediately, will become effective. Please reach out to us in case you are not on the standard pricing plan, details as mentioned',
    link: {
      label: ' here.',
      url: TNC_DETAILS_PRICING_LINK,
    },
  },
  {
    text: 'For Jumbo Jet or Spaceship plans, if the subscription to a Pricing Subscription Plan is not renewed, the settlements will be reversed to the standard settlement time frame of T+2 days. Please reach out to us in case of any previously agreed deviations from Razorpay’s standard settlement timelines.',
  },
  {
    text: 'Razorpay would take a minimum of 2 working days for activating your new pricing and settlement plans. Any further delay will be notified accordingly.',
  },
];

export { LS_LABELS, IMPRESSION_TIME_INTERVAL, TOUCH_SPEED, TNC_CONTENT, TNC_DETAILS_CREDIT_LINK };
