import React, { FC } from 'react';
import { Link } from 'react-router-dom';
import Popover, { PopoverBody } from 'common/ui/Popover';

import { statusClass, statusPopoverText } from '../../constants';

interface IAdditionalDetailsProps {
  displayName: string;
  currentValue: string;
  url: string;
  status: string;
  statusIdentifier: string;
}

const AdditionalDetails: FC<IAdditionalDetailsProps> = (props) => {
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

export default AdditionalDetails;
