import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose, ActionCreator, bindActionCreators } from 'redux';
import { useFormik } from 'formik';
import * as Yup from 'yup';
import FileUploadButton from 'common/ui/FileUpload/Button';
import rzpLogo from 'assets/rzpLogo.svg';
import rzpWhiteLogo from 'assets/rzpWhiteLogo.svg';
import { showNotification } from 'merchant_common/reducers/notifications';
import { AsyncBtn } from 'common/new-ui/Button';
import { MobilePreviewDetails } from './MobilePreviewDetails';
import { MobilePreviewWelcome } from './MobilePreviewWelcome';
import { DesktopPreviewDetails } from './DesktopPreviewDetails';
import { DesktopPreviewWelcome } from './DesktopPreviewWelcome';
import { Carousel } from './Carousel';
import { useFetchConfig, useSaveConfig } from './useQueries';
import { classList } from 'common/utils/rzp-utils';
import Spinner from 'common/ui/Spinner';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare/index';
import { merchantFetch } from 'merchant/utils/ajax';
import { getInitialState } from './utils';
import { Notification } from 'common/typings/Store/notifications';

declare global {
  interface Window {
    colorLib?: TODO_PD;
  }
}
interface WhiteLabelThemePropsT {
  user?: TODO_PD;
  showNotification?: ActionCreator<Notification>;
  appId?: null | string;
}

