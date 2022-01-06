import { withRouter } from 'react-router-dom';
import React from 'react';
import RTracking from 'react-tracking';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { StoreProductsStatusLabel } from 'merchant/components/StatusLabel';

import { showNotification } from 'merchant_common/reducers/notifications';
import { addToProductsList, updateProductsList } from 'merchant/reducers/storefront';

import { uploadProductImage, saveProduct, fetchProduct, patchProduct } from '../model';

import { rupeesToPaise, paiseToRupees } from '../../../../common/utils/rzp-utils';
import { validateAmount } from 'common/utils/validators';
import track from './track';

const FILE_SIZE_LIMIT = 2; // 2 MB
const FORM_NAME = 'ProductCreate-Form';
const WRAPPER_CLASS = 'Stores--ProductCreate';

@withRouter
@connect(
  (state) => ({
    store: state.storefront,
  }),
  {
    showNotification,
    addToProductsList,
    updateProductsList,
  },
)
@RTracking(() => window.rzpQ.component('StoresProductCreate'))
export default class ProductCreate extends React.Component {
  state = {
    isLoading: false,
    formData: {},
    images: [],
    isSubmitDisabled: false,
  };

  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentDidMount() {
    if (this.props.product_id) {
      // eslint-disable-next-line react/no-did-mount-set-state
      this.setState({ isLoading: true });

      fetchProduct(this.props.product_id)
        .then((response) => {
          const sellingPrice = paiseToRupees(response.data.selling_price);
          let discountedPrice = paiseToRupees(response.data.discounted_price);

          /* 
        backend sets discountedPrice same as sellingPrice if discountedPrice not entered,
        hence making discounted price empty if same
        */
          if (sellingPrice === discountedPrice) {
            discountedPrice = '';
          }

          const { images, name, description, stock, status } = response.data;

          this.setState({
            images,
            formData: {
              name,
              description,
              stock,
              sellingPrice,
              discountedPrice,
            },
            status,
          });
        })
        .finally(() => {
          this.setState({
            isLoading: false,
          });
        });
    }

    this.toggleDisableState();

    track.init({
      store_id: this.props.store.entity.data.id,
    });
    track.openModal();
  }

  componentDidUpdate() {
    this.toggleDisableState();
  }

  toggleDisableState = () => {
    // if value not selected, html marks it as ':invalid' which is tehnically valid in our case. Hence, relying on is-invalid.
    const invalidFields = document.querySelectorAll(`.${FORM_NAME} .Input.is-invalid`);
    let isSubmitDisabled = invalidFields.length;

    // disable save if product inactive
    if (this.state.status === 'inactive') {
      isSubmitDisabled = true;
    }

    if (this.state.isSubmitDisabled !== isSubmitDisabled) {
      this.setState({ isSubmitDisabled });
    }
  };

  handleFieldChange = (event) => {
    const key = event.target.name;
    const value = event.target.value;

    this.setState((prevState) => {
      return {
        formData: {
          ...prevState.formData,
          [key]: value,
        },
      };
    });
  };

  handleSubmit = () => {
    const { formData, images } = this.state;
    const { product_id } = this.props;

    track.createProduct();

    const payload = {
      name: formData.name,
      description: formData.description,
      images,
      stock: formData.stock,
      discounted_price: rupeesToPaise(formData.discountedPrice) || null,
      selling_price: rupeesToPaise(formData.sellingPrice),
    };

    return saveProduct(payload, product_id)
      .then((response) => {
        this.props.showNotification({
          type: 'success',
          message: 'Product successfully saved',
        });

        if (product_id) {
          this.props.updateProductsList(response.data);
        } else {
          this.props.addToProductsList(response.data);
        }

        this.props.onClose();
      })
      .catch((error) => {
        this.props.showNotification({
          type: 'error',
          message: error?.errors?.[0],
        });
      });
  };

  handleCancel = () => {
    // if modal view
    if (this.props.onClose) {
      track.cancelBtn();
      this.props.onClose();
    } else {
      this.props.history.push('/stores/products');
    }
  };

