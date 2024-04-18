import BankPlaceholder from 'assets/assisted_financing/bank_placeholder.png';
import Hdfc from 'assets/assisted_financing/cardEmi/HDFC.gif';
import Icici from 'assets/assisted_financing/cardEmi/ICIC.gif';
import Indb from 'assets/assisted_financing/cardEmi/INDB.gif';
import Kotak from 'assets/assisted_financing/cardEmi/KKBK.gif';
import YesBank from 'assets/assisted_financing/cardEmi/YESB.gif';
import Amex from 'assets/assisted_financing/cardEmi/amex.svg';
import Axis from 'assets/assisted_financing/cardEmi/axis.png';
import Scbl from 'assets/assisted_financing/cardEmi/scbl.gif';
import OneCard from 'assets/assisted_financing/cardlessEmi/ONE_CARD.gif';
import Bajaj from 'assets/assisted_financing/cardlessEmi/bajaj.svg';
import Barb from 'assets/assisted_financing/cardlessEmi/barb.gif';
import Cashe from 'assets/assisted_financing/cardlessEmi/cshe.png';
import EarlySalary from 'assets/assisted_financing/cardlessEmi/earlysalary.svg';
import Hcin from 'assets/assisted_financing/cardlessEmi/hcin.svg';
import Idfc from 'assets/assisted_financing/cardlessEmi/idfc.gif';
import Krbe from 'assets/assisted_financing/cardlessEmi/krbe.png';
import Liquiloans from 'assets/assisted_financing/cardlessEmi/liquiloans.svg';
import Tvsc from 'assets/assisted_financing/cardlessEmi/tvsc.svg';
import Axio from 'assets/assisted_financing/cardlessEmi/walnut369.svg';
import Citi from 'assets/ecosystem_health/citi.png';
import Hsbc from 'assets/ecosystem_health/hsbc.png';
import RBL from 'assets/ecosystem_health/ratn.png';
import Sbi from 'assets/ecosystem_health/sbi.png';
import { getDialCodeByCountryCode } from '@razorpay/i18nify-js/phoneNumber';
import { getUser } from 'merchant/store';

const user = getUser();

export const eligibilityPhoneNumberValidityRegex = new RegExp(
  `^(\\${getDialCodeByCountryCode(user.merchant.country_code)})?[6-9]\\d{9}$`,
);

export const paymentLinkBannerTitles = [
  'Available EMI tenures',
  'Interest rates',
  'Complete the payment',
];

export const cardlessEmiList = {
  walnut369: {
    name: 'axio Cardless EMI',
    icon: Axio,
  },
  bajaj: {
    name: 'Bajaj Finserv Cardless EMI',
    icon: Bajaj,
  },
  earlysalary: {
    name: 'fibe Cardless EMI',
    icon: EarlySalary,
  },
  zestmoney: {
    name: 'ZestMoney Cardless EMI',
    icon: BankPlaceholder,
  },
  flexmoney: {
    name: 'Cardless EMI by InstaCred',
    icon: BankPlaceholder,
  },
  barb: {
    name: 'Bank of Baroda Cardless EMI',
    icon: Barb,
  },
  hdfc: {
    name: 'HDFC Bank Cardless EMI',
    icon: Hdfc,
  },
  idfb: {
    name: 'IDFC First Bank Cardless EMI',
    icon: Idfc,
  },
  kkbk: {
    name: 'Kotak Mahindra Bank Cardless EMI',
    icon: Kotak,
  },
  icic: {
    name: 'ICICI Bank Cardless EMI',
    icon: Icici,
  },
  hcin: {
    name: 'Home Credit Ujjwal Card',
    icon: Hcin,
  },
  onecard: {
    name: 'OneCard',
    icon: OneCard,
  },
  cshe: {
    name: 'CASHe Cardless EMI',
    icon: Cashe,
  },
  krbe: {
    name: 'KreditBee Cardless EMI',
    icon: Krbe,
  },
  tvsc: {
    name: 'TVS Credit Cardless EMI',
    icon: Tvsc,
  },
  liquiloans: {
    name: 'Liquiloans Cardless EMI',
    icon: Liquiloans,
  },
};

