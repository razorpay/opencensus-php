import moment from 'moment';
import {
  INSTRUMENT_CODES_MAP,
  STATUS,
  accessInstrumentList,
} from 'merchant/views/EcosystemDowntimes/constants';
import type {
  DowntimeDictionaryType,
  DowntimeMetaDataType,
  MethodInstrumentDataListType,
  MethodsInstrumentListType,
  PreviousDowntimeDictionaryType,
  StaticInstrumentMappingType,
} from './types';

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
  const isMethodAvailable = activeDowntimesObj?.[method];
  const isMethodInstrumentTypeAvailable = activeDowntimesObj?.[method]?.[instrumentType];

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
    const instrumentType = Object.keys(instrument).filter((item) => item !== 'method')?.[0];
    const instrumentValue = instrument?.[instrumentType];
    return _prepareDowntimeObj({
      currentDowntimeObj: acc,
      method,
      instrumentType,
      instrumentValue,
      data: true,
    });
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

export const getHoursMinutesFromTimestamp = (timestamp: number, ignoreFields: string[]): string => {
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

  const finalValues = [hours, minutes, seconds]
    .filter(({ value, unit }) => !!value && !ignoreFields.includes(unit))
    .map(({ value, unit }) => `${value}${unit}`);

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
      duration: getHoursMinutesFromTimestamp((end || now) - begin, []),
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

export const getInstrumentList = (): MethodInstrumentDataListType => {
  const data: MethodsInstrumentListType[] = accessInstrumentList();
  return data.reduce((acc, item) => {
    const intermediateAcc = acc;
    const method = item.method;
    const subGroup = Object.keys(item)
      .filter((item) => item !== 'method')
      .pop();
    if (method && subGroup) {
      const instrument = item[subGroup];

      if (!intermediateAcc?.[method]) intermediateAcc[method] = {};
      if (!intermediateAcc?.[method]?.[subGroup]) {
        intermediateAcc[method][subGroup] = [];
      }
      intermediateAcc[method][subGroup].push({
        key: instrument,
        name: INSTRUMENT_CODES_MAP?.[instrument].name || instrument,
        logo: INSTRUMENT_CODES_MAP?.[instrument].logo,
      });
    }
    return intermediateAcc;
  }, {});
};

export const getPayloadForResolvedDowntimes = (): Record<string, string | number> => {
  //NOTE :: for intial mvp we query for 1 week data.
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

  const subGroups = Object.values(downtimes);
  return subGroups
    .reduce((acc: DowntimeMetaDataType[], item: DowntimeMetaDataType) => {
      return [...acc, ...Object.values(item)];
    }, [])
    .sort(
      ({ severity: firstSeverity }, { severity: secondSeverity }) =>
        STATUS[secondSeverity].weight - STATUS[firstSeverity].weight,
    );
};

export const getDowntimeHeaderAndDescription = (
  downtime: DowntimeMetaDataType,
): Record<string, string | JSX.Element> => {
  const { severity, instrument, created_at } = downtime;

  const heading = `${STATUS?.[severity]?.text} Downtime`;
  const icon = STATUS[severity]?.icon;

  const time = moment(created_at * 1000).format('DD/MM, HH:mm');
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
  pastDowntimesForInstrument: DowntimeMetaDataType[];
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
    totalDuration: getHoursMinutesFromTimestamp(totalDuration, []) || 0,
    totalDowntimes: collectedDowntimes.length || 0,
  };
};
