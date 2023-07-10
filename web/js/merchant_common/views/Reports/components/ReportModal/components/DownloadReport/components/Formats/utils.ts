import { Delimiter, Format } from 'merchant_common/views/Reports/types';
import { DELIMITER_SUPPORT_MAP } from './constants';

export const getAvailableDelimiter = (selectedFormat?: Format): Delimiter[] => {
  if (selectedFormat?.value) {
    return DELIMITER_SUPPORT_MAP[selectedFormat.value] || [];
  }

  return [];
};
