import React, { useMemo } from 'react';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';
import ShowWhen from 'merchant/components/ShowWhen';
import { isOrgFeatureExist } from 'merchant/models/User';
import { getCustomURL } from 'merchant/components/DocsLink';
import { useI18Service } from 'common/i18';

const SettlementGuideText = ({ user }) => {
  const showRzpBranding =
    user?.isOrgAllowedFunctionality?.('external_links') &&
    !isOrgFeatureExist('hide_razorpay_text_link');
  const docHref = useMemo(() => getCustomURL('http://razorpay.com/settlement'), []);
  const { isConfigTagEnabled } = useI18Service();

  return (
    <div className="settlement-row">
      <div className="col-md-6 col-md-offset-3 col-sm-12 text-center">
        <div>The amount that gets settled to your bank account will show up here.</div>
        <ShowWhen additionalCondition={() => !isConfigTagEnabled('settlements.settlement_guide')}>
          <div>
            <ShowWhen additionalCondition={() => showRzpBranding}>
              <a className="btn-link" target="_blank" rel="noopener noreferrer" href={docHref}>
                See our Settlements Guide
              </a>
            </ShowWhen>
            <ShowWhen additionalCondition={() => !showRzpBranding}>
              See the Settlements Guide
            </ShowWhen>
            &nbsp; to understand how it works.
          </div>
        </ShowWhen>
      </div>
    </div>
  );
};

SettlementGuideText.propTypes = {
  user: PropTypes.object,
};

export default connect((state) => ({ user: state.session.user }), null)(SettlementGuideText);
