/* eslint-disable @typescript-eslint/no-unused-vars */
import {
  BusinessModel,
  PosActivationStatus,
  FeeCategory,
  TableItem,
  FilterStateType,
} from '../types';

export const fetchCountByStatus = (status: PosActivationStatus, date: Date) => {
  return Math.floor(Math.random() * 100);
};

export const fetchMerchantsKYC = async (filters: FilterStateType): Promise<TableItem[]> => {
  await new Promise((r) => setTimeout(r, 500));
  return Promise.resolve([
    ...Array.from({ length: 20 }, (_, i) => ({
      id: (i + 1).toString(),
      mId: `rzp${Math.floor(Math.random() * 1000000)}`,
      merchantName: ['name 1', 'name2', 'name 3'][Math.floor(Math.random() * 3)],
      mobileNumber: '919999999999',
      initiatedOn: new Date(
        2021,
        Math.floor(Math.random() * 12),
        Math.floor(Math.random() * 28) + 1,
      ),
      status: [
        PosActivationStatus.UNDER_REVIEW,
        PosActivationStatus.NEEDS_CLARIFICATION,
        PosActivationStatus.KYC_QUALIFIED,
        PosActivationStatus.REJECTED,
        PosActivationStatus.ACTIVATED,
      ][Math.floor(Math.random() * 5)],
      pricing: [FeeCategory.CUSTOM, FeeCategory.STANDARD][Math.floor(Math.random() * 2)],
      businessModel: [BusinessModel.AGGREGATOR, BusinessModel.DIRECT][
        Math.floor(Math.random() * 2)
      ],
    })),
  ]);
};
