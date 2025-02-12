import React from 'react';
import { Link } from 'react-router-dom';
import DocsLink from 'merchant/components/DocsLink';
import Banner from 'common/ui/Banner';
import { getMode } from 'merchant/store';

export default React.memo(function DirectTransferBanner() {
  const track = _track();
  track.onViewBanner();

  return (
    <div className="direct-transfer-banner">
      <Banner>
        <i className="i i-route" />
        <div className="content">
          <h4>Introducing Direct Transfers on Route</h4>
          <div className="desc">
            Now create <b>Direct Transfers</b> to your linked accounts <b>from Route!</b>
            <DocsLink
              url="https://razorpay.com/docs/route/dashboard/"
              title="Learn more"
              onClick={track.onClickDocsLink}
            />
          </div>
          <Link
            to="/route/transfers"
            className="btn Button--primary--invert explore-now-btn"
            onClick={track.onClickCTA}
          >
            Explore Now
          </Link>
        </div>
      </Banner>
    </div>
  );
});

function _track() {
  const mode = getMode();

  function onViewBanner() {
    window.rzpQ.push(
      window.rzpQ.routeActions().success('route.direct_transfers.banner.show', {
        mode,
      }),
    );
  }

  function onClickCTA() {
    window.rzpQ.push(
      window.rzpQ.routeActions().interaction('route.direct_transfers.banner.click_goto_transfers', {
        mode,
      }),
    );
  }

  function onClickDocsLink() {
    window.rzpQ.push(
      window.rzpQ.routeActions().interaction('route.direct_transfers.banner.click_docs', {
        mode,
      }),
    );
  }

  return {
    onViewBanner,
    onClickCTA,
    onClickDocsLink,
  };
}
