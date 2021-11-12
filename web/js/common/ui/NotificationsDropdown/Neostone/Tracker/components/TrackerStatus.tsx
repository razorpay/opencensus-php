import React, { useState } from 'react';
import XcaCTA from './XCACTA';
import { caApplicationStatus, bankNamesMap } from '../TrackerConstant';
import { ICICIKYCStatus, caApplicationBlockedICICIStatus } from '../ICICITrackerStatus';
import FooterCTA from './FooterCTA';
import { classList } from 'common/utils/rzp-utils';
import { setItem, getItem } from 'common/utils/localStorage';
import moment from 'moment';

interface statusType {
  highlight?: string;
  uiStatus?: string;
  code?: number;
  subText?: string;
}
let statusContent: Array<statusType> = [];
const statusClass = {
  200: 'gStatus',
  300: 'bStatus',
  400: 'rStatus',
  500: 'gyStatus',
  800: '',
};

const setTrackerViewLimit = () => {
  const lastSetTime = getItem('neostone-tracker');
  if (!lastSetTime) {
    const currentTime = moment().unix();
    setItem('neostone-tracker', currentTime);
  }
};

const getFooterLabel = (proceededBank, bankStatusForCTA) => {
  if (proceededBank === bankNamesMap.RBL && bankStatusForCTA === caApplicationStatus.ACTIVATED)
    return 'Explore Current Account';
  else if (proceededBank === bankNamesMap.ICICI) {
    if (
      bankStatusForCTA === ICICIKYCStatus.account_opened ||
      bankStatusForCTA === ICICIKYCStatus.registration_request_sent
    )
      return 'Activate Current Account';
    else if (bankStatusForCTA === ICICIKYCStatus.account_activated)
      return 'Explore Current Account';
  }
  return false;
};

const TrackerStatus = ({
  viewMoreStatus,
  viewLessStatus,
  bankStatusForCTA,
  proceededBank,
  iciciPan,
}) => {
  const [shouldShowMore, setShouldShowMore] = useState(false);
  const footerLabel = getFooterLabel(proceededBank, bankStatusForCTA);

  if (shouldShowMore) {
    statusContent = [...viewMoreStatus];
  } else {
    statusContent = [...viewLessStatus];
  }

  if (bankStatusForCTA === caApplicationStatus.ACTIVATED) setTrackerViewLimit();

  const handleExpandStatus = () => setShouldShowMore((prevState) => !prevState);

  let footerContent: React.ReactElement | null = null;
  if (footerLabel) footerContent = <FooterCTA footerLabel={footerLabel} />;
  else if (!caApplicationBlockedICICIStatus.includes(bankStatusForCTA))
    footerContent = (
      <div className="trackerStatus__viewMore" onClick={handleExpandStatus}>
        View {shouldShowMore ? 'Less' : 'More'}{' '}
        <i className={`i ${shouldShowMore ? 'i-chevron-up' : 'i-chevron-down'}`} />
      </div>
    );

  return (
    <div className="tracker">
      <div className="trackerStatus">
        {statusContent.map((item: statusType, index) => (
          <div
            className={classList(item?.highlight && 'trackerStatus--highlight')}
            key={`${index}-${item?.uiStatus}`}
          >
            <div
              className={`trackerStatus__statusList ${statusClass[item?.code || 800]} ${
                index + 1 === statusContent?.length && statusContent?.length > 1 && 'removeBorder'
              }`}
              key={item?.uiStatus}
            >
              <h5 className={`${item?.highlight ? 'neoBoldStatus' : ''}`}>{item?.uiStatus}</h5>
              {item?.highlight ? <p>{item?.subText}</p> : null}
            </div>
            {item?.highlight ? (
              <XcaCTA
                proceededBank={proceededBank}
                bankStatusForCTA={bankStatusForCTA}
                iciciPan={iciciPan}
              />
            ) : null}
          </div>
        ))}
        {footerContent}
      </div>
    </div>
  );
};

export default TrackerStatus;
