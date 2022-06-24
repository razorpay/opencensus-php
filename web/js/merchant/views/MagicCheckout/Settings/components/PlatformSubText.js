import {
  CONTENT,
  NESTED_VIEW_TYPE,
  PLATFORMS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrack } from 'common/utils/analytics';

const PlatformSubText = ({
  nested_view_type,
  shop_id,
  updatePage,
  platform,
  shipping_info,
  merchantId,
  one_click_checkout,
}) => {
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
  if (platform === PLATFORMS.VALUES.SHOPIFY) {
    const handleUpdatePage = () => {
      analyticsTrack({
        objectName: '1ccclickededitplatforml1screen',
        actionName: 'behav',
        properties: {
          magic_checkout_enabled: one_click_checkout,
          platform,
          store_id: shop_id || null,
          merchant_id: merchantId,
        },
      });
      updatePage(NESTED_VIEW_TYPE.PLATFORM_SELECTION);
    };

    const getDomainSubText = (domainSubText) =>
      domainSubText.includes('myshopify.com') ? domainSubText : `${domainSubText}.myshopify.com`;

    return (
      <div>
        <div className="display-flex align-center margin-t-16">
          <div className="platform-label">
            <div className="font-14">{getDomainSubText(domainSubText)}</div>
            <i className="i i-info-outline">
              <Popover align="bottom" theme="dark">
                <PopoverBody>
                  <div>The Store ID of your Shopify website e.g. test.myshopify.com</div>
                </PopoverBody>
              </Popover>
            </i>
          </div>
          <div
            className="font-12 platform-details platform-edit pointer"
            onClick={handleUpdatePage}
          >
            <i className="i i-edit_board platform-edit-icon" />
            Edit
          </div>
        </div>
      </div>
    );
  }
  return (
    <div className="display-flex align-center margin-t-16">
      <div className="font-12 platform-details">{CONTENT[platform]?.VIEW_LABEL}</div>
      {domainSubText && platform === PLATFORMS.VALUES.WOOCOMMERCE ? (
        <div className="font-12 platform-details">{domainSubText}</div>
      ) : null}
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
