import moment from 'moment';
import { insightsDateRangePresets } from 'merchant/views/Insights/constants';

interface DateRange {
  from: number; 
  to: number; 
}

export const getInitialDateRange = (): DateRange => {
  const preset = insightsDateRangePresets[0];
  const [from, to] = preset.value();
  return {
    from: moment(from).unix(),  // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
    to: moment(to).unix(),  // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
  };
}; 