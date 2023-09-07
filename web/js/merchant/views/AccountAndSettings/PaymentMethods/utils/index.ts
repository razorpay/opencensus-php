import { User, LeafListItem } from 'common/typings';

export const isInternationalLeafItemDisabled = ({
  leafList,
  user,
}: {
  leafList: LeafListItem;
  user: User;
}): boolean => {
  const { slug } = leafList || {};

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
