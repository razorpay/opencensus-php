import {
  HANDLERS,
  InfoDataInterface,
  InfoDataPayload,
  PersonalProfileFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

import { getI18FormattedPhoneNumber } from 'merchant/components/Mask/Contact';

export const getInfoData = ({
  user,
  profile,
  dataConfig,
  isRevampedInfo,
}: InfoDataPayload): InfoDataInterface[] => {
  return dataConfig.reduce((accumulator, each) => {
    const { isVisible, shouldEdit, getValue, ...rest } = each;
    let value;
    if (isVisible({ user, profile, isRevampedInfo })) {
      const infoObject = { ...rest };
      if (each.id === PersonalProfileFields.CONTACT_MOBILE) {
        // We only want to format Phone Number
        value = getI18FormattedPhoneNumber(getValue({ user }));
      } else {
        value = getValue({ user });
      }
      infoObject.value = value;
      infoObject.isEditEnable = shouldEdit({ user });
      if (each.id === PersonalProfileFields.EMAIL) {
        const {
          user: { email },
        } = user;
        if (email) {
          infoObject.handlerType = HANDLERS.UPDATE;
        } else {
          infoObject.handlerType = HANDLERS.ADD;
          infoObject.queryParam = undefined;
        }
      }
      accumulator.push(infoObject);
    }
    return accumulator;
  }, [] as InfoDataInterface[]);
};
