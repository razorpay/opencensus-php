export const FIRS_START_YEAR = 2015;
export const MONTH_OFFSET = 10;
export const RECENT_MONTH_OFFSET = 2;

export const FileType = {
  FIRS_INTERNAL_FILE: 'firs_internal_file',
  FIRS_INTERNAL_AMEX_FILE: 'firs_internal_amex_file',
  FIRS_ICICI_ZIP: 'firs_icici_zip',
  FIRS_FILE: 'firs_file',
  FIRS_FIRSTDATA_FILE: 'firs_firstdata_file',
};

export const FileStatus = {
  PROCESSING: 'processing',
  PROCESSED: 'processed',
  FAILED: 'failed',
  NO_DATA: 'noData',
};

export const FileName = {
  [FileType.FIRS_ICICI_ZIP]: 'ICICI',
  [FileType.FIRS_FILE]: 'RBL',
  [FileType.FIRS_FIRSTDATA_FILE]: 'ICICI',
  [FileType.FIRS_INTERNAL_AMEX_FILE]: 'Amex',
  [FileType.FIRS_INTERNAL_FILE]: 'Razorpay statement',
};

export const PopupType = {
  DOWNLOAD_FIRS: 'download_firs',
  NO_FIRS: 'no_firs',
  INTERNAL_FIRS: 'internal_firs',
  SUCCESS_MODAL: 'success_modal',
};

export const PopupTitle = {
  [PopupType.DOWNLOAD_FIRS]: 'Download FIRS',
  [PopupType.NO_FIRS]: 'No FIRS files found',
  [PopupType.INTERNAL_FIRS]: 'Request for Razorpay statement',
  [PopupType.SUCCESS_MODAL]: 'Request for Razorpay statement',
};

export const INTERNAL_FILES = [FileType.FIRS_INTERNAL_AMEX_FILE, FileType.FIRS_INTERNAL_FILE];
export const BANK_FILES = [
  FileType.FIRS_ICICI_ZIP,
  FileType.FIRS_FIRSTDATA_FILE,
  FileType.FIRS_FILE,
];

export const monthList = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];
