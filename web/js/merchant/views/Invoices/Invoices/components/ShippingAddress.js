import { stringifyAddress } from 'common/utils/rzp-utils';

const ShippingAddress = (props) => {
  const {
    isFetchingAddresses,
    isDisabled,
    customer,
    showSelectAddressModal,
    areShippingAddressActionsVisible,
    selectShippingAddress,
    selectedShippingAddress,
    track,
  } = props;
  return (
    <div className="inv__address-container inv__address-container-shipping">
      <label className="text-uppercase">Shipping Address</label>
      <span className={`two-btn-group ${areShippingAddressActionsVisible ? '' : 'invisible'}`}>
        <button
          className="btn btn-sm btn-link"
          onClick={showSelectAddressModal('shipping')}
          type="button"
        >
          Change
        </button>
        <button
          className="btn btn-sm btn-link"
          onClick={() => {
            selectShippingAddress(null);
            track({
              eventAction: 'Remove Address',
              eventLabel: 'Shipping',
            });
          }}
          type="button"
        >
          Remove
        </button>
      </span>
      <div className="inv__address-container">
        {selectedShippingAddress ? (
          stringifyAddress(selectedShippingAddress)
        ) : (
          <div className="light-placeholder">
            {isFetchingAddresses ? (
              <>Loading...</>
            ) : !isDisabled ? (
              customer && customer.id ? (
                <button
                  className="btn btn-link"
                  onClick={showSelectAddressModal('shipping')}
                  type="button"
                >
                  + Add Shipping Address
                </button>
              ) : (
                <>Select customer to add Shipping Address</>
              )
            ) : (
              <>Shipping Address not applicable.</>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default ShippingAddress;
