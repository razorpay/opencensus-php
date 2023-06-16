import { Fragment } from 'react';
import {
  SAVED_CONFIGS,
  DISCOUNT_INFO,
  DISCOUNT_TYPE,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';

const CODPrepaidSavedConfigs = (props) => {
  const { setIsConfigSaved, prepayCODConfigs, isManualReviewOpted } = props;

  return (
    <>
      <div className="configurations-box saved-configs-view">
        <div className="saved-configs-container">
          <div className="saved-configs-header">
            <p>Settings</p>
            <div
              className="configs-edit pointer"
              onClick={() => setIsConfigSaved(false)}
              data-testid="edit-cta"
            >
              <i className="i i-edit_board configs-edit-icon" />
              Edit
            </div>
          </div>
          <hr />
          <div className="saved-configs-values-container">
            {SAVED_CONFIGS.map((config, idx) => {
              if (config.key !== 'riskCategory' || isManualReviewOpted) {
                return (
                  <Fragment key={config.key}>
                    <div className="saved-config display-flex flex--column gap--4">
                      <p className="saved-config-condition">{config.title}</p>
                      <p className="saved-config-action">{config.getText(prepayCODConfigs)}</p>
                      {config.key === 'discount' &&
                        prepayCODConfigs?.discount?.type !== DISCOUNT_TYPE.zero && (
                          <div className="discount-info">
                            <p className="info-text">{DISCOUNT_INFO}</p>
                          </div>
                        )}
                    </div>
                    {idx !== SAVED_CONFIGS.length - 1 && <hr />}
                  </Fragment>
                );
              } else {
                return null;
              }
            })}
          </div>
        </div>
      </div>
    </>
  );
};

export default CODPrepaidSavedConfigs;
