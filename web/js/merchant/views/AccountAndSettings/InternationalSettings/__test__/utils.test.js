import {
  generateBankFirs,
  generateInternalFirs,
  dateObject,
  fileObject,
} from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures';
import {
  cachedData,
  mockGlobalDate,
} from 'merchant/views/AccountAndSettings/InternationalSettings/__test__/fixtures';
import {
  BANK_FILES,
  INTERNAL_FILES,
  FileType,
  FileStatus,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import {
  getDataFromApi,
  getCurrentDateInfo,
  checkFirsData,
  getListOfYears,
  isRecentMonth,
  isMonthValid,
  getFirsDetails,
  updateFirsDataObject,
  getCategorizedFirsFiles,
  isRequestFirsEnabled,
} from 'merchant/views/AccountAndSettings/InternationalSettings/utils';

jest.mock('merchant/views/AccountAndSettings/InternationalSettings/constants', () => ({
  ...jest.requireActual('merchant/views/AccountAndSettings/InternationalSettings/constants'),
  FIRS_START_YEAR: 2021,
}));

describe('Tests for getDataFromApi', () => {
  test('should return data when response is successful', () => {
    const response = {
      success: true,
      data: { someData: 'example' },
    };
    const result = getDataFromApi(response);
    expect(result).toEqual({ someData: 'example' });
  });

  test('should throw an error when response is not successful', () => {
    const response = {
      success: false,
    };
    try {
      getDataFromApi(response);
    } catch (error) {
      expect(error.message).toBe('FIRC API call failed');
    }
  });
});

describe('Tests for getCurrentDateInfo', () => {
  test('should return current date info', () => {
    // Mock the current date to a specific date for the test
    const { onComplete, mockDate } = mockGlobalDate('2023-09-15T12:00:00Z');
    const result = getCurrentDateInfo();
    onComplete();

    expect(result.currentDate).toEqual(mockDate);
    expect(result.currentYear).toEqual(2023);
    expect(result.currentMonth).toEqual(8); // Month is 0-based (September is 8)
  });
});

describe('Tests for checkFirsData', () => {
  test('should return null if cachedData is empty', () => {
    const result = checkFirsData({}, 2021);
    expect(result).toBeNull();
  });

  test('should return data for the specified year if month is not provided', () => {
    const result = checkFirsData(cachedData, 2021);
    expect(result).toEqual(cachedData[2021]);
  });

  test('should return data for the specified year and month if provided', () => {
    const result = checkFirsData(cachedData, 2021, 'January');
    expect(result).toEqual(cachedData[2021].January);
  });

  test('should return an empty array if the specified month does not exist', () => {
    const result = checkFirsData(cachedData, 2022, 'April');
    expect(result).toEqual([]);
  });

  test('should return null if the specified year does not exist in cachedData', () => {
    const result = checkFirsData(cachedData, 2024);
    expect(result).toBeNull();
  });
});

describe('Tests for getListOfYears', () => {
  let onComplete;

  beforeAll(() => {
    const data = mockGlobalDate('2023-09-15T12:00:00Z');
    onComplete = data.onComplete;
  });

  afterAll(() => {
    onComplete();
  });

  test('should return an array of years from startYear to currentYear', () => {
    const startYear = 2020;
    const result = getListOfYears(startYear);

    // Expected result based on the mocked current year (2023)
    const expectedYears = [
      { title: '2023', value: '2023' },
      { title: '2022', value: '2022' },
      { title: '2021', value: '2021' },
      { title: '2020', value: '2020' },
    ];

    expect(result).toEqual(expectedYears);
  });

  test('should return an array of years starting from FIRS_START_YEAR if startYear is negative', () => {
    const startYear = -1;
    const result = getListOfYears(startYear);

    const expectedYears = [
      { title: '2023', value: '2023' },
      { title: '2022', value: '2022' },
      { title: '2021', value: '2021' },
    ];

    expect(result).toEqual(expectedYears);
  });

  test('should return an empty array if startYear is greater than the current year', () => {
    const startYear = 2024;
    const result = getListOfYears(startYear);

    expect(result).toEqual([]);
  });
});

describe('Tests for getListOfYears for january month', () => {
  test('should return an array of years starting from FIRS_START_YEAR to prev year if it is january', () => {
    const { onComplete } = mockGlobalDate('2023-01-15T12:00:00Z');
    const startYear = -1;
    const result = getListOfYears(startYear);

    const expectedYears = [
      { title: '2022', value: '2022' },
      { title: '2021', value: '2021' },
    ];

    expect(result).toEqual(expectedYears);
    onComplete();
  });
});

describe('isRecentMonth', () => {
  let onComplete;

  beforeAll(() => {
    const data = mockGlobalDate('2023-09-15T12:00:00Z');
    onComplete = data.onComplete;
  });

  afterAll(() => {
    onComplete();
  });

  test('should return true for the current month', () => {
    expect(isRecentMonth('September', 2023)).toBe(true);
  });

  test('should return true for the previous month', () => {
    expect(isRecentMonth('August', 2023)).toBe(true);
  });

  test('should return true for the month before the previous month', () => {
    expect(isRecentMonth('July', 2023)).toBe(true);
  });

  test('should return false for older months', () => {
    expect(isRecentMonth('June', 2023)).toBe(false);
  });

  test('should return false for any month in a different year', () => {
    expect(isRecentMonth('January', 2024)).toBe(false);
  });
});

describe('Tests for isMonthValid', () => {
  let onComplete;

  beforeAll(() => {
    const data = mockGlobalDate('2023-09-15T12:00:00Z');
    onComplete = data.onComplete;
  });

  afterAll(() => {
    onComplete();
  });

  test('should return true for the current month', () => {
    expect(isMonthValid('September', 2023)).toBe(true);
  });

  test('should return true for the next month', () => {
    expect(isMonthValid('October', 2023)).toBe(true);
  });

  test('should return true for the month after the next month', () => {
    expect(isMonthValid('November', 2023)).toBe(true);
  });

  test('should return true for older months', () => {
    expect(isMonthValid('August', 2023)).toBe(true);
  });

  test('should return false for the current month in the next year', () => {
    expect(isMonthValid('September', 2024)).toBe(false);
  });

  test('should return false for an invalid month', () => {
    expect(isMonthValid('', 2023)).toBe(false);
  });

  test('should return true for prev month if current month 6th has reached', () => {
    const { onComplete } = mockGlobalDate('2023-09-07T12:00:00Z');
    expect(isMonthValid('August', 2023)).toBe(true);
    onComplete();
  });
});

describe('Tests for getFirsDetails', () => {
  test('When only bank firs are passed', () => {
    const bankFirs = generateBankFirs(BANK_FILES);
    const response = getFirsDetails(bankFirs);

    expect(response).toStrictEqual(
      expect.objectContaining({
        fileCount: bankFirs.length,
        hasTransactions: true,
        isFirsRequested: false,
      }),
    );
  });

  test('When bank and internal firs are passed', () => {
    const bankFirs = generateBankFirs(BANK_FILES);
    const internalFirs = generateInternalFirs(INTERNAL_FILES);
    const response = getFirsDetails([...bankFirs, ...internalFirs]);

    expect(response).toStrictEqual(
      expect.objectContaining({
        fileCount: bankFirs.length + internalFirs.length,
        hasTransactions: true,
        isFirsRequested: false,
      }),
    );
  });

  test('When bank and internal firs are passed but internal firs has status failed', () => {
    const bankFirs = generateBankFirs(BANK_FILES);
    const internalFirs = generateInternalFirs(INTERNAL_FILES, FileStatus.FAILED);
    const response = getFirsDetails([...bankFirs, ...internalFirs]);

    expect(response).toStrictEqual(
      expect.objectContaining({
        fileCount: bankFirs.length + internalFirs.length - 1,
        hasTransactions: true,
        isFirsRequested: false,
      }),
    );
  });

  test('When bank and internal firs are passed but internal firs has status Processing', () => {
    const bankFirs = generateBankFirs(BANK_FILES);
    const internalFirs = generateInternalFirs(INTERNAL_FILES, FileStatus.PROCESSING);
    const response = getFirsDetails([...bankFirs, ...internalFirs]);

    expect(response).toStrictEqual(
      expect.objectContaining({
        fileCount: bankFirs.length + internalFirs.length - 1,
        hasTransactions: true,
        isFirsRequested: true,
      }),
    );
  });

  test('When only internal firs is passed with status noData', () => {
    const internalFirs = generateInternalFirs([FileType.FIRS_INTERNAL_FILE], FileStatus.NO_DATA);
    const response = getFirsDetails(internalFirs);

    expect(response).toStrictEqual(
      expect.objectContaining({
        fileCount: 0,
        hasTransactions: false,
        isFirsRequested: false,
      }),
    );
  });

  test('When internal and amex firs is passed with status noData', () => {
    const internalFirs = generateInternalFirs(INTERNAL_FILES, FileStatus.NO_DATA);
    const response = getFirsDetails(internalFirs);

    expect(response).toStrictEqual(
      expect.objectContaining({
        fileCount: internalFirs.length - 1,
        hasTransactions: true,
        isFirsRequested: false,
      }),
    );
  });
});

describe('Tests for updateFirsDataObject', () => {
  const { month, year } = dateObject;

  test('When firsData is not a valid object', () => {
    const response = updateFirsDataObject(month, year, 'firsData', fileObject);
    expect(response).toBe('firsData');
  });

  test('firsData has not internal file', () => {
    const bankFiles = generateBankFirs(BANK_FILES);
    const internalFiles = generateInternalFirs([FileType.FIRS_INTERNAL_AMEX_FILE]);
    const firsData = {
      [year]: {
        [month]: [...bankFiles, ...internalFiles],
      },
    };
    const response = updateFirsDataObject(month, year, firsData, fileObject);

    //creating the updated object
    const updatedFirsData = { [year]: { [month]: [...bankFiles, ...internalFiles, fileObject] } };

    expect(response).toStrictEqual(updatedFirsData);
  });

  test('firsData has internal file', () => {
    const bankFiles = generateBankFirs(BANK_FILES);
    const internalFiles = generateInternalFirs(INTERNAL_FILES, FileStatus.FAILED);
    const firsData = {
      [year]: {
        [month]: [...bankFiles, ...internalFiles],
      },
    };
    const response = updateFirsDataObject(month, year, firsData, fileObject);

    //creating the updated object
    const updatedFirsData = { [year]: { [month]: [...bankFiles, internalFiles[0], fileObject] } };

    expect(response).toStrictEqual(updatedFirsData);
  });
});

describe('Tests for getCategorizedFirsFiles', () => {
  test('When only bank firs are passed', () => {
    const bankFirs = generateBankFirs(BANK_FILES);
    const response = getCategorizedFirsFiles(bankFirs);

    expect(response).toStrictEqual({
      internalFirs: [],
      bankFirs,
      shouldShowRequestButton: true,
      isFirsRequestFailed: false,
    });
  });

  test('When only internal firs are passed', () => {
    //generateInternalFirs by default tests internal file status to processed
    const internalFirs = generateInternalFirs(INTERNAL_FILES);
    const response = getCategorizedFirsFiles(internalFirs);

    expect(response).toStrictEqual({
      internalFirs,
      bankFirs: [],
      shouldShowRequestButton: false,
      isFirsRequestFailed: false,
    });
  });

  test('When only internal firs are passed with status failed', () => {
    const internalFirs = generateInternalFirs(INTERNAL_FILES, FileStatus.FAILED);
    const response = getCategorizedFirsFiles(internalFirs);

    expect(response).toStrictEqual({
      internalFirs: [internalFirs[0]],
      bankFirs: [],
      shouldShowRequestButton: true,
      isFirsRequestFailed: true,
    });
  });

  test('When only internal firs are passed with status noData', () => {
    const internalFirs = generateInternalFirs(INTERNAL_FILES, FileStatus.NO_DATA);
    const response = getCategorizedFirsFiles(internalFirs);

    expect(response).toStrictEqual({
      internalFirs: [internalFirs[0]],
      bankFirs: [],
      shouldShowRequestButton: false,
      isFirsRequestFailed: false,
    });
  });

  test('When only internal firs are passed with status processing', () => {
    const internalFirs = generateInternalFirs(INTERNAL_FILES, FileStatus.PROCESSING);
    const response = getCategorizedFirsFiles(internalFirs);

    expect(response).toStrictEqual({
      internalFirs,
      bankFirs: [],
      shouldShowRequestButton: false,
      isFirsRequestFailed: false,
    });
  });

  test('internalfirs should always come at the bottom of the list', () => {
    const internalFirs = generateInternalFirs(INTERNAL_FILES, FileStatus.PROCESSING);
    const response = getCategorizedFirsFiles(internalFirs.slice().reverse());

    expect(response).toStrictEqual({
      internalFirs,
      bankFirs: [],
      shouldShowRequestButton: false,
      isFirsRequestFailed: false,
    });
  });
});

describe('Tests for isRequestFirsEnabled', () => {
  test('Should return true when experiment is enabled', () => {
    const expObject = { variables: { result: 'on' } };
    expect(isRequestFirsEnabled(expObject)).toBe(true);
  });

  test('Should return false when experiment is disabled', () => {
    const expObject = { variables: { result: 'off' } };
    expect(isRequestFirsEnabled(expObject)).toBe(false);
  });

  test('Should return false when experiment variables in not present in object', () => {
    //wroung key
    const expObject = { variable: { result: 'off' } };
    expect(isRequestFirsEnabled(expObject)).toBe(false);
  });

  test('Should return false when experiment variables value is null', () => {
    //wroung key
    const expObject = { variable: null };
    expect(isRequestFirsEnabled(expObject)).toBe(false);
  });
});
