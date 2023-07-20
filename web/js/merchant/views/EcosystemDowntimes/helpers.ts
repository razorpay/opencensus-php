import moment from 'moment';
import {
  INSTRUMENT_CODES_MAP,
  INSTRUMENT_TYPE_NAMES_MAP,
  METHOD_NAMES_MAP,
  STATUS,
  accessInstrumentList,
} from 'merchant/views/EcosystemDowntimes/constants';
import type {
  DowntimeDictionaryType,
  DowntimeMetaDataType,
  InstrumentGroupTypes,
  MethodInstrumentDataListType,
  MethodsInstrumentListType,
  PreviousDowntimeDictionaryType,
  StaticInstrumentMappingType,
  SuccessRateResponseType,
} from './types';
import { toTitleCase } from 'common/utils';

export const extractInstrumentType = (keys: string[]): string | null => {
  const ignoreFields = ['method', 'srKey'];
  const type = keys.find((key) => !ignoreFields.includes(key));
  return type && INSTRUMENT_TYPE_NAMES_MAP?.[type] ? type : null;
};

export const _prepareDowntimeObj = ({
  currentDowntimeObj,
  method,
  instrumentType,
  instrumentValue,
  data,
}) => {
  if (!method || !instrumentType || !instrumentValue) return null;
  //cloning and appending to the current downtime obj
  let activeDowntimesObj = currentDowntimeObj;
  //null checks on properties
  const isMethodAvailable = Boolean(activeDowntimesObj?.[method]);
  const isMethodInstrumentTypeAvailable = Boolean(activeDowntimesObj?.[method]?.[instrumentType]);

  if (!isMethodAvailable) {
    activeDowntimesObj = {
      ...activeDowntimesObj,
      [method]: null,
    };
  }

  if (!isMethodInstrumentTypeAvailable) {
    activeDowntimesObj[method] = {
      ...activeDowntimesObj[method],
      [instrumentType]: null,
    };
  }

  activeDowntimesObj[method][instrumentType] = {
    ...activeDowntimesObj[method][instrumentType],
    [instrumentValue]: data,
  };

  return activeDowntimesObj;
};

export const getSupportedMethodInstrumentDictionary = (): StaticInstrumentMappingType => {
  const methodInstrumentList = accessInstrumentList();
  return methodInstrumentList.reduce((acc, instrument) => {
    const method = instrument.method;
    const instrumentType = extractInstrumentType(Object.keys(instrument));

    if (method && instrumentType) {
      const instrumentValue = instrument?.[instrumentType];
      return _prepareDowntimeObj({
        currentDowntimeObj: acc,
        method,
        instrumentType,
        instrumentValue,
        data: true,
      });
    }
    return acc;
  }, {});
};

export const processOnGoingDowntimes = (
  onGoingDowntimeData: DowntimeMetaDataType[] = [],
): DowntimeDictionaryType => {
  //filtering out the methods which are in the static list.
  const supportEntities = getSupportedMethodInstrumentDictionary();
  const filteredDowntimeData = onGoingDowntimeData.filter((downtime) => {
    const { method, instrument } = downtime;
    const instrumentType = Object.keys(instrument)?.[0];
    const instrumentValue = instrument[instrumentType];
    return !!supportEntities?.[method]?.[instrumentType]?.[instrumentValue];
  });

  return filteredDowntimeData.reduce((acc, currentActiveDowntime) => {
    const { method, instrument } = currentActiveDowntime;
    const instrumentType = Object.keys(instrument)?.[0];
    const instrumentValue = instrument[instrumentType];
    const intermediateDowntimeObj = _prepareDowntimeObj({
      currentDowntimeObj: acc,
      method,
      instrumentType,
      instrumentValue,
      data: currentActiveDowntime,
    });

    return intermediateDowntimeObj;
  }, {});
};

