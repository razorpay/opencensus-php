import Input from 'common/new-ui/Input';
import { useFormikContext } from 'formik';
import Banner from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner';
import { BannerType } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import WebsiteDetailsSections from 'merchant/views/Settings/Configuration/components/WebsiteDetailsSections';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { getProductOptions, getProductValue, getWebsiteDetailsInfo } from './utils';
import { trackIEEvent } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import { getPreferredProduct } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils';

const BusinessDetails = ({
  user,
  disabled,
  triggerSource,
  saveFormData,
  isRevampFlow,
  closeModal,
}) => {
  const [productOptions, setProductOptions] = useState([]);
  const formikProps = useFormikContext();
  const websiteInfo = getWebsiteDetailsInfo(user);

  const getError = (name) =>
    (formikProps.touched[name] ? formikProps.errors[name] : '') ||
    (!!formikProps.status ? formikProps.status[name] : '');

  const handleChange = () => saveFormData(formikProps);

  const handleCheckboxChange = (value, productValue) => {
    let products = [];
    if (value === '1') {
      products = [...formikProps.values.products, ...productValue];
    } else {
      products = formikProps.values.products.filter((item) => !productValue.includes(item));
    }
    trackIEEvent({
      objectName: 'Preferred Product',
      actionName: 'Clicked',
      properties: {
        preferred_product: getPreferredProduct(products),
      },
      subSection: 'Info Form',
    });
    formikProps.setFieldValue('products', products);
  };

  useEffect(() => {
    let products = getProductOptions(isRevampFlow, websiteInfo.isWebsiteDetails);
    if (triggerSource) {
      products = products.filter(
        (opts) => opts.value.toString() === getProductValue(triggerSource),
      );
    }
    setProductOptions(products);
  }, [isRevampFlow, triggerSource, websiteInfo.isWebsiteDetails]);

  return (
    <div class="business-details">
      <div class="main-title">BUSINESS DETAILS</div>
      <div class="sub-title">
        International payments are associated with a higher risk of frauds and chargeback, hence it
        is governed by strict risk evaluations policies laid down by our banking partners
      </div>
      {isRevampFlow && !websiteInfo.isWebsiteDetails && (
        <Banner type={BannerType.WEBSITE_DETAIL_UPDATE} />
      )}
      {isRevampFlow ? (
        <Input.Group
          required
          label="Choose product(s) to collect international payments on"
          className="product-options"
        >
          <div class="Input-content">
            {productOptions.map((each, index) => (
              <Input.Check
                key={`check-${index}`}
                autoRender
                required
                fieldLabel={each.label}
                className="Input--vTop"
                checked={formikProps.values.products.includes(each.value[0])}
                onChange={(e) => handleCheckboxChange(e.target.value, each.value)}
                disabled={disabled || each.disabled}
                description={each.disabled ? 'Registered website required' : ''}
              />
            ))}
          </div>
        </Input.Group>
      ) : (
        <Input.Radio
          required
          name="products"
          label="Enable international payments on"
          onBlur={handleChange}
          options={productOptions}
          defaultValue={formikProps.values.products.toString()}
          disabled={disabled}
          className="Input--vTop"
          propagatedError={getError('products')}
        />
      )}

      <div class="spacer" />
      <Input.Select
        required
        name="goods_type"
        label="Goods Type"
        value={formikProps.values.goods_type}
        options={[
          { label: '--Select--', name: '' },
          { label: 'Physical goods', name: 'physical_goods' },
          { label: 'Digital services', name: 'digital_services' },
          { label: 'Both', name: 'both' },
        ]}
        disabled={disabled}
        onBlur={handleChange}
        mature={formikProps.touched.goods_type}
        propagatedError={getError('goods_type')}
      />

      <Input.Textarea
        required
        name="business_use_case"
        label="Business Use-Case"
        disabled={disabled}
        value={formikProps.values.business_use_case}
        placeholder="Why do you need international payments (Min 50 Chars)"
        info={`Ex: "We sell apparels, unisex. Most of our customers are from abroad, so we need to enable international card acceptance for that reason"`}
        onBlur={handleChange}
        mature={formikProps.touched.business_use_case}
        propagatedError={getError('business_use_case')}
        showCharacterLength={(val) => (val?.length ? val.length : null)}
      />

      <Input.Select
        required
        name="business_txn_size"
        disabled={disabled}
        value={formikProps.values.business_txn_size}
        label="Average Transaction Size for your Business"
        options={[
          { label: '--Select in INR--', name: '' },
          { label: '<5000', name: '0=5000' },
          { label: '5000 - 10,000', name: '5000=10000' },
          { label: '10,000 - 25,000', name: '10000=250000' },
          { label: '25,000 - 50,000', name: '25000=500000' },
          { label: '50,000 - 1,00,000', name: '50000=100000' },
          { label: '>1,00,000 ', name: '100000=-1' },
        ]}
        onBlur={handleChange}
        mature={formikProps.touched.business_txn_size}
        info="This will put a upper cap on your transaction size. You can later change it by contacting support"
        propagatedError={getError('business_txn_size')}
      />

      {isRevampFlow ? (
        websiteInfo.isWebsiteDetails ? (
          <WebsiteDetailsSections websiteInfo={websiteInfo} closeModal={closeModal} />
        ) : null
      ) : (
        <Input
          required
          name="about_us_link"
          label="Website / App Link"
          disabled={disabled}
          value={formikProps.values.about_us_link}
          mature={formikProps.touched.about_us_link}
          placeholder="Enter Website / App Link"
          info={{
            'Sample Website url': 'https://www.google.com',
            App:
              'Please provide Google play store URL; In case your app is not hosted on google play store, share any other app store URL',
            'Sample App url': 'https://play.google.com/store/apps/details?id=com.whatsapp',
          }}
          onBlur={handleChange}
          propagatedError={getError('about_us_link')}
        />
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(BusinessDetails);
