import {
  CONTENT,
  NESTED_VIEW_TYPE,
  PLATFORMS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

const PlatformSubText = ({ nested_view_type, shop_id, updatePage, platform, shipping_info }) => {
  let domainSubText = shop_id;
  if (platform === PLATFORMS.VALUES.WOOCOMMERCE) {
    domainSubText = shipping_info.split('wp-json')[0];
  }
  if (nested_view_type !== NESTED_VIEW_TYPE.SETTINGS) {
    return (
      <div className="margin-t-8 platform-subtext">
        Select the platform of your ecommerce website.
      </div>
    );
  }
  return (
    <div className="display-flex align-center margin-t-16">
      <div className="font-12 platform-details">{CONTENT[platform]?.VIEW_LABEL}</div>
      {domainSubText ? <div className="font-12 platform-details">{domainSubText}</div> : null}
      <div
        className="font-12 platform-details platform-edit pointer"
        onClick={() => updatePage(NESTED_VIEW_TYPE.PLATFORM_SELECTION)}
      >
        <i className="i i-edit_board platform-edit-icon" />
        Edit
      </div>
    </div>
  );
};

export default PlatformSubText;
