import React from 'react';
import StatusIcon from 'merchant/views/EcosystemDowntimes/components/StatusIcon';
import type {
  StatusTypes,
  DowntimeMetaDataType,
  EcosystemDowntimesStatusType,
  EcosystemDowntimesActions,
  MethodsInstrumentListType,
} from './types';
import moment from 'moment';
import { getDowntimesAfterTimestamp } from './helpers';
import instrumentList from 'merchant/views/EcosystemDowntimes/instruments.json';

//single entry point to access static instrument list
export const accessInstrumentList = (): MethodsInstrumentListType[] => [...instrumentList];

export const PAGE_TITLE = 'Ecosystem Health';
const INSTRUMENT_LOGO_BASE_PATH = '/dist/css/assets/ecosystem_health';

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
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/bhim.png`,
  },
  ICIC: {
    name: 'ICICI Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/icici.png`,
  },
  HDFC: {
    name: 'HDFC Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/hdfc.png`,
  },
  KKBK: {
    name: 'Kotak Mahindra Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/kotak.png`,
  },
  UTIB: {
    name: 'Axis Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/axis.png`,
  },
  SBIN: {
    name: 'State Bank Of India',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/sbi.png`,
  },
  CITI: {
    name: 'CITI Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/citi.png`,
  },
  PUNB: {
    name: 'Punjab National Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/pnb.png`,
  },
  BARB: {
    name: 'Bank Of Baroda',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/bob.png`,
  },
  BKID: {
    name: 'Bank Of India',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/boi.png`,
  },
  CNRB: {
    name: 'Canara Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/cnb.png`,
  },
  DICL: {
    name: 'Diners Club',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/diners.png`,
  },
  AMEX: {
    name: 'American Express',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/amex.png`,
  },
  MC: {
    name: 'Mastercard',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/mc.png`,
  },
  RUPAY: {
    name: 'RUPAY',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/rupay.png`,
  },
  VISA: {
    name: 'VISA',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/visa.png`,
  },
  google_pay: {
    name: 'Google Pay',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/gpay.png`,
  },
  phonepe: {
    name: 'PhonePe',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/phonepe.png`,
  },
  paytm: {
    name: 'Paytm',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/paytm.png`,
  },
  bhim: {
    name: 'BHIM',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/bhim.png`,
  },
  ybl: {
    name: 'YBL',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/yesbank.png`,
  },
  apl: {
    name: 'APL',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/amazonpay.png`,
  },
  oksbi: {
    name: 'oksbi',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/sbi.png`,
  },
  okaxis: {
    name: 'okaxis',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/axis.png`,
  },
  okhdfcbank: {
    name: 'okhdfcbank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/hdfc.png`,
  },
  okicici: {
    name: 'okicici',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/icici.png`,
  },
  RATN: {
    name: 'RBL Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/ratn.png`,
  },
  HSBC: {
    name: 'HSBC Corporation',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/hsbc.png`,
  },
  UBIN: {
    name: 'Union Bank of India',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/ubin.png`,
  },
  IDIB: {
    name: 'Indian Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/idib.png`,
  },
  JAKA: {
    name: 'Jammu and Kashmir Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/jaka.png`,
  },
  PYTM: {
    name: 'Paytm Payments Bank',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/pytm.png`,
  },
  amazon_pay: {
    name: 'Amazon pay',
    logo: `${INSTRUMENT_LOGO_BASE_PATH}/amazonpay.png`,
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
    information: '(last 24 hours)',
    value: (params: {
      activeDowntimeForInstrument: DowntimeMetaDataType;
      pastDowntimesForInstrument: DowntimeMetaDataType[];
    }): string | number =>
      getDowntimesAfterTimestamp({
        ...params,
        timestamp: moment(new Date()).subtract(24, 'hours').unix(),
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
