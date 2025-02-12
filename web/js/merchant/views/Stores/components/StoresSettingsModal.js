import { Link } from 'react-router-dom';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import Input, { Label, Description } from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';

import { showNotification } from 'merchant_common/reducers/notifications';
import { useState } from 'react';
import { deleteStoreEntity, updateStoreEntity } from '../model';
import { connect } from 'react-redux';
import { deleteStore, updateStore } from '../../../reducers/storefront';
import { slugValidator, storeNameValidator } from '../helpers';
import { isAmount } from 'common/utils/validators';
// import PluginsAndAddOns from './PluginsAndAddOns';

const StoresSettingsModal = (props) => {
  const [disableSubmit, setDisableSubmit] = useState(false);
  const [formData, setFormData] = useState({
    title: (props.entity.data && props.entity.data.title) || '',
    shipping_fees:
      (props.entity.data &&
        props.entity.data.settings.shipping_fees &&
        String(Number(props.entity.data.settings.shipping_fees) / 100)) ||
      '',
    shipping_days: (props.entity.data && props.entity.data.settings.shipping_days) || '',
    slug: (props.entity.data && props.entity.data.slug) || '',
  });

  // const isPluginConfigured = false;
  // const isPluginConfigured =
  //   paymentPageEntity.settings &&
  //   (paymentPageEntity.settings.pp_ga_pixel_tracking_id ||
  //     paymentPageEntity.settings.pp_fb_pixel_tracking_id);

  const onSubmit = (e) => {
    e.preventDefault();

    const request = {
      title: formData.title,
      // description: formData.description,
      slug: formData.slug,
      settings: {
        shipping_fees: formData.shipping_fees && Number(formData.shipping_fees) * 100,
        shipping_days: formData.shipping_days,
        // pp_fb_pixel_tracking_id: '',
        // pp_ga_pixel_tracking_id: '',
      },
    };

    return updateStoreEntity(request)
      .then((res) => {
        // update store, close modal, show toast message
        props.onClose();
        props.updateStore(res);
        props.showNotification({
          type: 'success',
          message: 'Store Settings updated successfully',
        });
      })
      .catch((err) => {
        // show error message
        props.showNotification({
          type: 'error',
          message: err.errors[0],
        });
      });
  };

  const onChange = (event) => {
    const { name, value } = event.target;

    setFormData((prevState) => ({
      ...prevState,
      [name]: value,
    }));

    setTimeout(() => {
      const form = document.getElementsByClassName('Modal-container--store-settings')[0];
      const _disableSubmit = form.querySelectorAll('.is-invalid').length;

      setDisableSubmit(_disableSubmit);
    });
  };

  const handleDeleteStore = () => {
    return deleteStoreEntity()
      .then(() => {
        // close modal, reset redux store, show toast message
        props.onClose();
        props.deleteStore();
        props.showNotification({
          type: 'success',
          message: 'Store deleted successfully',
        });
      })
      .catch(() => {
        // show error message
        props.showNotification({
          type: 'error',
          message: 'Store deletion failed',
        });
      });
  };

  // const openConfigurePluginsView = () => {
  // this.props.openModal({
  //   size: 'medium',
  //   className: 'PluginsAndAddOns',
  //   component: <PluginsAndAddOns />,
  // });
  // track.settings.clickConfigurePlugins();
  // };

  return (
    <ModalMask maskClosable={false}>
      <Modal showCloseBtn={false} className="store-settings">
        <div className="title">
          <i className="i-settings-outline mr-5" />
          <span>Store Settings</span>
        </div>
        <Form onSubmit={onSubmit} onChange={onChange}>
          <main>
            <Input
              autoRender
              name="title"
              value={formData.title}
              label="Store Name"
              className="Input--vTop"
              placeholder="My Store"
              required
              // maxLength="100"
              description="This will be used as your store website’s title and header"
              validator={storeNameValidator}
            />

            <Input
              autoRender
              name="slug"
              label="Link to Store"
              value={formData.slug}
              className="Input--vTop Input--stores-slug"
              addonValueBefore="http://stores.razorpay.com/"
              description={<div>{formData.slug ? formData.slug.length : 0} / 30</div>}
              validator={slugValidator}
            />

            <div className="label-link-section">
              <Label text="Store Logo" />
              <Link to="/config" className="btn btn-link">
                Upload your brand’s logo here <i className="i-external-link" />
              </Link>
            </div>

            <div className="label-link-section">
              <Label text="Store theme color" />
              <Link to="/config" className="btn btn-link">
                Choose your brand’s theme color here <i className="i-external-link" />
              </Link>
              <Description text="The default theme color will be used if none is specified" />
            </div>

            <Input
              autoRender
              name="shipping_fees"
              value={formData.shipping_fees}
              label="Shipping fee"
              className="Input--vTop"
              placeholder="0.00"
              addonBefore="₹"
              type="number"
              description="Fee will be applied to final bill amount during checkout"
              validator={(val) => {
                if (val && !isAmount(val)) {
                  const decimal = val && val.split('.');

                  if (decimal.length == 2 && decimal[1].length > 2) {
                    return 'The shipping fees can contain up to 2 decimals';
                  }
                }
                if (val && Number(val) < 1) {
                  return 'The shipping fees must be at least 1 Re.';
                }
                if (val && Number(val) > 10000) {
                  return 'The shipping fees must be less than Rs 10,000.';
                }
                return '';
              }}
            />
            <Input
              autoRender
              name="shipping_days"
              value={formData.shipping_days}
              label="Ships in"
              className="Input--vTop"
              placeholder="0"
              addonAfter="days"
              type="number"
              // maxLength="100"
              description="If this is specified, it will be shown to customer"
              validator={(val) => {
                if (val && val.indexOf('.') > -1) {
                  return 'The shipping days cannot contain decimals.';
                }
                if (val && (Number(val) < 1 || Number(val) > 90)) {
                  return 'The shipping days must be between 1 and 90.';
                }
                return '';
              }}
            />
            {/* <div className="label-link-section">
              <Label text="Facebook Pixel & Google Analytics" />
              <div>Add Facebook Pixel / GA tracking ID to track your page metrics</div>
              <Button.Transparent
                type="button"
                className="Button--Link"
                onClick={openConfigurePluginsView}
              >
                <b>{isPluginConfigured ? 'Update' : 'Configure'}</b>
              </Button.Transparent>
            </div> */}
            <div className="label-link-section">
              <AsyncBtn
                type="button"
                className="btn Button--invert Button--danger"
                pendingState="Deleting..."
                onClick={handleDeleteStore}
              >
                <i className="i i-delete-outline mr-5" />
                Delete my store
              </AsyncBtn>
            </div>
          </main>
          <footer>
            <Button type="button" onClick={props.onClose}>
              Cancel
            </Button>

            <AsyncBtn.Primary
              type="submit"
              pendingState="Saving..."
              onClick={onSubmit}
              disabled={disableSubmit}
            >
              Save changes
            </AsyncBtn.Primary>
          </footer>
        </Form>
      </Modal>
    </ModalMask>
  );
};

const mapStateToProps = (state) => ({
  entity: state.storefront.entity,
});

const mapDispatchToProps = (dispatch) => ({
  showNotification: (data) => dispatch(showNotification(data)),
  deleteStore: (data) => dispatch(deleteStore(data)),
  updateStore: (data) => dispatch(updateStore(data)),
});

export default connect(mapStateToProps, mapDispatchToProps)(StoresSettingsModal);
