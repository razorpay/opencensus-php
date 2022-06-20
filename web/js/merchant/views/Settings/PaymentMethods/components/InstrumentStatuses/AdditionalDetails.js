import React from 'react';
import { Link } from 'react-router-dom';
import Popover, { PopoverBody } from 'common/ui/Popover';
import PropTypes from 'prop-types';

import { statusClass, statusPopoverText } from '../../constants';

const AdditionalDetails = (props) => {
  const { displayName, currentValue, url, status, statusIdentifier } = props;
  const displayStatus = status?.replace('_', ' ') || '';
  return (
    <div className="details-field">
      <div>
        <p>{displayName}</p>
        <p className="text-muted">{currentValue?.length > 0 ? currentValue : '--'}</p>
      </div>
      <div>
        {statusIdentifier === 'redirect_to_form' ? (
          <Link to={url} title="External Link">
            Add now <i className="i i-external-link" />
          </Link>
        ) : (
          <>
            {['under_review', 'rejected'].includes(status) && (
              <Link to={url} title="External Link">
                View Details <i className="i i-external-link" />
              </Link>
            )}
            <div className={statusClass[status]}>
              {displayStatus}
              <Popover align="bottom" theme="dark">
                <PopoverBody>
                  <div className="popover-text">{statusPopoverText[status]}</div>
                </PopoverBody>
              </Popover>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

AdditionalDetails.propTypes = {
  displayName: PropTypes.string,
  currentValue: PropTypes.string,
  url: PropTypes.string,
  status: PropTypes.string,
  statusIdentifier: PropTypes.string,
};

export default AdditionalDetails;
