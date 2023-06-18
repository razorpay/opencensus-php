import { currencyList, getSplitzExperiments } from './mocks/fixtures';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';
import {
  getCurrentFinancialYear,
  stringTemplate,
  getCurrencyConfig,
  getFormattedAmount,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  mergeCurrencyFormatting,
  exportFileAsExcel,
} from 'common/utils/rzp-utils';
import FileSaver from 'file-saver';
import xlsx from 'xlsx';

const saveAsSpy = jest.spyOn(FileSaver, 'saveAs');
const writeSpy = jest.spyOn(xlsx, 'write');

describe('test for getCurrentFinancialYear', () => {
  it('should return correct financial year for 31st march', () => {
    jest.useFakeTimers('modern');
    jest.setSystemTime(new Date(2023, 2, 31));
    const currentYear = getCurrentFinancialYear();
    expect(currentYear).toBe(2022);
    jest.useRealTimers();
  });

  it('should return correct financial year for 1st april', () => {
    jest.useFakeTimers('modern');
    jest.setSystemTime(new Date(2023, 3, 1));
    const nextYear = getCurrentFinancialYear();
    expect(nextYear).toBe(2023);
    jest.useRealTimers();
  });
});

test('stringTemplate', () => {
  const str = '/notes/{category}?noteId={noteId}';
  const replacer = { category: 'development', noteId: '1' };

  expect(stringTemplate(str, replacer)).toBe('/notes/development?noteId=1');
});

describe('Tests for currency formatting', () => {
  beforeAll(() => {
    window.rzp_user = {
      splitz_experiments: getSplitzExperiments(abExperimentsMap.n_exponent_support),
    };
  });

  test('getCurrencyConfig should refer to INR when no currency is passed', () => {
    const { decimals, formatter } = getCurrencyConfig();
    expect(decimals).toBe(2);
    expect(formatter('1234567.12', decimals)).toBe('12,34,567.12');
  });

  test('getCurrencyConfig should refer to local currency list when window.currencyList is undefined', () => {
    const { decimals } = getCurrencyConfig('KWD');
    expect(decimals).toBe(3);
  });

  test('getCurrencyConfig should return decimals as 2 and default formatting if Currency is not found in the list', () => {
    const { decimals, formatter } = getCurrencyConfig('ABC');
    expect(decimals).toBe(2);
    expect(formatter(1234567.12, decimals)).toBe('1,234,567.12');
  });

  test('getCurrencyConfig should return default formatting function if formatting is not present for a currency', () => {
    const { decimals, formatter } = getCurrencyConfig('USD');
    expect(formatter(1000000.12, decimals)).toBe('1,000,000.12');
  });

  test('getCurrencyConfig should return specific formatting function if formatting is present for a currency', () => {
    const { decimals, formatter } = getCurrencyConfig('INR');
    expect(formatter(1000000.12, decimals)).toBe('10,00,000.12');
  });

  test('getFormattedAmount should return value with correct formatting if only amount is passed', () => {
    expect(getFormattedAmount(11111)).toBe('111.11');
  });

  test('getFormattedAmount should return value with correct formatting if amount and currency is passed', () => {
    expect(getFormattedAmount(11111, 'KWD')).toBe('11.111');
  });

  test('getFormattedAmount should return value with correct formatting if currency does not exist in the list', () => {
    expect(getFormattedAmount(11111, 'ABC')).toBe('111.11');
  });

  test('getFormattedAmount should return value with correct formatting for INR if number with length > 7 is passed', () => {
    expect(getFormattedAmount(1111111)).toBe('11,111.11');
  });

  test('getFormattedAmount should return value with correct formatting for currencies with specific format', () => {
    expect(getFormattedAmount(1111111, 'AUD')).toBe('11 111.11');
  });

  test('getFormattedAmount should return value with default formatting if no formatting is associated with a currency', () => {
    expect(getFormattedAmount(11111111, 'USD')).toBe('111,111.11');
  });

  test('getFormattedAmount should return value with default formatting if currency is not found in the mapper', () => {
    expect(getFormattedAmount(11111111, 'ABC')).toBe('111,111.11');
  });

  test('getCurrencyConfig should refer to local window.currencyList when it is defined', () => {
    currencyList.KWD.denomination = 10000;
    window.currencyList = currencyList;

    const { decimals } = getCurrencyConfig('KWD');

    expect(decimals).toBe(4);
    window.currencyList = null;
  });

  test('getCurrencyConfig should return 2 if experiment is disabled', () => {
    window.rzp_user = {
      splitz_experiments: getSplitzExperiments(abExperimentsMap.n_exponent_support, 'off'),
    };

    const { decimals } = getCurrencyConfig('kwd');

    expect(decimals).toBe(2);
  });
});

