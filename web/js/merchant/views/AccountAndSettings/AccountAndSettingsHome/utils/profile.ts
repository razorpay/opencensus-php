import {
  InfoDataInterface,
  InfoDataPayload,
  HANDLERS,
  PersonalProfileFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { InfoDataConfig } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/profile';

export const getInfoData = ({ user, profile }: InfoDataPayload): InfoDataInterface[] => {
  return InfoDataConfig.reduce((accumulator, each) => {
    const { isVisible, shouldEdit, getValue, ...rest } = each;
    if (isVisible({ user, profile })) {
      const infoObject = { ...rest };
      infoObject.value = getValue({ user });
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
