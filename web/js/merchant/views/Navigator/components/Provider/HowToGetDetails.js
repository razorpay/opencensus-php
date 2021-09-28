import ModalHeader from 'common/ui/ModalHeader';
import { gatewayDetailsMapping } from '../util';

export const HowToGetDetails = ({ providers, selectedProvider, closeModal }) => {
  const gatewayName = providers[selectedProvider]['Gateway Name'].data_value;

  return (
    <div className="gateway-details-desc--how-to-modal">
      <ModalHeader onCloseClick={closeModal} title={`Where do I find ${gatewayName} details?`} />
      <div className="modal-body">
        {selectedProvider === 'atom' ? (
          <div className="atom-block-wrapper">
            <p className="atom-block-title">Have a registered Atom Business Account</p>
            <p className="atom-block-desc">
              For API keys details, you need to refer to the excel sheet shared by Atom on your
              registered email Id.
            </p>
          </div>
        ) : (
          <img src={gatewayDetailsMapping[selectedProvider].dashboardImg} />
        )}
        <div className="acc-info-msg">
          <i className="i i-info-outline" />
          Don’t have a registered {gatewayName} Business Account, register on{' '}
          <a
            href={gatewayDetailsMapping[selectedProvider].dashboardUrl}
            className="gateway-dashboard-url"
            target="_blank"
            rel="noopener noreferrer"
          >
            {gatewayDetailsMapping[selectedProvider].dashboardUrlLabel}
          </a>
        </div>
      </div>
      <div className="modal-footer">
        <button className="btn btn-primary" onClick={closeModal}>
          Got It
        </button>
      </div>
    </div>
  );
};
