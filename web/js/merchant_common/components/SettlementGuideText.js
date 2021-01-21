import React from 'react';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';

const SettlementGuideText = ({ user }) => {
  return (
    <div className="settlement-row">
      <div className="col-md-6 col-md-offset-3 col-sm-12 text-center">
        <div>The amount that gets settled to your bank account will show up here.</div>
        <div>
          {(user && user.isOrgAllowedFunctionality && user.isOrgAllowedFunctionality('external_links')) ? (
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              See our Settlements Guide
            </a>
          ) : (
              'See the Settlements Guide'
            )}{' '}
          to understand how it works.
        </div>
      </div>
    </div>
  );
};

SettlementGuideText.propTypes = {
  user: PropTypes.object,
};

export default connect((state) => ({ user: state.session.user }), null)(SettlementGuideText);
