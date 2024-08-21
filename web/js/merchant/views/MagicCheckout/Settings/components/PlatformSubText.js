import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  CONTENT,
  NESTED_VIEW_TYPE,
  PLATFORMS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { APP_VIEW_RADIO_OPTIONS } from 'merchant/views/MagicCheckout/Settings/constants';
import MagicCheckoutToggle from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/MagicCheckoutToggle';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { Text } from '@razorpay/blade/components';
import { analyticsTrack } from 'common/utils/analytics';
import { updateAppView } from 'merchant/reducers/magicCheckout';
import { RCOD_APP_NAME, MAGIC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';

const PlatformSubText = ({
  nested_view_type,
  shop_id,
  updatePage,
  updateSettings,
  platform,
  shipping_info,
  merchantId,
  one_click_checkout,
  user,
  apps_installed,
  dashboard_view,
  updateAppInStore,
}) => {
  let domainSubText = shop_id;
  const { isShopifyMagicEnabled, isMagicWoocEnabled } = user;

  if (platform === PLATFORMS.VALUES.WOOCOMMERCE) {
    domainSubText = shipping_info?.split('wp-json')[0];
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
        screen: 'platform settings l1',
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

    const onAppChange = ({ target }) => {
      const { value } = target;

      updateSettings(
        {
          platform,
          shop_id,
          dashboard_view: value,
        },
        true,
      ).then(() => updateAppInStore(value));
    };

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
            data-testid="platform-edit-icon"
          >
            <i className="i i-edit_board platform-edit-icon" />
            Edit
          </div>
        </div>
        {apps_installed?.length > 1 ? (
          <>
            <div className="display-flex align-center margin-t-16 apps-toggle">
              <Text size="medium" weight="semibold" marginRight="12px">
                Toggle checkout platform:
              </Text>
              <Input.Radio
                name="app_select"
                options={APP_VIEW_RADIO_OPTIONS}
                defaultValue={dashboard_view || MAGIC_APP_NAME}
                onChange={onAppChange}
              />
            </div>
            <Text size="small" marginTop="8px" color="surface.text.gray.muted">
              Please select your active checkout on Shopify - Magic Checkout/Shopify One-page
              Checkout(Magic X)
            </Text>
          </>
        ) : null}
        {isShopifyMagicEnabled && dashboard_view !== RCOD_APP_NAME && (
          <div className="display-flex align-center margin-t-16">
            <MagicCheckoutToggle />
          </div>
        )}
      </div>
    );
  }
  return (
    <>
      <div className="display-flex align-center margin-t-16">
        <div className="font-12 platform-details">{CONTENT[platform]?.VIEW_LABEL}</div>
        {domainSubText && platform === PLATFORMS.VALUES.WOOCOMMERCE ? (
          <div className="font-12 platform-details">{domainSubText}</div>
        ) : null}
        <div
          className="font-12 platform-details platform-edit pointer"
          onClick={() => updatePage(NESTED_VIEW_TYPE.PLATFORM_SELECTION)}
          data-testid="platform-edit-icon"
        >
          <i className="i i-edit_board platform-edit-icon" />
          Edit
        </div>
      </div>
      {platform === PLATFORMS.VALUES.WOOCOMMERCE && isMagicWoocEnabled && (
        <div className="display-flex align-center margin-t-16">
          <MagicCheckoutToggle />
        </div>
      )}
    </>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateAppInStore: updateAppView,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(PlatformSubText);
