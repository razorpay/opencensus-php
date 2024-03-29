import React from 'react';
import { Link, AlertCircleIcon } from '@razorpay/blade/components';
import { withRouter } from 'common/deprecated/withRouter';

import Popover, { PopoverBody } from 'common/ui/Popover';

const DateRangeTooltip = ({ history }) => {
  const handleRedirect = () => {
    history.push('/reports');
  };

  return (
    <div>
      <AlertCircleIcon color="interactive.icon.gray.normal" size="medium" />
      <Popover theme="dark" align="top">
        <PopoverBody>
          <div>
            Please refer to{' '}
            <Link variant="button" onClick={handleRedirect} size="small" color="">
              reports section
            </Link>{' '}
            for older than 90 days data.
          </div>
        </PopoverBody>
      </Popover>
    </div>
  );
};

export default withRouter(DateRangeTooltip);
