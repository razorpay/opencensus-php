import { User, LeafListItem } from 'common/typings';

export const isInternationalLeafItemDisabled = ({
  leafList,
  user,
  isB2BEnabled,
}: {
  leafList: LeafListItem;
  user: User;
  isB2BEnabled: boolean;
}): boolean => {
  const { slug } = leafList || {};

  return (
    (slug === 'localcurrencytransfer' && (!isB2BEnabled || !user?.international)) ||
    (slug === 'swiftbanktransfer' && (!isB2BEnabled || !user?.international)) ||
    (slug === 'instantbanktransfer' && !user?.international)
  );
};
