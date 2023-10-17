import {
  FileType,
  FileStatus,
  monthList,
  FIRS_START_YEAR,
  INTERNAL_FILES,
  BANK_FILES,
  MONTH_OFFSET,
  RECENT_MONTH_OFFSET,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import {
  ApiResponsetype,
  CheckFirsDataReturnType,
  DropdownListType,
  FirsDataType,
  FirsFileType,
  GetCategorizedFirsFilesType,
  GetCurrentDateInfoType,
  GetFirsDetailsReturnType,
  MonthlyDataType,
} from 'merchant/views/AccountAndSettings/InternationalSettings/typings';
import { ExperimentType } from 'common/splitz/types';

export const getDataFromApi = (response: ApiResponsetype): any => {
  const isSuccess = response.success;
  if (isSuccess) {
    const data = response.data;
    return data;
  } else throw new Error('FIRC API call failed');
};

/**
 * @returns {object} - date, year, month
 */
export const getCurrentDateInfo = (): GetCurrentDateInfoType => {
  const currentDate = new Date();
  const currentYear = currentDate.getFullYear();
  const currentMonth = currentDate.getMonth();
  return {
    currentDate,
    currentYear,
    currentMonth,
  };
};

/**
 * This function gets firs data from the cached data object
 * based on month and year
 * @param cachedData - cached data object
 * @param year
 * @param month
 * @returns {}
 */
export const checkFirsData = (
  cachedData: FirsDataType,
  year: number,
  month?: string,
): CheckFirsDataReturnType => {
  if (!cachedData?.[year]) {
    return null;
  }
  if (year && !month) {
    return cachedData[year];
  }
  if (year && month) {
    return cachedData[year]?.[month] ?? [];
  }
  return null;
};

/**
 * This function returns array of years from provided startYear
 * to current Year
 * @param startYear - year to start from
 * @returns {Array} - Array of years
 */
export const getListOfYears = (startYear: number): DropdownListType => {
  if (typeof startYear !== 'number' || startYear < 0) {
    startYear = FIRS_START_YEAR;
  }

  // eslint-disable-next-line
  let { currentMonth, currentYear } = getCurrentDateInfo();
  const years: DropdownListType = [];

  if (currentMonth === 0) {
    currentYear -= 1;
  }

  for (let year = currentYear; year >= startYear; year--) {
    years.push({ title: year.toString(), value: year.toString() });
  }

  return years;
};

/**
 * This function checks if month passed is close to the current month or not
 * based on the offset
 * @param month - firs month
 * @param year - firs year
 * @returns {boolean}
 */
export const isRecentMonth = (month: string, year: number): boolean => {
  const { currentMonth, currentYear } = getCurrentDateInfo();
  const monthIndex = monthList.indexOf(month);
  return year === currentYear && monthIndex >= currentMonth - RECENT_MONTH_OFFSET;
};

export const isMonthValid = (month: string, year: number): boolean => {
  if (!month) return false;
  const { currentDate, currentYear } = getCurrentDateInfo();
  const monthIndex = monthList.indexOf(month);

  //if we are at Jan then move year to prev because there won't be any data for current year
  if (year > currentYear) {
    return false;
  }

  // Calculate the next month and year
  const nextMonth = monthIndex === 11 ? 0 : monthIndex + 1; // Wrap around to January if it's December
  const nextYear = monthIndex === 11 ? year + 1 : year;

  // Check if the current date is on or after the 6th day of the next month
  const nextMonth6th = new Date(nextYear, nextMonth, MONTH_OFFSET);
  return currentDate >= nextMonth6th;
};

/**
 * @param months - response object
 * @param year - selected year
 * @returns - formatted data
 */
export const formatFirsData = (months: MonthlyDataType, year: number): MonthlyDataType => {
  const formattedResponse: MonthlyDataType = {};
  monthList.forEach((month) => {
    if (isMonthValid(month, year)) {
      formattedResponse[month] = months?.[month] ?? [];
    }
  });
  return formattedResponse;
};

export const getFirsDetails = (files: Array<FirsFileType>): GetFirsDetailsReturnType => {
  // Initialize default values
  let fileCount = files.length;
  let hasTransactions = true;
  let isFirsRequested = false;

  // Find the internal FIRS file
  const internalFirsFile = files.find((file) => file.document_type === FileType.FIRS_INTERNAL_FILE);

  // Check if the internal FIRS file is not processed, reduce file count
  if (internalFirsFile && internalFirsFile?.file_status !== FileStatus.PROCESSED) {
    fileCount -= 1;
  }

  // Check if there are no transactions (internal FIRS file status is NO_DATA and file count is 0)
  if (internalFirsFile?.file_status === FileStatus.NO_DATA && fileCount === 0) {
    hasTransactions = false;
  }

  // Check if internal firs is in requested state
  if (internalFirsFile?.file_status === FileStatus.PROCESSING) {
    isFirsRequested = true;
  }

  // Return the result as an object
  return {
    fileCount,
    hasTransactions,
    isFirsRequested,
  };
};

/**
 * @param month - month to update data for
 * @param year - year to update data for
 * @param firsData - data from context
 * @param data - api response
 * @returns - updated firsData object
 */
export const updateFirsDataObject = (
  month: string,
  year: number,
  firsData: FirsDataType,
  data: FirsFileType,
): FirsDataType => {
  if (typeof firsData !== 'object') return firsData;
  const files = [...(firsData?.[year]?.[month] ?? [])];
  const filteredData = files.filter((file) => file.document_type !== FileType.FIRS_INTERNAL_FILE);
  return {
    ...firsData,
    [year]: {
      ...(firsData?.[year] ?? {}),
      [month]: [...filteredData, data],
    },
  };
};

/**
 * Categorizes data based on document_type
 * @param files
 * @returns
 */
export const getCategorizedFirsFiles = (
  files: Array<FirsFileType>,
): GetCategorizedFirsFilesType => {
  const internalFirs: Array<FirsFileType> = [];
  const bankFirs: Array<FirsFileType> = [];

  for (const file of files) {
    if (
      INTERNAL_FILES.includes(file.document_type) &&
      [FileStatus.PROCESSED, FileStatus.PROCESSING].includes(file.file_status)
    ) {
      internalFirs.push(file);
    }
    if (BANK_FILES.includes(file.document_type)) {
      bankFirs.push(file);
    }
  }

  // Sorting the array to push internal firs to last
  internalFirs.sort((file) => {
    if (file.document_type === FileType.FIRS_INTERNAL_FILE) return 1;
    return -1;
  });

  // Find the internal FIRS file
  const internalFirsFile = files.find((file) => file.document_type === FileType.FIRS_INTERNAL_FILE);

  // Check conditions
  const isFirsRequestFailed = internalFirsFile?.file_status === FileStatus.FAILED;
  const shouldShowRequestButton = !internalFirsFile || isFirsRequestFailed;

  return {
    internalFirs,
    bankFirs,
    shouldShowRequestButton,
    isFirsRequestFailed,
  };
};

/**
 * Experiment for requestFIRS
 * @param experiment
 * @returns {boolean}
 */
export const isRequestFirsEnabled = (experiment: ExperimentType): boolean =>
  experiment?.variables?.result === 'on';