describe('Tests for mergeCurrencyFormatting', () => {
  test('Function should return static currency object if wrong data type is passed in arguments', () => {
    const data = mergeCurrencyFormatting('dummy data');
    expect(typeof data).toBe('object');
    expect(data.default).toBeDefined();
  });

  test('Function should return merged data when correct data is passed', () => {
    const data = mergeCurrencyFormatting(currencyList);
    expect(typeof data).toBe('object');
    expect(data.default).toBeDefined();
    expect(data.INR.format).toBeDefined();
  });
});

describe('Download Sample File', () => {
  writeSpy.mockImplementation(() => jest.fn());
  saveAsSpy.mockImplementation(() => jest.fn());
  const fileName = 'test';
  let fileFormat = 'xlsx';
  const finalDataSend = [
    {
      category: 'sample_pl_LpoFCooJAk0a2j',
      data: [
        {
          Amount: '',
          'Primary Reference ID': '',
          Email: '',
          Phone: '',
        },
      ],
    },
  ];

  test('should download in xlsx format', () => {
    exportFileAsExcel({ finalDataSend, fileName, fileFormat });
    expect(FileSaver.saveAs).toHaveBeenCalledWith(new Blob(), 'test.xlsx');
  });

  test('should download in csv format', () => {
    fileFormat = 'csv';
    exportFileAsExcel({ finalDataSend, fileName, fileFormat });
    expect(FileSaver.saveAs).toHaveBeenCalledWith(new Blob(), 'test.csv');
  });
});

describe('Tests for unit conversion', () => {
  beforeAll(() => {
    window.rzp_user = {
      splitz_experiments: getSplitzExperiments(abExperimentsMap.n_exponent_support),
    };
  });

  test('i18CurrencyConversionFromCommonUnitToMinorUnit should return correct conversion when 2 decimal currency is passed', () => {
    expect(i18CurrencyConversionFromCommonUnitToMinorUnit(100.123, 'INR')).toBe(10012);
  });

  test('i18CurrencyConversionFromCommonUnitToMinorUnit should return correct conversion when 3 decimal currency is passed', () => {
    expect(i18CurrencyConversionFromCommonUnitToMinorUnit(100.12, 'KWD')).toBe(100120);
  });

  test('i18CurrencyConversionFromCommonUnitToMinorUnit should return correct conversion for 2 decimal when currency passed does not exist', () => {
    expect(i18CurrencyConversionFromCommonUnitToMinorUnit(100.12, 'ABC')).toBe(10012);
  });

  test('i18CurrencyConversionFromMinorUnitToCommonUnit should return correct conversion when 2 decimal currency is passed', () => {
    expect(i18CurrencyConversionFromMinorUnitToCommonUnit(100123, 'INR')).toBe(1001.23);
  });

  test('i18CurrencyConversionFromMinorUnitToCommonUnit should return correct conversion when 3 decimal currency is passed', () => {
    expect(i18CurrencyConversionFromMinorUnitToCommonUnit(10012, 'KWD')).toBe(10.012);
  });

  test('i18CurrencyConversionFromMinorUnitToCommonUnit should return correct conversion for 2 decimal when currency passed does not exist', () => {
    expect(i18CurrencyConversionFromMinorUnitToCommonUnit(10012, 'ABC')).toBe(100.12);
  });
});
