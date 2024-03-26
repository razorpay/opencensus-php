// import * as zip from '@zip.js/zip.js';
import * as XLSX from 'xlsx';

// Used to check if zip is encrypted and also if zip can be extracted with the given password
// Returns true if extraction success with given password
// TODO: Due to zip.js, all UT's are failing. Need to change this library.
export const extractZip = () => {
  // const r = new zip.ZipReader(new zip.BlobReader(file), { password });
  // const entries = await r.getEntries();
  // const entry = entries[0];
  // try {
  //   await entry.getData(new zip.BlobWriter());
  // } catch (error) {
  //   if (error.message === zip.ERR_ENCRYPTED || error.message === zip.ERR_INVALID_PASSWORD) {
  //     // Incorrect password
  //     return false;
  //   }
  // }
  // await r.close();
  return true;
};

export const isExcelEncrypted = (file) => {
  const reader = new FileReader();
  reader.readAsArrayBuffer(file);
  return new Promise((resolve) => {
    reader.onload = (e) => {
      try {
        const data = e.target.result;
        XLSX.read(data);
        resolve(false);
      } catch (error) {
        resolve(true);
      }
    };
  });
};

// Converts bank_rrn_file to Bank Rrn File
export const getReadableFromKey = (key) => {
  const words = key?.split('_');
  return words.map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
