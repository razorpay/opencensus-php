import {
  DataDurationType,
  RepetitionType,
} from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleReport/types';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { SelectedRangeType, TimePickerRes } from 'merchant_common/views/Reports/components/types';

export interface InitialStateType {
  isCustomEnabled: boolean;
  isRunForeverEnabled: boolean;
  showErrorInSection?: number;
  isResetComplete: boolean;

  // section 1
  selectedConfig?: BaseConfigType;
  scheduleName: string;
  selectedFormat?: {
    label: string;
    value: string;
  };
  selectedAccount?: DataDurationType;

  // section 2
  selectedDataDuration?: DataDurationType;
  selectedRepetition?: RepetitionType;
  customDataDuration?: SelectedRangeType;
  whenTime?: TimePickerRes;

  // section 3
  recipients: string[];

  // misc
  isSubmitButtonLoading: boolean;
}
