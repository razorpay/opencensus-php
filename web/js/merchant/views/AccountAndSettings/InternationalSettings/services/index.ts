import { merchantFetch } from 'merchant/utils/ajax';
import {
  FileType,
  monthList,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import {
  FirsDataType,
  FirsFileType,
} from 'merchant/views/AccountAndSettings/InternationalSettings/typings';
import {
  formatFirsData,
  getDataFromApi,
} from 'merchant/views/AccountAndSettings/InternationalSettings/utils';

/* FIRS Api's start -----> */

// eslint-disable-next-line consistent-return
export const fetchFirsData = async (year: number): Promise<FirsDataType | void> => {
  try {
    const response = await merchantFetch({
      url: 'merchant/firs',
      method: 'get',
      data: {
        year,
      },
    });
    const data = getDataFromApi(response);
    return {
      [year]: formatFirsData(data?.months ?? {}, year),
    };
  } catch {
    throw new Error('Data fetch failed! Please try again');
  }
};

// eslint-disable-next-line consistent-return
export const requestInternalFirs = async (
  month: string,
  year: number,
): Promise<FirsFileType | void> => {
  try {
    const response = await merchantFetch({
      url: 'merchant/firs',
      method: 'POST',
      data: {
        month: monthList.indexOf(month) + 1,
        year,
        type: FileType.FIRS_INTERNAL_FILE,
      },
    });
    const data = getDataFromApi(response);
    if (data) return data;
    else throw new Error();
  } catch {
    throw new Error('Something went wrong! please try again in some time');
  }
};

export const downloadFirsFile = async (
  month: string,
  year: number,
  document_id: string,
): Promise<void> => {
  try {
    const response = await merchantFetch({
      url: 'merchant/firs/content',
      method: 'get',
      data: {
        month: monthList.indexOf(month) + 1,
        year,
        document_id,
      },
    });
    const data = getDataFromApi(response);
    if (data?.signed_url) window.open(data.signed_url, '_blank');
    else throw new Error();
  } catch {
    throw new Error('There was an error in downloading the file. Please try again');
  }
};

/* FIRS Api's end -----> */