export const getElapsedTime = (timestamp: number, ignoreFields: string[] = []): string => {
  const hours = {
    value: Math.floor(timestamp / 60 / 60),
    unit: 'hrs',
  };
  const minutes = {
    value: Math.floor(timestamp / 60) - hours.value * 60,
    unit: 'mins',
  };
  const seconds = {
    value: timestamp % 60,
    unit: 'secs',
  };

  if (seconds.value && (hours.value || minutes.value)) {
    minutes.value += seconds.value > 30 ? 1 : 0;
    seconds.value = 0;
  }

  const finalValues = [hours, minutes, seconds]
    .filter(({ value, unit }) => !!value && !ignoreFields.includes(unit))
    .map(({ value, unit }) => `${value}${value < 2 ? unit.slice(0, -1) : unit}`);

  return finalValues.join(' ');
};

export const processPreviousDowntimes = (
  resolvedDowntimeData: DowntimeMetaDataType[] = [],
): PreviousDowntimeDictionaryType => {
  return resolvedDowntimeData.reduce((acc, previousDowntime) => {
    const { method, instrument, begin, end } = previousDowntime;
    const instrumentType = Object.keys(instrument)?.[0];
    const instrumentValue = instrument[instrumentType];

    const prevDowntimesForInstrument =
      acc?.[method]?.[instrumentType]?.[instrumentValue]?.previousDowntimes;

    const now = moment(new Date()).unix();
    const from = moment(begin * 1000).format('DD MMM HH:mm');
    const to = end ? moment(end * 1000).format('DD MMM HH:mm') : null;

    const finalDowntimeObj = {
      ...previousDowntime,
      fromToString: [from, to].filter(Boolean).join(' to '),
      duration: getElapsedTime((end || now) - begin),
    };

    const intermediateDowntimeObj = _prepareDowntimeObj({
      currentDowntimeObj: acc,
      method,
      instrumentType,
      instrumentValue,
      data: {
        previousDowntimes: !prevDowntimesForInstrument
          ? [finalDowntimeObj]
          : [...prevDowntimesForInstrument, finalDowntimeObj],
      },
    });

    return intermediateDowntimeObj;
  }, {});
};

export const getInstrumentList = ({
  activeDowntimes,
}: {
  activeDowntimes?: DowntimeDictionaryType | null;
}): MethodInstrumentDataListType => {
  const data: MethodsInstrumentListType[] = accessInstrumentList();
  return data.reduce((acc, item) => {
    const intermediateAcc = acc;
    const { method, srKey } = item;
    const subGroup = extractInstrumentType(Object.keys(item));
    if (method && subGroup) {
      const instrument = item[subGroup];

      if (!intermediateAcc?.[method]) intermediateAcc[method] = {};
      if (!intermediateAcc?.[method]?.[subGroup]) {
        intermediateAcc[method][subGroup] = [];
      }

      //sorting based on downtimes(if any)
      const downtime = activeDowntimes?.[method]?.[subGroup]?.[instrument];
      const severity = downtime?.severity || STATUS.operational.slug;
      let instruments = [...intermediateAcc[method][subGroup]];
      const newInstrumentObject = {
        key: instrument,
        name: INSTRUMENT_CODES_MAP?.[instrument].name || instrument,
        logo: INSTRUMENT_CODES_MAP?.[instrument].logo,
        weight: STATUS[severity].weight,
        srKey,
      };
      instruments = [...instruments, newInstrumentObject];
      instruments = instruments.sort((firstIns, secondIns) => secondIns.weight - firstIns.weight);

      intermediateAcc[method][subGroup] = instruments;
    }

    return intermediateAcc;
  }, {});
};

export const getPayloadForResolvedDowntimes = (): Record<string, string | number> => {
  //NOTE :: for intial mvp we query for 30 days data.
  const today = moment(new Date()).format('YYYY/MM/DD');
  const end = moment(new Date()).subtract(30, 'days').format('YYYY/MM/DD');
  return {
    skip: 0,
    startDate: end,
    endDate: today,
  };
};

export const groupAllDowntimesByMethod = (
  activeDowntimes: DowntimeDictionaryType,
  method: string,
): DowntimeMetaDataType[] => {
  const downtimes = activeDowntimes?.[method];
  if (!downtimes) return [];

  const subGroups = Object.values(downtimes) as Record<
    InstrumentGroupTypes,
    DowntimeMetaDataType
  >[];

  return subGroups
    .reduce(
      (acc: DowntimeMetaDataType[], item: Record<InstrumentGroupTypes, DowntimeMetaDataType>) => {
        return [...acc, ...Object.values(item)];
      },
      [],
    )
    .sort(
      ({ severity: firstSeverity }, { severity: secondSeverity }) =>
        STATUS[secondSeverity].weight - STATUS[firstSeverity].weight,
    );
};

