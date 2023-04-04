import {
  ACTION_REQUIRED,
  ACTIVATED_ACTION_REQUIRED,
  REQUESTED,
  ACTIVATED,
  REJECTED,
  GREYED,
} from 'merchant/views/Settings/PaymentMethods/constants';

import { IntermediateInsName } from './styles';

export function getListClass({ status = '', path = '', user = {} }) {
  if ([REJECTED, ACTION_REQUIRED].includes(status)) {
    return 'action-required-list-item';
  } else if (status === ACTIVATED_ACTION_REQUIRED) {
    return 'activated-action-required-list-item';
  } else if (status === GREYED || user?.isInstrumentRequestHidden) {
    return 'list-item-disabled';
  } else if ((status === ACTIVATED && path === 'pg.wallet.paytm') || status === REQUESTED) {
    return 'list-item-has-description';
  } else if (path === 'pg.upi.google_pay') {
    return 'list-item-has-long-description';
  } else {
    return 'list-item';
  }
}

export function displayName({ name, intermediateInstrument }) {
  if (
    (intermediateInstrument && !['cards', 'netbanking'].includes(intermediateInstrument.slug)) ||
    !intermediateInstrument
  ) {
    return <strong data-testid="direct-ins-name">{name}</strong>;
  }

  return <IntermediateInsName data-testid="intermediate-ins-name">{name}</IntermediateInsName>;
}
