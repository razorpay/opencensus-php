import { utils, write } from "xlsx";
import { saveAs } from 'file-saver';

/**
 * Exports the provided data as an Excel or CSV file.
 *
 * @param {Object} params - The parameters for exporting the file.
 * @param {Array<{ category: string, data: any[] }>} params.finalDataSend - The data to be exported, divided by categories.
 * @param {string} params.fileName - The name of the file to be exported.
 * @param {string} params.fileFormat - The format of the file ('xlsx' or 'csv').
 * 
 * @example
 * const data = [
 *   { category: 'Sheet1', data: [{ id: 1, name: 'Item1' }, { id: 2, name: 'Item2' }] },
 *   { category: 'Sheet2', data: [{ id: 3, name: 'Item3' }] }
 * ];
 * exportFileAsExcel({ finalDataSend: data, fileName: 'Export', fileFormat: 'xlsx' });
 */
export const exportFileAsExcel = ({
  finalDataSend,
  fileName,
  fileFormat
}: {
  finalDataSend: { category: string, data: any[] }[],
  fileName: string,
  fileFormat: 'xlsx' | 'csv'
}): void => {
  const fileType = fileFormat === 'xlsx' ? 'xlsx' : 'csv';

  // Explicitly define the type for Sheets
  const obj = finalDataSend.reduce(
    (obj: { Sheets: { [key: string]: any }, SheetNames: string[] }, item) => {
      const json = utils.json_to_sheet(item.data);
      obj.Sheets[item.category] = json;
      obj.SheetNames.push(item.category);
      return obj;
    },
    { Sheets: {}, SheetNames: [] }
  );
  
  let excelBuffer;

  if (fileType === 'xlsx') {
    excelBuffer = write(obj, { bookType: 'xlsx', type: 'array' });
  } else if (fileType === 'csv') {
    excelBuffer = utils.sheet_to_csv(obj.Sheets[obj.SheetNames[0]]);
  }

  const data = new Blob([excelBuffer], { type: fileType });
  saveAs(data, `${fileName}.${fileFormat}`);
};
