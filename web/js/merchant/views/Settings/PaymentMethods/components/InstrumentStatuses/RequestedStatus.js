import React from 'react';
import moment from 'moment';
import { REQUESTED } from 'merchant/views/Settings/PaymentMethods/constants';
import { getDisabledInstruments } from 'merchant/views/Settings/PaymentMethods/utils';

import { disabledMessagesForInstrument } from 'merchant/views/Settings/PaymentMethods/components/LeafListItem/utils';

export const RequestedStatus = ({ instrument, tat, user }) => {
  const { status, name } = instrument;
  const isTatExpired =
    tat && moment().diff(moment.unix(instrument.created_at).add(tat[instrument.path], 'days')) > 0;
  const estimatedEnablementTime =
    tat &&
    moment.unix(instrument.created_at).add(tat[instrument.path], 'days').format('Do MMMM YYYY');

  const disabledInstruments = getDisabledInstruments(user);
  // Variable to check whether instrument is disabled i.e has paused new merchant onboarding
  const instrumentDisabledMessage = disabledMessagesForInstrument(name, disabledInstruments);
  const showRequestedStatusBlock = status === REQUESTED || instrumentDisabledMessage;

  return showRequestedStatusBlock ? (
    <div className="flex-end instrument-description">
      <div className="instrument-description-container">
        <i className="i i-info-outline" />
        <p className="description">
          {instrumentDisabledMessage || (
            <>
              Estimated {name} date of enablement:{' '}
              <span className="description-strong">
                {estimatedEnablementTime} {isTatExpired && '*'}
              </span>
            </>
          )}
        </p>
      </div>
      {isTatExpired && !disabledInstruments.includes(name) && (
        <p className="disclaimer">
          Sorry for the inconvenience, the request is taking longer than usual.
        </p>
      )}
    </div>
  ) : null;
};
