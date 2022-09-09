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
    <div class="inv__address-container">
      <label class="text-uppercase">Billing Address</label>
      <span class={`two-btn-group ${areBillingAddressActionsVisible ? '' : 'invisible'}`}>
        <button
          class="btn btn-sm btn-link"
          onClick={showSelectAddressModal('billing')}
          type="button"
        >
          Change
        </button>
        <button
          class="btn btn-sm btn-link"
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
      <div class="inv__address-container">
        {selectedBillingAddress ? (
          stringifyAddress(selectedBillingAddress)
        ) : (
          <div class="light-placeholder">
            {isFetchingAddresses ? (
              <>Loading...</>
            ) : !isDisabled ? (
              customer && customer.id ? (
                <button
                  class="btn btn-link"
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
