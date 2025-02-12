import React from 'react';
import { compose } from 'redux';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import Button from 'common/new-ui/Button';

const modalImageType = {
  'JAN22-CATALYST-PP-EL-CTA1': 'pp-cross-sell-elearning-comp.png',
  'JAN22-CATALYST-PP-ED-CTA1': 'pp-cross-sell-courses-comp.png',
  'JAN22-CATALYST-PP-EC-CTA1': 'pp-cross-sell-ecommerce-comp.png',
};
function CatalystCampaign({
  alt = 'payment link',
  imageID = '',
  closeModal,
  history,
}): React.ReactElement {
  const handleCtaClick = () => {
    closeModal();
    history.push('/paymentpages/new');
  };
  return (
    <div className="container">
      <button type="button" className="close" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div className="image">
        <img
          src={`${window?.cdnBaseUrl}/static/assets/growth-assets/banner/${modalImageType[imageID]}`}
          alt={alt}
        />
      </div>
      <div className="button">
        <Button.Primary className="btn btn-primary" type="button" onClick={handleCtaClick}>
          Create a Payment Page
        </Button.Primary>
      </div>
    </div>
  );
}

export default withRouter<any>(
  compose(connect(null, { closeModal: fnCloseModal })(CatalystCampaign)),
);
