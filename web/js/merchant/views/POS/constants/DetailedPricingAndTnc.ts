import { DetailedPricingModel } from 'merchant/views/POS/types';

export const DETAILED_PRICING: DetailedPricingModel[] = [
  {
    title: null,
    rows: [
      {
        name: 'Particulars',
        value: 'MDR',
      },
    ],
  },
  {
    title: 'Credit Card (Visa/Master/Rupay)',
    rows: [
      {
        name: 'Other segments',
        value: '1.85%',
      },
      {
        name: 'International Card/Corp cards/Amex/Diners',
        value: '3.00%',
      },
    ],
  },
  {
    title: 'Debit Card & BQR through Debit Card (Excl Rupay)',
    rows: [
      {
        name: '<2000*',
        value: '0.40%',
      },
      {
        name: '>2000*',
        value: '0.90%',
      },
      {
        name: 'UPI/Rupay Debit Card',
        value: '0.00%',
      },
    ],
  },
];

export const TERMS_AND_CONDITIONS = [
  {
    criteria: 'Monthly Plan Pricing',
    rows: [
      'The above pricing is inclusive of sim card and paper roll cost.',
      `At the time of deployment, 3 (three) paper rolls will be provided to the Merchant. Subsequently, 2 paper rolls will be provided to the Merchant every month subject to the TID being transacting in the previous month. If in any month, any particular TID did not perform any transactions, then no paper roll will be provided for that particular month.`,
      `Once an order for POS Devices is placed, the same shall be non-cancellable, non-refundable and non-returnable.`,
      `Any additional paper roll requirement at Merchant’s end will be charged @INR 20 (plus applicable GST) per paper roll. Provided, the Merchant will have to place a minimum order of 5 Paper Rolls.`,
      `One Time Set Up fee charged as per the above table shall be non-refundable.`,
      `Merchant shall bear all the repair / replacement charges (including inspection charges) associated with the POS Devices / accessories, in the event such POS Devices / accessories are damaged or lost or becomes inoperable, while being in Merchant’s possession.`,
    ],
  },
  {
    criteria: 'Lifetime Pricing',
    rows: [
      `There will be a 1 (one) year manufacturing warranty on POS Devices. The terms of warranty shall be in accordance with OEM’s policy.`,
      ` Once an order for POS Devices is placed, the same shall be non-cancellable, non-refundable and non-returnable.`,
      `The Merchant shall ensure a total transaction volume of INR 10,000 per POS Device per month. In any particular, if a merchant fails to ensure transaction volume of INR 10,000, then an additional fee of INR 300 (plus applicable tax) shall be charged for that month.`,
      `Any paper roll requirement at Merchant’s end will be charged @INR 20 (plus applicable GST) per paper roll. Provided, the Merchant will have to place a minimum order of 5 Paper Rolls.`,
      `Merchant shall bear all the repair / replacement charges (including inspection charges) associated with the POS Devices / accessories, in the event such POS Devices / accessories are damaged or lost or becomes inoperable, while being in Merchant’s possession.`,
    ],
  },
];
