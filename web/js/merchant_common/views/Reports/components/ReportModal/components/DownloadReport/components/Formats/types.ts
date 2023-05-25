import { Delimiter, Format } from 'merchant_common/views/Reports/types';

export interface FormatsProps {
  availableFormats: Format[];
  selectedFormat: Format | undefined;
  selectedDelimiter: Delimiter | undefined;
  setSelectedFormat: (x: Format) => void;
  setSelectedDelimiter: (x: Delimiter) => void;
  showErrorInSection?: number;
}
