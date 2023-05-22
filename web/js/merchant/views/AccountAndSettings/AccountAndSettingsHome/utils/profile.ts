import {
  HANDLERS,
  InfoDataInterface,
  InfoDataPayload,
  PersonalProfileFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

export const getInfoData = ({
  user,
  profile,
  dataConfig,
  isRevampedInfo,
}: InfoDataPayload): InfoDataInterface[] => {
  return dataConfig.reduce((accumulator, each) => {
    const { isVisible, shouldEdit, getValue, ...rest } = each;
    if (isVisible({ user, profile, isRevampedInfo })) {
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