const WhiteLabelTheme = ({
  user,
  showNotification,
  appId = null,
}: WhiteLabelThemePropsT): JSX.Element => {
  const [isMobilePreview, setIsMobilePreview] = useState<boolean>(false);
  const { id } = user;
  const { data, isLoading, isError, refetch } = useFetchConfig(id, appId, showNotification);
  const { saveConfig } = useSaveConfig(refetch, showNotification);

  const validationSchema = Yup.object().shape({
    brand_color: Yup.string()
      .required('Required!')
      .matches(/^#([0-9A-F]{6})$/, 'Please enter 6 digit hex color with #'),
    text_color: Yup.string()
      .required('Required!')
      .matches(/^#([0-9A-F]{6})$/),
    brand_name: Yup.string().max(30, 'Max length is 30'),
  });

  const formik = useFormik({
    initialValues: getInitialState({ isError, data }),
    enableReinitialize: true,
    validationSchema,
    validateOnChange: false,
    validateOnBlur: true,
    onSubmit: (values) => {
      saveConfig(values);
    },
  });

  const updatePreviewClr = (e: React.ChangeEvent<HTMLInputElement>): void => {
    const themeClr = e.target.value;
    const txtClr = !window?.colorLib || window?.colorLib?.isDark(themeClr) ? '#FFFFFF' : '#060606';
    formik.setFieldValue('brand_color', themeClr.trim().toUpperCase(), false);
    formik.setFieldValue('text_color', txtClr.trim().toUpperCase());
  };

  useEffect(() => {
    const script = document.createElement('script');

    script.src = 'https://cdn.razorpay.com/static/assets/color.js';

    document.head.appendChild(script);
  }, []);

  const handlePreviewSwitch = (device: string): void => {
    if (device === 'mobile') {
      setIsMobilePreview(true);
    } else {
      setIsMobilePreview(false);
    }
  };

  const handleUploadLogo = async (event: React.ChangeEvent<HTMLInputElement>): Promise<void> => {
    if (event?.target?.files && event?.target?.files.length > 0) {
      const configId = formik.values.config_id;
      const file = event.target.files[0];

      const formData = new FormData();
      formData.append('logo', file);

      try {
        const response = await merchantFetch({
          url: `partner_config/${configId}/logo`,
          method: 'post',
          data: formData,
        });
        if (response?.success) {
          const { data } = response;
          if (data?.partner_metadata?.logo_url) {
            formik.setFieldValue('brand_logo', data.partner_metadata.logo_url);
            showNotification?.({
              type: 'success',
              message: 'Logo Uploaded Successfully',
            });
          }
        }
      } catch ({ errors }) {
        showNotification?.({
          type: 'error',
          message: errors,
        });
      }
    }
  };

  if (isLoading) {
    return (
      <div className="page-spinner-container">
        <Spinner center={true} />
      </div>
    );
  }
  return (
    <div id="partner-settings-container">
      <div className="panel-section--theme">
        <div className="panel-heading">
          <span className="title">Onboarding Flow Settings</span>
        </div>
        <div className="sub-nav-container">
          <div className="sub-nav-link">Basic Settings</div>
        </div>
        <div className="panel-body">
          <form onSubmit={formik.handleSubmit}>
            <label className="partner-configurator-label">Theme Color</label>
            <div className="partner-color-picker-container">
              <div className="color-picker-container">
                <input
                  name="brand_color"
                  aria-label="brand_color"
                  className="color-picker"
                  type="color"
                  value={formik.values.brand_color}
                  onChange={(e) => {
                    updatePreviewClr(e);
                  }}
                />
              </div>
              <div className="color-input">
                <input
                  name="brand_color"
                  aria-label="brand_color"
                  type="text"
                  value={formik.values.brand_color}
                  className="form-control"
                  onChange={(e) => {
                    updatePreviewClr(e);
                  }}
                  onBlur={formik.handleBlur}
                />
              </div>
            </div>
            {formik?.errors?.brand_color && (
              <span className="error-helper" data-testId="clrErr">
                {formik.errors.brand_color}
              </span>
            )}
            <div className="description">
              Choose a theme color for your brand.
              <br />
              The default theme color will be used if none is specified.
            </div>

            <div className="upload-logo-section">
              <label className="partner-configurator-label">Your Logo</label>
              <div className="upload-logo-container">
                {formik.values.brand_logo !== '' && (
                  <div className="logo-container">
                    <img
                      className="partner-logo"
                      src={formik.values.brand_logo}
                      width="100"
                      height="100"
                      alt="upload Logo"
                    />
                  </div>
                )}
                <div className="description-container">
                  <div className="description">
                    Choose a square image of your company logo.
                    <br />
                    Minimum dimensions 256x256 px.
                    <br />
                    Max file size: 1MB
                  </div>
                  <FileUploadButton
                    text={formik.values?.brand_logo ? 'Change Logo' : 'Choose File'}
                    labelClass="btn-primary"
                    data-testId="upInput"
                    accept="image/jpeg,image/jpg,image/png"
                    maxSize="1048576"
                    onChange={handleUploadLogo}
                  />
                </div>
              </div>
            </div>
            <div className="brand-name-container">
              <label className="partner-configurator-label">Enter Your Brand Name</label>
              <div className="brand-name-input">
                <input
                  name="brand_name"
                  aria-label="brand_name"
                  type="text"
                  className="form-control"
                  value={formik.values.brand_name}
                  onChange={formik.handleChange}
                />
              </div>
              {formik?.errors?.brand_name && (
                <span className="error-helper">{formik.errors.brand_name}</span>
              )}
              <div className="description">
                Default Name will be used on the welcome screen. Max character
                <br />
                limit 30.
              </div>
            </div>
            <div>
              <AsyncBtn.Primary
                disabled={formik.dirty === false || formik.isValid === false}
                className="btn btn-primary"
                onClick={formik.handleSubmit}
                type="submit"
                isPending={formik.isSubmitting}
                showLoader={true}
                pendingState="Saving..."
              >
                Save
              </AsyncBtn.Primary>
            </div>
          </form>
        </div>
      </div>
      <div className="preview-section">
        <div id="preview-label">Preview</div>
        <div className="carouselDiv">
          {isMobilePreview ? (
            <Carousel
              carouselItems={[
                <MobilePreviewWelcome
                  key="car2"
                  brandColor={formik.values.brand_color}
                  textColor={formik.values.text_color}
                  uploadLogo={formik.values.brand_logo}
                  brandName={formik.values.brand_name}
                />,
                <MobilePreviewDetails
                  key="car1"
                  brandColor={formik.values.brand_color}
                  textColor={formik.values.text_color}
                  rzpLogo={rzpLogo}
                />,
              ]}
            />
          ) : (
            <Carousel
              carouselItems={[
                <DesktopPreviewWelcome
                  key="preview-desktop-welcome"
                  brandName={formik.values.brand_name}
                  brandColor={formik.values.brand_color}
                  textColor={formik.values.text_color}
                  uploadLogo={formik.values.brand_logo}
                  rzpLogo={formik.values.text_color === '#FFFFFF' ? rzpWhiteLogo : rzpLogo}
                />,
                <DesktopPreviewDetails
                  key="preview-desktop-details"
                  brandName={formik.values.brand_name}
                  brandColor={formik.values.brand_color}
                  textColor={formik.values.text_color}
                  uploadLogo={formik.values.brand_logo}
                  rzpLogo={rzpLogo}
                />,
              ]}
            />
          )}
        </div>
        <div className="preview-switch-container">
          <div className="button-container">
            <div
              className={classList(
                'button-text',
                isMobilePreview ? 'transparant-button' : 'white-button',
              )}
              onClick={() => {
                handlePreviewSwitch('desktop');
              }}
            >
              Desktop
            </div>
            <div
              className={classList(
                'button-text',
                isMobilePreview ? 'white-button' : 'transparant-button',
              )}
              onClick={() => {
                handlePreviewSwitch('mobile');
              }}
            >
              Mobile
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default compose<TODO_PD>(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
)(WhiteLabelTheme);
