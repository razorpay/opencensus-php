import React from 'react';
import moment from 'moment';
import { REQUESTED } from '../../constants';

export const RequestedStatus = ({ instrument, tat }) => {
  const { status } = instrument;
  const isTatExpired =
    tat && moment().diff(moment.unix(instrument.created_at).add(tat[instrument.path], 'days')) > 0;
  const estimatedEnablementTime =
    tat &&
    moment.unix(instrument.created_at).add(tat[instrument.path], 'days').format('Do MMMM YYYY');
  return status === REQUESTED ? (
    <div className="flex-end instrument-description">
      <div className="instrument-description-container">
        <i className="i i-info-outline" />
        <p className="description">
          Estimated date of enablement:{' '}
          <span className="description-strong">
            {estimatedEnablementTime} {isTatExpired && '*'}
          </span>
        </p>
      </div>
      {isTatExpired && (
        <p className="disclaimer">
          Sorry for the inconvenience, the request is taking longer than usual.
        </p>
      )}
    </div>
  ) : null;
};
