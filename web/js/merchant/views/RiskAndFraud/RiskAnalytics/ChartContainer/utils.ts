import { ChartOption } from './types';

export const generateChartOptions = (labels: string[], values: string[]): ChartOption[] => {
  return labels.map((label, index) => ({ label, value: values[index] }));
};
