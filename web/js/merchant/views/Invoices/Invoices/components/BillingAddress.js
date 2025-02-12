import { stringifyAddress } from 'common/utils/rzp-utils';

const BillingAddress = (props) => {
  const {
    isFetchingAddresses,
    isDisabled,
    customer,
    showSelectAddressModal,
    areBillingAddressActionsVisible,
    selectBillingAddress,
    selectedBillingAddress,
    track,
  } = props;
  return (
    <div className="inv__address-container">
      <label className="text-uppercase">Billing Address</label>
      <span className={`two-btn-group ${areBillingAddressActionsVisible ? '' : 'invisible'}`}>
        <button
          className="btn btn-sm btn-link"
          onClick={showSelectAddressModal('billing')}
          type="button"
        >
          Change
        </button>
        <button
          className="btn btn-sm btn-link"
          onClick={() => {
            selectBillingAddress(null);
            track({
              eventAction: 'Remove Address',
              eventLabel: 'Billing',
            });
          }}
          type="button"
        >
          Remove
        </button>
      </span>
      <div className="inv__address-container">
        {selectedBillingAddress ? (
          stringifyAddress(selectedBillingAddress)
        ) : (
          <div className="light-placeholder">
            {isFetchingAddresses ? (
              <>Loading...</>
            ) : !isDisabled ? (
              customer && customer.id ? (
                <button
                  className="btn btn-link"
                  onClick={showSelectAddressModal('billing')}
                  type="button"
                >
                  + Add Billing Address
                </button>
              ) : (
                <>Select customer to add Billing Address</>
              )
            ) : (
              <>Billing Address not applicable.</>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default BillingAddress;
