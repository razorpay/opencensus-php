import React from 'react';
import PropTypes from 'prop-types';
import sanitizer from 'common/utils/xss-sanitizer';
import { Box } from '@razorpay/blade/components';

const SubText = (props) => {
  let text = props.text;
  if (props.optOut) {
    text += ` <span class="opt-out" id="rtbOptOut">Opt-out</span>`;
  }

  React.useEffect(() => {
    if (props.trackEvent && (props.showKnowMore || props.showDocTnCLink)) {
      props.trackEvent('RTBKnowMoreRendered');
    }
  }, [props]);

  return (
    <>
      <div
        onClick={props.handleOptOut}
        className={`trusted-sub-text ${props.className || ''}`}
        dangerouslySetInnerHTML={{ __html: sanitizer(text) }}
      />
      {props.showDocTnCLink && (
        <div className="sub-text-link">
          <a
            href="https://razorpay.com/docs/payment-gateway/dashboard-guide/trusted-badge/"
            target="_blank"
            rel="noreferrer noopener"
            onClick={() => {
              props.trackEvent('RTBKnowMoreClicked');
            }}
          >
            Know More
          </a>{' '}
          |{' '}
          <a
            target="_blank"
            rel="noreferrer noopener"
            href="https://razorpay.com/trusted-badge/merchant-terms/"
            onClick={() => {
              props.trackEvent('RTBTermsClicked');
            }}
          >
            View T&amp;C
          </a>
        </div>
      )}
      {!props.showDocTnCLink && props.showKnowMore && (
        <div className="sub-text-link">
          <a
            href="https://razorpay.com/docs/payment-gateway/dashboard-guide/trusted-badge/"
            target="_blank"
            rel="noreferrer noopener"
            onClick={() => {
              props.trackEvent('RTBKnowMoreClicked');
            }}
          >
            Know More
          </a>
        </div>
      )}
      {props.buyerProtectionLinks && (
        <Box marginTop="8px">
          <a
            href="https://drive.google.com/file/d/1X016w8TXE5LPlRW0CJXFHHsNqiQqTUQ1/view?usp=sharing"
            target="_blank"
            rel="noreferrer noopener"
          >
            Learn More
          </a>{' '}
          about how Buyer Protection can benefit your store and fill in your interest in this{' '}
          <a
            href="https://docs.google.com/forms/d/e/1FAIpQLSfBHHJuUZ7tTQGR5SNOaMCs1PviM9bCgZEIkGSLhv_edOK-vA/viewform?usp=sf_link"
            target="_blank"
            rel="noreferrer noopener"
          >
            form
          </a>{' '}
          to make your business stand out!
        </Box>
      )}
    </>
  );
};

SubText.propTypes = {
  text: PropTypes.string.isRequired,
  optOut: PropTypes.bool,
  showDocTnCLink: PropTypes.bool,
  showKnowMore: PropTypes.bool,
  className: PropTypes.string,
  handleOptOut: PropTypes.func.isRequired,
  trackEvent: PropTypes.func.isRequired,
};

export default SubText;
