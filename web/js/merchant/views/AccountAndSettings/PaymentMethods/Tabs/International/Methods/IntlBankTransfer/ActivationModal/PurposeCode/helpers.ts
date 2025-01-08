import { merchantFetch } from 'merchant/utils/ajax';

import { PurposeCodes } from './types';

export const getPurposeCode = () =>
  merchantFetch({
    url: 'purposecode',
  }).then<PurposeCodes>((res) => {
    return {
      groups: res?.data ?? [],
      codes: res?.data?.reduce((prev, item: PurposeCodes['groups'][0]) => {
        return prev.concat(
          item.codes.map((code) => ({
            purposeCode: code.purposeCode,
            description: code.description,
            helpText: item.purposeGroup,
          })),
        );
      }, []),
    };
  });
