import React, { useState } from 'react';
import moment from 'moment';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import LocalStorageService from 'common/utils/localStorage';

export default function Details({
  title,
  subtitle,
  details,
  imgSrc,
  content,
  isRTBProgramEnabled,
  isJoinedWaitlist,
  isOptedOut,
  joinTheWaitlist,
  optOutConfirmation,
}) {
  const [isPending, setPending] = useState(false);
  const tiralPeriodEnd = '31-05-2021';

  const joinWaitlist = () => {
    setPending(true);
    joinTheWaitlist().then((res) => {
      if (res === 'Success') {
        setPending(false);
      }
    });
  };

  const optOutClicked = () => {
    optOutConfirmation();
  };

  if (isRTBProgramEnabled && content !== 'qualification') {
    imgSrc = 'https://cdn.razorpay.com/static/assets/trustedbadge/rtb_introduction_enabled.svg';
  } else if (isJoinedWaitlist && content !== 'qualification') {
    imgSrc = 'https://cdn.razorpay.com/static/assets/trustedbadge/rtb_introduction_waiting.svg';
  }

  return (
    <div className={`row ${content === 'qualification' ? 'qualification-row' : ''}`}>
      <div className="col-lg-6">
        <div className="tb-title">{title}</div>
        {subtitle && <div className="tb-subtitle">{subtitle}</div>}

        <div className={`small-separator ${content === 'qualification' ? 'green-colored' : ''}`} />

        {details &&
          details.map((item, index) => {
            let children = item;
            let increaseLineHeight = false;
            if (item.indexOf('{end-date}') != -1) {
              increaseLineHeight = true;
              const endDate = moment(tiralPeriodEnd, 'DD-MM-YYYY').format('DD MMM YYYY');
              const diff = moment(tiralPeriodEnd, 'DD-MM-YYYY').diff(moment(), 'days');
              children = (
                <>
                  {item.replace('{end-date}', '')} <span className="trial-end-date">{endDate}</span>
                  {` (in ${diff} days)`}
                </>
              );
            }
            return (
              <div key={index} className="details-lines">
                <div className="detail-done-icon">
                  <i className={`i i-done ${increaseLineHeight ? 'increase-icon-line' : ''}`} />
                </div>
                <div className="detail-span">
                  <span>{children}</span>
                </div>
              </div>
            );
          })}

        {isRTBProgramEnabled ? (
          content !== 'qualification' && (
            <div className="opt-out-block">
              {isOptedOut ? (
                <div className="opt-out-block--after">
                  <span>We are processing your opt-out request</span>
                  <span>We will soon remove the badge from checkout</span>
                </div>
              ) : (
                <div className="opt-out-block--before">
                  <span>Not interested? </span>
                  <span onClick={optOutClicked}>Opt-out</span>
                </div>
              )}
            </div>
          )
        ) : (
          <>
            {isJoinedWaitlist ? (
              <Button className="joined-button">
                Joined the waitlist
                <i className="i i-tick" />
              </Button>
            ) : (
              <AsyncBtn.Primary onClick={joinWaitlist} showLoader={true}>
                Join the waitlist
                {isPending && <span className="spin-btn white" />}
              </AsyncBtn.Primary>
            )}
            <div className="info-cta">Be a part of our trusted merchant community</div>
          </>
        )}
      </div>

      <div className="col-lg-6 image-center">
        <img
          src={imgSrc}
          className={`${content === 'qualification' ? 'qualification-image' : 'intro-image'}`}
        />
      </div>
    </div>
  );
}
