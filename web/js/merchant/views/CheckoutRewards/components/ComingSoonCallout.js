import React from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';

export default function Details({
  isJoinedWaitlist = false,
  isPending = false,
  joinTheWaitlist = (f) => f,
}) {
  return (
    <div class={`ComingSoonCallout ${isJoinedWaitlist ? 'joined-waitlist' : ''}`}>
      <div class="title">Coming Soon</div>
      <div class="content-container">
        {isJoinedWaitlist ? (
          <>
            <div className="description">
              We are happy to have you in our waitlist! If you would like to speak with us about
              Checkout Rewards please{' '}
              <a
                className="early-access-form"
                href="https://forms.gle/j4zMUSw5vKBrjQge8"
                target="_blank"
                rel="noreferrer noopener"
              >
                Click here
              </a>
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
