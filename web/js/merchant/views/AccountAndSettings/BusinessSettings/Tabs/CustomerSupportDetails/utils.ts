import { isMobile } from 'common/utils/validators';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import {
  CustomerSupportDetailProps,
  InfoDetailsPageProps,
} from 'merchant/views/AccountAndSettings/BusinessSettings/typings';
import { CONTACT_SUPPORT_DETAILS } from 'merchant/views/AccountAndSettings/BusinessSettings/constants/constants';

const ACCESS_ROLES = 'owner admin manager';

export const handlePrefix = (phone: string): string => {
  const prefix = '+91-';
  if (isMobile(phone)) {
    return prefix + phone;
  }
  return phone;
};

export const getInfoData = ({
  support_detail,
}: Pick<CustomerSupportDetailProps, 'support_detail'>): InfoDetailsPageProps[] => {
  return CONTACT_SUPPORT_DETAILS.reduce((acc, details): InfoDetailsPageProps[] => {
    const { getValue, ...rest } = details;
    acc.push({
      ...rest,
      value: getValue({ ...support_detail.data }),
      isEditEnable: showWhenUtil({ myRole: ACCESS_ROLES }),
    });
    return acc;
  }, [] as InfoDetailsPageProps[]);
};