export const getDowntimeHeaderAndDescription = (
  downtime: DowntimeMetaDataType,
): Record<string, string | JSX.Element> => {
  const { severity, instrument, begin } = downtime;

  const heading = `${STATUS?.[severity]?.text} Downtime`;
  const icon = STATUS[severity]?.icon;

  const time = moment(begin * 1000).format('DD/MM, HH:mm');
  const instrumentName = INSTRUMENT_CODES_MAP[Object.values(instrument)?.[0] as string]?.name;
  const description = `${instrumentName} (started at ${time})`;

  return {
    heading,
    icon,
    description,
  };
};

export const getRemainingTime = (seconds: number): string => {
  const parsedSeconds = moment.duration(seconds, 'seconds');
  if (seconds < 60) {
    return `${parsedSeconds.seconds()}s`;
  }
  return `${parsedSeconds.minutes()}m ${parsedSeconds.seconds()}s`;
};

type getDowntimesAfterTimestampParamTypes = {
  activeDowntimeForInstrument?: DowntimeMetaDataType;
  pastDowntimesForInstrument?: DowntimeMetaDataType[];
  timestamp: number;
};
export const getDowntimesAfterTimestamp = ({
  activeDowntimeForInstrument,
  pastDowntimesForInstrument,
  timestamp,
}: getDowntimesAfterTimestampParamTypes): {
  totalDuration: number | string;
  totalDowntimes: number;
} => {
  let collectedDowntimes: number[] = [];
  const now = moment(new Date()).unix();

  // collecting previous downtimes
  if (pastDowntimesForInstrument) {
    collectedDowntimes = pastDowntimesForInstrument
      .filter(({ end }) => end && end > timestamp)
      .map(({ begin, end }) => {
        const isBeginBeyondRequiredFrame = begin < timestamp;
        const endTime = end || now;
        return isBeginBeyondRequiredFrame ? endTime - timestamp : endTime - begin;
      });
  }
  // appending current downtime if any
  if (activeDowntimeForInstrument) {
    const { begin } = activeDowntimeForInstrument;
    collectedDowntimes.push(now - begin);
  }

  const totalDuration = collectedDowntimes.reduce((acc, item) => acc + item, 0);

  return {
    totalDuration: getElapsedTime(totalDuration) || 0,
    totalDowntimes: collectedDowntimes.length || 0,
  };
};

export const getPayloadForSR = ({ srKey }: { srKey: string }) => {
  //NOTE :: for intial mvp we query for 7 days data.
  const to = moment(new Date()).unix();
  const from = moment(new Date()).subtract(7, 'days').unix();

  const [method, group, instrument] = srKey.split('.');

  return {
    entity: 'payments',
    from,
    to,
    interval: 60,
    mode: 'razorpay',
    filters: {
      method: [method],
      [group]: [instrument],
    },
  };
};

type SrDataPointsType = {
  total: number;
  successful: number;
  unsuccessful: number;
  isError: boolean;
  methodName: string;
  instrumentName: string;
};

type getSrDatPointsFromResponseTypes = {
  srResponse?: SuccessRateResponseType;
  method: string;
  instrument: string;
};

export const getSrDatPointsFromResponse = ({
  srResponse,
  method,
  instrument,
}: getSrDatPointsFromResponseTypes): SrDataPointsType => {
  let responseStruct = {
    total: 0,
    successful: 0,
    unsuccessful: 0,
    isError: false,
    methodName: METHOD_NAMES_MAP?.[method] || toTitleCase(method),
    instrumentName: INSTRUMENT_CODES_MAP?.[instrument]?.name || toTitleCase(instrument),
  };
  if (srResponse?.data) {
    const { data } = srResponse;

    if (data?.Code) {
      responseStruct = {
        ...responseStruct,
        isError: true,
      };
    } else {
      const { successful = 0, total = 0 } = data;
      responseStruct = {
        ...responseStruct,
        successful,
        total,
        unsuccessful: total - successful,
      };
    }
  }
  return responseStruct;
};
