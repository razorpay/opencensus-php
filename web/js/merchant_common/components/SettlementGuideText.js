import React from 'react';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';
import ShowWhen from 'merchant/components/ShowWhen';
import { isOrgFeatureExist } from 'merchant/models/User';

const SettlementGuideText = ({ user }) => {
  const showRzpBranding =
    user?.isOrgAllowedFunctionality?.('external_links') &&
    !isOrgFeatureExist('hide_razorpay_text_link');
  return (
    <div className="settlement-row">
      <div className="col-md-6 col-md-offset-3 col-sm-12 text-center">
        <div>The amount that gets settled to your bank account will show up here.</div>
        <div>
          <ShowWhen additionalCondition={() => showRzpBranding}>
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              See our Settlements Guide
            </a>
          </ShowWhen>
          <ShowWhen additionalCondition={() => !showRzpBranding}>
            See the Settlements Guide
          </ShowWhen>
          &nbsp; to understand how it works.
        </div>
      </div>
    </div>
  );
};

SettlementGuideText.propTypes = {
  user: PropTypes.object,
};

export default connect((state) => ({ user: state.session.user }), null)(SettlementGuideText);
