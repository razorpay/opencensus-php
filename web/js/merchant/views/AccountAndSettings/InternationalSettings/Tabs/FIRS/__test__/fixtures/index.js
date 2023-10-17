import {
  FileType,
  FileStatus,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';

/* default file object */
export const fileObject = {
  id: '123456789',
  document_type: FileType.FIRS_FILE,
  file_status: FileStatus.PROCESSED,
};

/* default date object */
export const dateObject = {
  month: 'September',
  year: 2022,
};

/**
 * creates an array of internal firs
 * @param {*} month - data for month
 * @param {*} year - data for year
 * @param {*} type - array of type (icici/firsdata/zip)
 * @returns
 */
export const generateBankFirs = (type) => {
  return type.map((t, index) => ({
    id: `bank-${index}`,
    document_type: t,
    file_status: FileStatus.PROCESSED,
  }));
};

/**
 * creates an array of internal firs
 * @param {*} month - data for month
 * @param {*} year - data for year
 * @param {*} type - array of type (amex/internal)
 * @param {*} status - status of internal file
 * @returns
 */
export const generateInternalFirs = (type, status = FileStatus.PROCESSED) => {
  return type.map((t, index) => ({
    id: `internal-${index}`,
    document_type: t,
    file_status: t === FileType.FIRS_INTERNAL_FILE ? status : FileStatus.PROCESSED,
  }));
};
