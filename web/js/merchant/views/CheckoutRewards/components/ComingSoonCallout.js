import React from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';

export default function Details({
  isJoinedWaitlist = false,
  isPending = false,
  joinTheWaitlist = (f) => f,
}) {
  return (
    <div className={`ComingSoonCallout ${isJoinedWaitlist ? 'joined-waitlist' : ''}`}>
      <div className="title">Coming Soon</div>
      <div className="content-container">
        {isJoinedWaitlist ? (
          <>
            <div className="description">
            Thank you for joining our waitlist. We have recorded your interest and will be in touch soon!
            </div>
            <Button className="btn btn-waitlist callout-joined-button">
              Joined the waitlist
              <i className="i i-tick" />
            </Button>
          </>
        ) : (
          <>
            <div className="description">
              We are working hard to make rewards available for you soon. You can get{' '}
              <em className="early-access">Early access</em> by joining the waitlist.
            </div>
            <AsyncBtn
              className="btn btn-waitlist btn-border"
              onClick={joinTheWaitlist}
              showLoader={true}
            >
              Join the waitlist
              {isPending && <span className="spin-btn visible" />}
            </AsyncBtn>
          </>
        )}
      </div>
    </div>
  );
}
