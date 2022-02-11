import FeeDetails from 'merchant/views/MagicCheckout/common/components/FeeDetails';

const ShippingMethodsListing = ({
  warehouse_pincode,
  onShippingSettingClick,
  enable_cod,
  shipping_fee_rule,
  cod_fee_rule,
}) => (
  <div className="serviceability-settings-cta border-shipping-setting">
    <div className="row">
      <div className="col-md-10">
        <div className="display-flex width-100">
          <div className="serviceability-settings-item-label display-inline">Warehouse Pincode</div>
          <span className="font-bold color-black">{warehouse_pincode}</span>
        </div>
        <div className="display-flex">
          <div className="serviceability-settings-item-label display-inline">COD Availability</div>
          <span className="cod_settings_val">{enable_cod ? 'Yes' : 'No'}</span>
        </div>
      </div>
      <div
        className="serviceability-setting-edit padding-8 pointer display-inline float-right"
        onClick={() => onShippingSettingClick(true, 'put')}
      >
        <i className="font-12 i i-edit" />
        Edit
      </div>
    </div>
    <FeeDetails {...shipping_fee_rule} type="shipping" label="Shipping" />
    {enable_cod ? <FeeDetails {...cod_fee_rule} type="cod" label="COD" /> : null}
  </div>
);

export default ShippingMethodsListing;
