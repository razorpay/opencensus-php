import { DetailedPricingModel, PricingTypes, TncObject } from 'merchant/views/POS/types';

export const OFFER_DETAILED_PRICING: DetailedPricingModel[] = [
  {
    title: null,
    banner: 'offer',
    rows: [
      {
        name: 'Particulars',
        value: 'MDR',
      },
    ],
  },
  {
    title: '',
    rows: [
      {
        name: 'All segments',
        value: '0%',
        text: 'charges upto ₹1L transactions',
      },
    ],
  },
];

export const DETAILED_PRICING: DetailedPricingModel[] = [
  {
    title: null,
    banner: 'info',
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
        name: 'Grocery Stores & Supermarkets',
        value: '1.30%',
      },
      {
        name: 'Utility, Govt., Education, Fuel, Insurance',
        value: '1.10%',
      },
      {
        name: 'Other segments',
        value: '1.85%',
      },
      {
        name: 'International Card/Corp cards/Amex/Diners',
        value: '3.00%',
      },
      {
        name: 'Rupay Credit Card on UPI',
        value: '2.00%',
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

export const WD10_DETAILED_OFFER_PRICING: DetailedPricingModel[] = [
  {
    title: null,
    banner: 'info',
    rows: [
      {
        name: 'Particulars',
        value: 'MDR',
      },
    ],
  },
  {
    title: null,
    rows: [
      {
        name: 'UPI',
        value: '0.00%',
      },
      {
        name: 'Rupay Credit Card on UPI',
        value: '2.00%',
      },
    ],
  },
];

type TncDictionary = {
  criteria: string;
  pricingType: PricingTypes;
  rows: TncObject[];
};

export const TERMS_AND_CONDITIONS: TncDictionary[] = [
  {
    criteria: 'Monthly Plan Pricing',
    pricingType: 'monthly',
    rows: [
      {
        tncType: 'offer',
        text: ' The Merchant hereby commits to sign up for a minimum period of 12 months (“Lock-in Period”). The Merchant shall ensure timely payment of Device rentals during the said Lock-in Period.',
      },
      {
        tncType: 'offer',
        text: `The Merchant understands that in the first instance (for both, transactions upto INR 1 lakh as well as subsequent transactions), the transaction fee (MDR) will be deducted as per the general transaction fee (MDR) rates. Subsequently, the differential transaction fee (MDR) shall be added back to the merchant settlement account in the subsequent month in the form of cashback.`,
      },
      {
        tncType: 'offer',
        text: `The Merchant understands that the ownership of the Devices shall remain with Razorpay and all the terms & conditions relating to Devices enumerated in the general terms & conditions shall be applicable.
      `,
      },
      {
        tncType: 'offer',
        text: `The Merchant agrees and understands that the offer prices extended by Razopray are subject to the fulfillment of the condition enumerated under this Offer Terms and Merchant Terms & Conditions.`,
      },
      {
        tncType: 'nonOffer',
        text: 'The above pricing is inclusive of sim card and paper roll cost.',
      },
      {
        tncType: 'normal',
        text: 'At the time of deployment, 3 (three) paper rolls will be provided to the Merchant. Subsequently, 2 paper rolls will be provided to the Merchant every month subject to the TID being transacting in the previous month. If in any month, any particular TID did not perform any transactions, then no paper roll will be provided for that particular month.',
      },
      {
        tncType: 'normal',
        text: 'Once an order for POS Devices is placed, the same shall be non-cancellable, non-refundable and non-returnable.',
      },
      {
        tncType: 'normal',
        text: 'Any additional paper roll requirement at Merchant’s end will be charged @INR 20 (plus applicable GST) per paper roll. Provided, the Merchant will have to place a minimum order of 5 Paper Rolls.',
      },
      {
        tncType: 'nonOffer',
        text: 'One Time Set Up fee charged as per the above table shall be non-refundable.',
      },
      {
        tncType: 'normal',
        text: 'Merchant shall bear all the repair / replacement charges (including inspection charges) associated with the POS Devices / accessories, in the event such POS Devices / accessories are damaged or lost or becomes inoperable, while being in Merchant’s possession.',
      },
      {
        tncType: 'nonOffer',
        text: 'EMI processing fees to be charged separately.',
      },
    ],
  },
  {
    criteria: 'Lifetime Pricing',
    pricingType: 'lifetime',
    rows: [
      {
        tncType: 'offer',
        text: `In relation to the POS Android Devices (A910, A50 mini) and Soundbox/QR, the monthly rental will be completely waived off for the first 24 months (for POS Android) and 18 months (for Soundbox / QR). Subsequent to the expiry of 24 months or 18 months (as the case may be), if the monthly transaction volume processed by the Merchant is less than INR 50,000 (per TID per month), then the Merchant shall pay monthly device rental fee of: `,
        subpoints: [
          `INR 99 (plus GST) per Device per month, in relation to POS Android Devices.`,
          `INR 49 (plus GST) per device per month in relation to Soundbox / QR.`,
          `For the purpose of this clause, the duration of 24 months / 18 months (as the case may be), shall be counted from the date of device installation at Merchant location.`,
        ],
      },
      {
        tncType: 'offer',
        text: `The one-time Set-up Fee as prescribed above shall be paid by the Merchant in advance and shall be collected    before or during the Merchant terms & conditions sign off.`,
      },
      {
        tncType: 'offer',
        text: `The Merchant understands that in the first instance (for both, transactions upto INR 1 lakh as well as subsequent transactions), the transaction fee (MDR) will be deducted as per the general transaction fee (MDR) rates. Subsequently, the differential transaction fee (MDR) shall be added back to the merchant settlement account in the subsequent month in the form of cashback.`,
      },
      {
        tncType: 'offer',
        text: `The Merchant understands that the ownership of the Devices shall remain with Razorpay and all the terms & conditions relating to Devices enumerated in the general terms & conditions shall be applicable.`,
      },
      {
        tncType: 'offer',
        text: `The Merchant agrees and understands that the offer prices extended by Razopray are subject to the fulfillment of the condition enumerated under this Offer Terms and Merchant Terms & Conditions.
        `,
      },
      {
        tncType: 'normal',
        text: 'There will be a 1 (one) year manufacturing warranty on POS Devices. The terms of warranty shall be in accordance with OEM’s policy.',
      },
      {
        tncType: 'normal',
        text: 'Once an order for POS Devices is placed, the same shall be non-cancellable, non-refundable and non-returnable.',
      },
      {
        tncType: 'nonOffer',
        text: 'The Merchant shall ensure a total transaction volume of INR 10,000 per POS Device per month. In any particular, if a merchant fails to ensure transaction volume of INR 10,000, then an additional fee of INR 300 (plus applicable tax) shall be charged for that month.',
      },
      {
        tncType: 'normal',
        text: 'Any paper roll requirement at Merchant’s end will be charged @INR 20 (plus applicable GST) per paper roll. Provided, the Merchant will have to place a minimum order of 5 Paper Rolls.',
      },
      {
        tncType: 'normal',
        text: 'Merchant shall bear all the repair / replacement charges (including inspection charges) associated with the POS Devices / accessories, in the event such POS Devices / accessories are damaged or lost or becomes inoperable, while being in Merchant’s possession.',
      },
      {
        tncType: 'nonOffer',
        text: 'EMI processing fees to be charged separately.',
      },
    ],
  },
];

type SoundboxOfferTnc = {
  criteria: string;
  pricingType: PricingTypes;
  rows: TncObject[];
};

export const SOUNDBOX_OFFER_TNC: SoundboxOfferTnc[] = [
  {
    criteria: 'Monthly Plan Pricing',
    pricingType: 'monthly',
    rows: [
      {
        tncType: 'offer',
        text: 'Prices are exclusive of GST',
      },
      {
        tncType: 'offer',
        text: 'Pricing is inclusive of sim cost ',
      },
      {
        tncType: 'offer',
        text: 'Once ordered, Device is non-returnable and non-refundable.',
      },
      {
        tncType: 'offer',
        text: 'Merchant specific integration charges (if any) are separately applicable on case to case basis based on effort',
      },
      {
        tncType: 'offer',
        text: '1 year manufacturing warranty for the device',
      },
      {
        tncType: 'nonOffer',
        text: 'Prices are exclusive of GST',
      },
      {
        tncType: 'nonOffer',
        text: 'Pricing is inclusive of sim cost ',
      },
      {
        tncType: 'nonOffer',
        text: 'Once ordered, Device is non-returnable and non-refundable.',
      },
      {
        tncType: 'nonOffer',
        text: 'Merchant specific integration charges (if any) are separately applicable on case to case basis based on effort',
      },
      {
        tncType: 'nonOffer',
        text: '1 year manufacturing warranty for the device',
      },
    ],
  },
  {
    criteria: 'Lifetime Pricing',
    pricingType: 'lifetime',
    rows: [
      {
        tncType: 'offer',
        text: 'Prices are exclusive of GST',
      },
      {
        tncType: 'offer',
        text: 'Pricing is inclusive of sim cost ',
      },
      {
        tncType: 'offer',
        text: 'Once ordered, Device is non-returnable and non-refundable.',
      },
      {
        tncType: 'offer',
        text: 'Merchant specific integration charges (if any) are separately applicable on case to case basis based on effort.',
      },
      {
        tncType: 'offer',
        text: '1 year manufacturing warranty for the device',
      },
      {
        tncType: 'nonOffer',
        text: 'Prices are exclusive of GST',
      },
      {
        tncType: 'nonOffer',
        text: 'Pricing is inclusive of sim cost ',
      },
      {
        tncType: 'nonOffer',
        text: 'Once ordered, Device is non-returnable and non-refundable.',
      },
      {
        tncType: 'nonOffer',
        text: 'Once ordered, Device is non-returnable and non-refundable.',
      },
      {
        tncType: 'nonOffer',
        text: 'Merchant specific integration charges (if any) are separately applicable on case to case basis based on effort.',
      },
      {
        tncType: 'nonOffer',
        text: '1 year manufacturing warranty for the device',
      },
    ],
  },
];