export const emiBanksList = {
  KKBK: {
    name: 'Kotak Mahindra Bank Credit Cards',
    icon: Kotak,
  },
  KKBK_DC: {
    name: 'Kotak Debit Cards',
    icon: Kotak,
  },
  HDFC_DC: {
    name: 'HDFC Debit Cards',
    icon: Hdfc,
  },
  HDFC: {
    name: 'HDFC Credit Cards',
    icon: Hdfc,
  },
  UTIB: {
    name: 'Axis Bank Credit Cards',
    icon: Axis,
  },
  INDB: {
    name: 'Indusind Bank Credit Cards',
    icon: Indb,
  },
  RATN: {
    name: 'RBL Bank Credit Cards',
    icon: RBL,
  },
  ICIC: {
    name: 'ICICI Bank Credit Cards',
    icon: Icici,
  },
  SCBL: {
    name: 'Standard Chartered Bank Credit Cards',
    icon: Scbl,
  },
  YESB: {
    name: 'Yes Bank Credit Cards',
    icon: YesBank,
  },
  AMEX: {
    name: 'American Express Credit Cards',
    icon: Amex,
  },
  SBIN: {
    name: 'State Bank of India Credit Cards',
    icon: Sbi,
  },
  BARB: {
    name: 'Bank of Baroda Credit Cards',
    icon: Barb,
  },
  BAJAJ: {
    name: 'Bajaj Finserv',
    icon: Bajaj,
  },
  CITI: {
    name: 'CITI Bank Credit Cards',
    icon: Citi,
  },
  HSBC: {
    name: 'HSBC Credit Cards',
    icon: Hsbc,
  },
  IDFB: {
    name: 'IDFC First Bank',
    icon: Idfc,
  },
  onecard: {
    name: 'OneCard',
    icon: OneCard,
  },
  INDB_DC: {
    name: 'Indusind Bank Debit Cards',
    icon: Indb,
  },
  ICIC_DC: {
    name: 'ICICI Bank Debit Cards',
    icon: Icici,
  },
};

/**
 * Constants to use all available EMI providers
 */
export const emiBanks = {
  HDFC: 'HDFC',
  ICIC: 'ICIC',
  UTIB: 'UTIB',
  SBIN: 'SBIN',
  AMEX: 'AMEX',
  RATN: 'RATN',
  CITI: 'CITI',
  KKBK: 'KKBK',
  INDB: 'INDB',
  HSBC: 'HSBC',
  BAJAJ: 'BAJAJ',
  SCBL: 'SCBL',
  YESB: 'YESB',
  BARB: 'BARB',
  FDRL: 'FDRL',
  onecard: 'onecard',
  SIBL: 'SIBL',
  STCB: 'STCB',
  IDFB: 'IDFB',
};

export const config = {
  walnut369: {
    name: 'axio',
    fee_bearer_customer: false,
    headless: false,
    pushToFirst: true,
    min_amount: 100,
  },
  bajaj: {
    name: 'Bajaj Finserv',
  },
  earlysalary: {
    name: 'fibe',
    fee_bearer_customer: false,
  },
  zestmoney: {
    name: 'ZestMoney',
    min_amount: 9900,
    fee_bearer_customer: false,
  },
  flexmoney: {
    name: 'Cardless EMI by InstaCred',
    headless: false,
    fee_bearer_customer: false,
  },
  barb: {
    name: 'Bank of Baroda Cardless EMI',
    headless: false,
  },
  fdrl: {
    name: 'Federal Bank Cardless EMI',
    headless: false,
  },
  hdfc: {
    name: 'HDFC Bank Cardless EMI',
    headless: false,
  },
  idfb: {
    name: 'IDFC First Bank Cardless EMI',
    headless: false,
  },
  kkbk: {
    name: 'Kotak Mahindra Bank Cardless EMI',
    headless: false,
  },
  icic: {
    name: 'ICICI Bank Cardless EMI',
    headless: false,
  },
  hcin: {
    name: 'Home Credit Ujjwal Card',
    headless: false,
    min_amount: 50000,
  },
  onecard: {
    name: 'OneCard',
  },
  cshe: {
    name: 'CASHe',
    headless: false,
    startingFrom: 14.5,
  },
  krbe: {
    name: 'KreditBee',
    headless: false,
    startingFrom: 22,
  },
  tvsc: {
    name: 'TVS Credit',
    headless: false,
    startingFrom: 27,
  },
  liquiloans: {
    name: 'Liquiloans',
  },
};
