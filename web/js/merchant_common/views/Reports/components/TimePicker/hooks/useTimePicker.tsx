import { useContext } from 'react';
import { TimePickerContext } from 'merchant_common/views/Reports/components/TimePicker/context/TimePickerProvider';
import { TimePickerContextType } from 'merchant_common/views/Reports/components/TimePicker/types';

export const useTimePicker = (): TimePickerContextType => useContext(TimePickerContext);
