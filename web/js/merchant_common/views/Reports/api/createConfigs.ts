import { merchantFetch } from 'merchant/utils/ajax';
import {
  AvailableColumnsType,
  CustomReportConfigType,
  CustomReportType,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/types';
import { ResType } from 'merchant_common/views/Reports/api/types';

export const getConfigById = async (id: string): Promise<ResType<CustomReportType>> => {
  return merchantFetch({
    url: `reporting/configs/${id}`,
    method: 'get',
    headers: { 'X-Report-Type': 'merchant' },
  });
};

export const fetchReportData = async (
  reportType: string,
  source: string,
): Promise<ResType<AvailableColumnsType>> => {
  return merchantFetch({
    url: `reporting/merchant/config-components/${reportType}?source=${source}`,
    method: 'get',
  });
};

export const createOrCloneConfig = async (
  configPayload: CustomReportConfigType,
): Promise<ResType<CustomReportType>> => {
  return merchantFetch({
    url: `reporting/merchant/configs`,
    method: 'post',
    data: configPayload,
  });
};

export const deleteConfig = async (id: string): Promise<ResType<CustomReportType>> => {
  return merchantFetch({
    url: `reporting/configs/${id}`,
    method: 'delete',
    headers: { 'X-Report-Type': 'merchant' },
  });
};

export const editConfig = async (
  id: string,
  configPayload: CustomReportConfigType,
): Promise<ResType<CustomReportType>> => {
  return merchantFetch({
    url: `reporting/merchant/configs/${id}`,
    method: 'patch',
    data: configPayload,
  });
};
