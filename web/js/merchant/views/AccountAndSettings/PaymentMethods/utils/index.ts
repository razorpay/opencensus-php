import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
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

export const isRecurringInstrumentEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || {
    abExperiments: { recurring_instrument_requester: undefined },
  };
  if (!abExperiments?.recurring_instrument_requester) return false;
  return isExperimentEnabled(abExperiments.recurring_instrument_requester);
};