  handleImageInsert = () => {
    // Listen upload local image and save to server
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.click();

    input.onchange = () => {
      const file = input.files[0];
      const fileSizeMB = file.size / 1024 / 1024;

      if (fileSizeMB > FILE_SIZE_LIMIT) {
        this.props.showNotification({
          type: 'error',
          message: `Image too large. Max limit ${FILE_SIZE_LIMIT}MB`,
        });

        return;
      }

      const isImageType = /^image\//.test(file.type);

      if (isImageType) {
        this.props.showNotification({
          type: 'success',
          message: 'Uploading image...',
          closeTimeout: 2500,
        });

        uploadProductImage(file)
          .then((res) => {
            if (res && res.success) {
              const url = res.data[0];

              this.setState((prevState) => {
                const images = [...prevState.images];
                images.push(url);

                return {
                  images,
                };
              });
            } else {
              const errorMessage = 'Some network error occurred';

              throw new Error({ errors: [errorMessage] });
            }
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors[0],
            });
          });
      } else {
        const errorMessage = 'Select a valid image';

        self.props.showNotification({
          type: 'error',
          message: errorMessage,
        });
      }
    };

    track.addProductImage();
  };

  handleImageRemove = (index) => {
    this.setState((prevState) => {
      const images = [...prevState.images];
      images.splice(index, 1);

      return {
        images,
      };
    });
  };

  handleDeactivate = () => {
    this.context.confirm({
      header: 'Deactivate Product?',
      message: 'Are you sure you want to deactivate this product? This action is irreversible.',
      affirmativeLabel: 'Deactivate',
      affirmativePendingLabel: 'Deactivating',
      action: () => {
        return patchProduct(this.props.product_id, { status: 'inactive' }).then((response) => {
          this.setState({ status: 'inactive' });

          this.props.updateProductsList(response.data);
        });
      },
    });
  };

  handleProductName = (e) => {
    const value = e.target.value;
    track.productName(value);
  };

  handleSellingPrice = (e) => {
    const value = e.target.value;
    track.sellingPrice(value);
  };

  handleDiscountedPrice = (e) => {
    const value = e.target.value;
    track.discountedPrice(value);
  };

  handleQuantityAvailable = (e) => {
    const value = e.target.value;
    track.quantityAvailable(value);
  };

  handleProductDescription = (e) => {
    const value = e.target.value;
    track.productDescription(value);
  };

  render() {
    const { images, isSubmitDisabled, isLoading, formData, status } = this.state;
    const isEdit = !!this.props.product_id;
    const isModalView = this.props.onClose;

    const content = (
      <div>
        <div class="title">
          {isEdit ? 'Edit Product' : 'Adding a new Product '} <div class="title-underline" />
        </div>
        <Form name={FORM_NAME} class={FORM_NAME} onChange={this.handleFieldChange}>
          {isLoading ? (
            <div class="loader-container">
              <div class="spinner" />
            </div>
          ) : (
            <main>
              <Input
                autoRender
                name="name"
                label="Product Name"
                class="Input--vTop"
                placeholder="eg. Polo Tshirt XL"
                required
                maxLength="100"
                defaultValue={formData.name}
                onBlur={this.handleProductName}
              />
              {isEdit ? (
                <div class="status-field">
                  <StoreProductsStatusLabel status={status} />
                  {status === 'active' ? (
                    <>
                      <span class="m-r" />
                      <button
                        class="Button--Link Button--transparent Button"
                        onClick={this.handleDeactivate}
                      >
                        Deactivate
                      </button>
                    </>
                  ) : (
                    ''
                  )}
                </div>
              ) : (
                ''
              )}
              <div class="Input product-images">
                <div class="Input-label">
                  Product Images<small> (optional)</small>
                </div>
                <div class="info">Max size per image 2MB</div>
                <div class="Input-content">
                  {images.map((imageUrl, index) => (
                    <div key={imageUrl} class="image-container">
                      <img
                        src={imageUrl}
                        width="56px"
                        height="56px"
                        alt={`product image ${index}`}
                      />
                      <div class="remove-button" onClick={() => this.handleImageRemove(index)}>
                        <i class="i i-close" />
                      </div>
                    </div>
                  ))}
                  {images.length < 5 && (
                    <div class="add" onClick={this.handleImageInsert}>
                      <i class="i i-plus" />
                    </div>
                  )}
                </div>
              </div>
              <Input.Group class="InputGroup--inline InputGroup--vTop price" label="Selling Price">
                <div class="Input-content">
                  <div class="Input Input--Currency Input--noMargin">
                    <div class="value">₹</div>
                  </div>
                  <Input
                    autoRender
                    name="sellingPrice"
                    placeholder="0.00"
                    required
                    defaultValue={formData.sellingPrice}
                    validator={validateAmount}
                    onBlur={this.handleSellingPrice}
                  />
                </div>
              </Input.Group>
              <span style={{ width: '2%', display: 'inline-block' }} />
              <Input.Group
                class="InputGroup--inline InputGroup--vTop price"
                label={
                  <div>
                    Discounted Price <small>(opt.)</small>
                  </div>
                }
              >
                <div class="Input-content">
                  <div class="Input Input--Currency Input--noMargin">
                    <div class="value">₹</div>
                  </div>
                  <Input
                    autoRender
                    name="discountedPrice"
                    placeholder="0.00"
                    defaultValue={formData.discountedPrice}
                    validator={validateAmount}
                    onBlur={this.handleDiscountedPrice}
                  />
                </div>
              </Input.Group>
              <Input
                name="stock"
                label="Quantity Available"
                class="Input--vTop"
                placeholder="No. of units in Stock"
                defaultValue={formData.stock}
                type="number"
                onBlur={this.handleQuantityAvailable}
                validator={(val) => {
                  if (!val) {
                    return 'Quantity is a required field';
                  }
                  if (val && Number(val) % 1 !== 0) {
                    return 'Quantity cannot contain decimals';
                  }
                  return '';
                }}
              />
              <Input.Textarea
                autoRender
                name="description"
                size="largest"
                label={
                  <div>
                    Product Description <small>(optional)</small>
                  </div>
                }
                class="Input--vTop"
                maxLength="240"
                placeholder="1 to 2 line description of the product"
                defaultValue={formData.description}
                description={
                  <div class="text-right">{(formData.description || '').length} / 240</div>
                }
                onBlur={this.handleProductDescription}
              />
            </main>
          )}
          <footer>
            <Button type="button" onClick={this.handleCancel}>
              Cancel
            </Button>

            <AsyncBtn.Primary
              type="submit"
              pendingState="Saving..."
              onClick={this.handleSubmit}
              disabled={isSubmitDisabled}
            >
              Save
            </AsyncBtn.Primary>
          </footer>
        </Form>
      </div>
    );

    if (isModalView) {
      return (
        <div class={WRAPPER_CLASS}>
          <ModalMask maskClosable={false}>
            <Modal class={content && 'animate-down'} showCloseBtn={false}>
              <ModalContent>{content}</ModalContent>
            </Modal>
          </ModalMask>
        </div>
      );
    }

    return (
      <div class={WRAPPER_CLASS}>
        <div class="StandaloneContainer">{content}</div>;
      </div>
    );
  }
}
