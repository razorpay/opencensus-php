import React from 'react';
import moment from 'moment';

import StatusIcon from 'merchant/views/EcosystemDowntimes/components/StatusIcon';
import instrumentList from 'merchant/views/EcosystemDowntimes/instruments.json';

import { getDowntimesAfterTimestamp } from './helpers';

import type {
  StatusTypes,
  DowntimeMetaDataType,
  EcosystemDowntimesStatusType,
  EcosystemDowntimesActions,
  MethodsInstrumentListType,
} from './types';

//single entry point to access static instrument list
export const accessInstrumentList = (): MethodsInstrumentListType[] => [...instrumentList];

export const PAGE_TITLE = 'Ecosystem Health';

export const WAIT_TIME_FOR_NEXT_REFRESH = 60 * 5;

export const ACTIONS: Record<string, EcosystemDowntimesActions> = {
  SET_ACTIVE_DOWNTIMES: 'SET_ACTIVE_DOWNTIMES',
  SET_FOCUSED_INSTRUMENT: 'SET_FOCUSED_INSTRUMENT',
  SET_PREVIOUS_DOWNTIMES: 'SET_PREVIOUS_DOWNTIMES',
};

export const STATUS: Record<StatusTypes, EcosystemDowntimesStatusType> = {
  operational: {
    slug: 'operational',
    text: 'Operational',
    weight: 0,
    colorKey: 'information',
    icon: <StatusIcon type="operational" />,
  },
  low: {
    slug: 'low',
    text: 'Low Severity',
    weight: 1,
    colorKey: 'notice',
    icon: <StatusIcon type="low" />,
  },
  medium: {
    slug: 'medium',
    text: 'Medium Severity',
    weight: 2,
    colorKey: 'notice',
    icon: <StatusIcon type="medium" />,
  },
  high: {
    slug: 'high',
    text: 'High Severity',
    weight: 3,
    colorKey: 'negative',
    icon: <StatusIcon type="high" />,
  },
};

export const INSTRUMENT_CODES_MAP = {
  upi: {
    name: 'BHIM',
    logo: 'bhim.png',
  },
  ICIC: {
    name: 'ICICI Bank',
    logo: 'icici.png',
  },
  HDFC: {
    name: 'HDFC Bank',
    logo: 'hdfc.png',
  },
  KKBK: {
    name: 'Kotak Mahindra Bank',
    logo: 'kotak.png',
  },
  UTIB: {
    name: 'Axis Bank',
    logo: 'axis.png',
  },
  SBIN: {
    name: 'State Bank Of India',
    logo: 'sbi.png',
  },
  CITI: {
    name: 'CITI Bank',
    logo: 'citi.png',
  },
  PUNB: {
    name: 'Punjab National Bank',
    logo: 'pnb.png',
  },
  BARB: {
    name: 'Bank Of Baroda',
    logo: 'bob.png',
  },
  BKID: {
    name: 'Bank Of India',
    logo: 'boi.png',
  },
  CNRB: {
    name: 'Canara Bank',
    logo: 'cnb.png',
  },
  DICL: {
    name: 'Diners Club',
    logo: 'diners.png',
  },
  AMEX: {
    name: 'American Express',
    logo: 'amex.png',
  },
  MC: {
    name: 'Mastercard',
    logo: 'mc.png',
  },
  RUPAY: {
    name: 'RUPAY',
    logo: 'rupay.png',
  },
  VISA: {
    name: 'VISA',
    logo: 'visa.png',
  },
  google_pay: {
    name: 'Google Pay',
    logo: 'gpay.png',
  },
  phonepe: {
    name: 'PhonePe',
    logo: 'phonepe.png',
  },
  paytm: {
    name: 'Paytm',
    logo: 'paytm.png',
  },
  bhim: {
    name: 'BHIM',
    logo: 'bhim.png',
  },
  ybl: {
    name: 'YBL',
    logo: 'yesbank.png',
  },
  apl: {
    name: 'APL',
    logo: 'amazonpay.png',
  },
  oksbi: {
    name: 'oksbi',
    logo: 'sbi.png',
  },
  okaxis: {
    name: 'okaxis',
    logo: 'axis.png',
  },
  okhdfcbank: {
    name: 'okhdfcbank',
    logo: 'hdfc.png',
  },
  okicici: {
    name: 'okicici',
    logo: 'icici.png',
  },
  RATN: {
    name: 'RBL Bank',
    logo: 'ratn.png',
  },
  HSBC: {
    name: 'HSBC Corporation',
    logo: 'hsbc.png',
  },
  UBIN: {
    name: 'Union Bank of India',
    logo: 'ubin.png',
  },
  IDIB: {
    name: 'Indian Bank',
    logo: 'idib.png',
  },
  JAKA: {
    name: 'Jammu and Kashmir Bank',
    logo: 'jaka.png',
  },
  PYTM: {
    name: 'Paytm Payments Bank',
    logo: 'pytm.png',
  },
  amazon_pay: {
    name: 'Amazon pay',
    logo: 'amazonpay.png',
  },
};

export const METHOD_NAMES_MAP = {
  upi: 'UPI',
  card: 'Cards',
  netbanking: 'Net Banking',
  emandate: 'E-mandate',
};

export const DOWNTIME_SUMMARY_FIELDS = [
  {
    name: 'downtimesToday',
    description: 'Downtimes Today',
    information: `(from ${moment(new Date()).format('DD MMM')}, 00:00)`,
    value: (params: {
      activeDowntimeForInstrument: DowntimeMetaDataType;
      pastDowntimesForInstrument: DowntimeMetaDataType[];
    }): string | number =>
      getDowntimesAfterTimestamp({ ...params, timestamp: moment().startOf('day').unix() })
        ?.totalDowntimes,
  },
  {
    name: 'downtimesInLast24hrs',
    description: 'Downtime Duration',
    information: `(from ${moment(new Date()).format('DD MMM')}, 00:00)`,
    value: (params: {
      activeDowntimeForInstrument: DowntimeMetaDataType;
      pastDowntimesForInstrument: DowntimeMetaDataType[];
    }): string | number =>
      getDowntimesAfterTimestamp({
        ...params,
        timestamp: moment().startOf('day').unix(),
      })?.totalDuration,
  },
];

export const SR_QUERY_CACHE_KEY = 'successRate';

export const INSTRUMENT_TYPE_NAMES_MAP = {
  issuer: 'Issuer',
  bank: 'Bank',
  network: 'Network',
  vpa_handle: 'VPA handle',
  psp: 'PSP apps',
};
