import React from 'react';
import Input from 'common/new-ui/Input';
import { useFormikContext } from 'formik';

const QuickLinks = ({ disabled }) => {
  const formikProps = useFormikContext();
  const getError = (name) =>
    (formikProps.touched[name] ? formikProps.errors[name] : '') ||
    (!!formikProps.status ? formikProps.status[name] : '');

  return (
    <div class="quick-links">
      <div class="main-title">QUICK LINKS</div>
      <div class="sub-title">Quick links from your website is must to resolve future disputes</div>

      <Input
        required
        name="about_us_link"
        label="About us"
        disabled={disabled}
        value={formikProps.values.about_us_link}
        mature={formikProps.touched.about_us_link}
        placeholder="https://www.example.com/about-us"
        onBlur={formikProps.handleBlur}
        propagatedError={getError('about_us_link')}
      />

      <Input
        required
        label="Contact us"
        name="contact_us_link"
        disabled={disabled}
        onBlur={formikProps.handleBlur}
        value={formikProps.values.contact_us_link}
        mature={formikProps.touched.contact_us_link}
        placeholder="https://www.example.com/contact-us"
        description="Should contain your operating and registered address"
        propagatedError={getError('contact_us_link')}
      />
      <Input
        required
        name="terms_and_conditions_link"
        label="Terms and Conditions"
        disabled={disabled}
        onBlur={formikProps.handleBlur}
        placeholder="https://www.example.com/t&c"
        value={formikProps.values.terms_and_conditions_link}
        mature={formikProps.touched.terms_and_conditions_link}
        propagatedError={getError('contact_us_link')}
      />

      <Input
        required
        name="privacy_policy_link"
        label="Privacy Policy"
        disabled={disabled}
        onBlur={formikProps.handleBlur}
        placeholder="https://www.example.com/privacy"
        value={formikProps.values.privacy_policy_link}
        mature={formikProps.touched.privacy_policy_link}
        propagatedError={getError('privacy_policy_link')}
      />
      <Input
        required
        onBlur={formikProps.handleBlur}
        disabled={disabled}
        name="refund_and_cancellation_policy_link"
        label="Refund and Cancellation Policy"
        placeholder="https://www.example.com/refund"
        value={formikProps.values.refund_and_cancellation_policy_link}
        mature={formikProps.touched.refund_and_cancellation_policy_link}
        propagatedError={getError('refund_and_cancellation_policy_link')}
      />
      {formikProps.values.goods_type !== 'digital_services' && (
        <Input
          required
          name="shipping_policy_link"
          label="Shipping Policy"
          disabled={disabled}
          onBlur={formikProps.handleBlur}
          value={formikProps.values.shipping_policy_link}
          placeholder="https://www.example.com/shipping"
          mature={formikProps.touched.shipping_policy_link}
          propagatedError={getError('shipping_policy_link')}
        />
      )}
      <Input
        name="social_media_page_link"
        label="Social Media Page"
        disabled={disabled}
        onBlur={formikProps.handleBlur}
        placeholder="https://www.facebook.com/business"
        value={formikProps.values.social_media_page_link}
        mature={formikProps.touched.social_media_page_link}
        propagatedError={getError('social_media_page_link')}
        info="Please provide us with the link to your most used social media page on facebook/instagram/linkedin/others"
      />
    </div>
  );
};

export default QuickLinks;
