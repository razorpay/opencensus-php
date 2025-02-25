enum PaymentOptionValues {
  BONUS = 'BONUS',
  DUPLICATE_CARD = 'DUPLICATE_CARD',
  EXCHANGE = 'EXCHANGE',
  GUEST_CERTIFICATE = 'GUEST_CERTIFICATE',
  OTHERS = 'OTHERS',
  PLATINUM_ENROLLMENT = 'PLATINUM_ENROLLMENT',
  PLATINUM_KIT = 'PLATINUM_KIT',
  PLATINUM_RENEWALS = 'PLATINUM_RENEWALS',
  RCI = 'RCI',
  RCI_DIRECTORY = 'RCI_DIRECTORY',
  RENEWALS = 'RENEWALS',
  RENEWAL_EXTENSION = 'RENEWAL_EXTENSION',
  RENTAL = 'RENTAL',
  SELF_ENROLLMENT = 'SELF_ENROLLMENT',
  SPACEBANK_EXTENSION = 'SPACEBANK_EXTENSION',
  ST_COMP_BOOK = 'ST_COMP_BOOK',
  TPRO1 = 'TPRO1',
  TPRO2 = 'TPRO2',
  NB = 'NB',
  RNC = 'RNC',
  RNNC = 'RNNC',
  LOAD = 'LOAD',
  SHPR = 'SHPR',
  TRVL = 'TRVL',
  ENDO = 'ENDO',
  GRP = 'GRP',
  CHBO = 'CHBO',
  RECOR = 'RECOR',
}

interface PaymentOption {
  label: string;
  value: PaymentOptionValues;
}

interface PaymentLink {
  name: string;
  type: string;
  options: PaymentOption[];
}

export const PAYMENT_LINKS_CUSTOM_NOTES: Record<string, PaymentLink> = {
  SRI_RAM_MID: {
    name: 'SriRam',
    type: 'Business Segment',
    options: [
      { label: 'BONUS', value: PaymentOptionValues.BONUS },
      { label: 'DUPLICATE_CARD', value: PaymentOptionValues.DUPLICATE_CARD },
      { label: 'EXCHANGE', value: PaymentOptionValues.EXCHANGE },
      { label: 'GUEST_CERTIFICATE', value: PaymentOptionValues.GUEST_CERTIFICATE },
      { label: 'OTHERS', value: PaymentOptionValues.OTHERS },
      { label: 'PLATINUM_ENROLLMENT', value: PaymentOptionValues.PLATINUM_ENROLLMENT },
      { label: 'PLATINUM_KIT', value: PaymentOptionValues.PLATINUM_KIT },
      { label: 'PLATINUM_RENEWALS', value: PaymentOptionValues.PLATINUM_RENEWALS },
      { label: 'RCI', value: PaymentOptionValues.RCI },
      { label: 'RCI_DIRECTORY', value: PaymentOptionValues.RCI_DIRECTORY },
      { label: 'RENEWALS', value: PaymentOptionValues.RENEWALS },
      { label: 'RENEWAL_EXTENSION', value: PaymentOptionValues.RENEWAL_EXTENSION },
      { label: 'RENTAL', value: PaymentOptionValues.RENTAL },
      { label: 'SELF_ENROLLMENT', value: PaymentOptionValues.SELF_ENROLLMENT },
      { label: 'SPACEBANK_EXTENSION', value: PaymentOptionValues.SPACEBANK_EXTENSION },
      { label: 'ST_COMP_BOOK', value: PaymentOptionValues.ST_COMP_BOOK },
      { label: 'TPRO1', value: PaymentOptionValues.TPRO1 },
      { label: 'TPRO2', value: PaymentOptionValues.TPRO2 },
    ],
  },

  RCI_MID: {
    name: 'RCI',
    type: 'Business Segment',
    options: [
      { label: 'BONUS', value: PaymentOptionValues.BONUS },
      { label: 'DUPLICATE_CARD', value: PaymentOptionValues.DUPLICATE_CARD },
      { label: 'EXCHANGE', value: PaymentOptionValues.EXCHANGE },
      { label: 'GUEST_CERTIFICATE', value: PaymentOptionValues.GUEST_CERTIFICATE },
      { label: 'OTHERS', value: PaymentOptionValues.OTHERS },
      { label: 'PLATINUM_ENROLLMENT', value: PaymentOptionValues.PLATINUM_ENROLLMENT },
      { label: 'PLATINUM_KIT', value: PaymentOptionValues.PLATINUM_KIT },
      { label: 'PLATINUM_RENEWALS', value: PaymentOptionValues.PLATINUM_RENEWALS },
      { label: 'RCI', value: PaymentOptionValues.RCI },
      { label: 'RCI_DIRECTORY', value: PaymentOptionValues.RCI_DIRECTORY },
      { label: 'RENEWALS', value: PaymentOptionValues.RENEWALS },
      { label: 'RENEWAL_EXTENSION', value: PaymentOptionValues.RENEWAL_EXTENSION },
      { label: 'RENTAL', value: PaymentOptionValues.RENTAL },
      { label: 'SELF_ENROLLMENT', value: PaymentOptionValues.SELF_ENROLLMENT },
      { label: 'SPACEBANK_EXTENSION', value: PaymentOptionValues.SPACEBANK_EXTENSION },
      { label: 'ST_COMP_BOOK', value: PaymentOptionValues.ST_COMP_BOOK },
      { label: 'TPRO1', value: PaymentOptionValues.TPRO1 },
      { label: 'TPRO2', value: PaymentOptionValues.TPRO2 },
    ],
  },

  APOLLO_MID: {
    name: 'Apollo',
    type: 'Scenario',
    options: [
      { label: 'NB', value: PaymentOptionValues.NB },
      { label: 'RNC', value: PaymentOptionValues.RNC },
      { label: 'RNNC', value: PaymentOptionValues.RNNC },
      { label: 'LOAD', value: PaymentOptionValues.LOAD },
      { label: 'SHPR', value: PaymentOptionValues.SHPR },
      { label: 'TRVL', value: PaymentOptionValues.TRVL },
      { label: 'ENDO', value: PaymentOptionValues.ENDO },
      { label: 'GRP', value: PaymentOptionValues.GRP },
      { label: 'CHBO', value: PaymentOptionValues.CHBO },
      { label: 'RECOR', value: PaymentOptionValues.RECOR },
    ],
  },
};
