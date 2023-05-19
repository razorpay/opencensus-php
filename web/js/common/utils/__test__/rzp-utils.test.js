import { getCurrentFinancialYear, stringTemplate, exportFileAsExcel } from 'common/utils/rzp-utils';
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
