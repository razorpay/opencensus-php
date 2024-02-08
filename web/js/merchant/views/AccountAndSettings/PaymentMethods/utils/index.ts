import { User, LeafListItem } from 'common/typings';

export const isInternationalLeafItemDisabled = ({
  leafList,
  user,
  showMoreInternationalMethods,
}: {
  leafList: LeafListItem;
  user: User;
  showMoreInternationalMethods: boolean;
}): boolean => {
  const { slug } = leafList || {};

  if (
    slug === 'moreinternationalmethods' &&
    (!showMoreInternationalMethods || !user.international)
  ) {
    return true;
  }

  return (
    [
      'localcurrencytransfer',
      'swiftbanktransfer',
      'instantbanktransfer',
      'moneysaverexportaccount',
    ].includes(slug ?? '') &&
    (!user?.international || user?.isInternationalMethodsHidden)
  );
};
