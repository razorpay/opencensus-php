import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { connect } from 'react-redux';
import {
  Alert,
  ArrowUpRightIcon,
  Button,
  Heading,
  Link,
  TextArea,
  TextInput,
  IconButton,
  CloseIcon,
  TrashIcon,
  Text,
  Box,
} from '@razorpay/blade/components';
import CatalogStatusLabel from 'merchant/views/PaymentPages/common/Products/CatalogStatusLabel';
import ConfirmModal from 'merchant/views/PaymentPages/common/ConfirmModal';
import { IPaymentPagesProduct } from 'merchant/reducers/paymentPages/storefront';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { bindActionCreators } from 'redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  decimalFields,
  emptyProduct,
  emptyProductErrors,
  generateProductRequest,
  numberWith2Digits,
  numericFields,
  numericRegex,
  validateProduct,
} from './utils';
import {
  AddImageButton,
  ImageButton,
  ImagesContainer,
  ImageSelector,
  PricePreview,
  ProductDrawerWrapper,
  HeadingContainer,
  HeadingInfo,
  ScrollableContent,
  PriceInputField,
  TextInputContainer,
  PriceInfo,
  StyledItalics,
} from './styled';
import CategoryDropdown from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/CategoryDropdown';
import {
  createProductCatalog,
  editProductCatalog,
  uploadImageInDescription as uploadStorefrontImage,
  deleteProductCatalog,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { PRODUCT_MESSAGES } from './constants';
import { ICategories } from 'merchant/reducers/paymentPages/types';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

interface IProductDrawer {
  storeFrontId: string | undefined;
  isCreate: boolean;
  screenSource: 'listing_view' | 'store_view';
  handleClose: () => void;
  productData: IPaymentPagesProduct | null;
  showNotification: (data: any) => void;
  onSuccess: (response?: any) => void;
  onDeleteSuccess: (id?: string) => void;
  drawerPosition: 'left' | 'right';
  hasTransparentBackground: boolean;
  showCentralCatalogueInfo: boolean;
  showDeleteCTA: boolean;
  showProductStatus: boolean;
  openModal: (data: any) => void;
  closeModal: () => void;
  onCategoryAddSuccess: (category) => void;
  categories: ICategories;
  top?: string;
}

const FILE_SIZE_LIMIT = 2; // 2 MB

const ProductDrawer = ({
  isCreate,
  storeFrontId,
  screenSource,
  handleClose,
  productData,
  showNotification,
  onSuccess,
  onDeleteSuccess,
  drawerPosition = 'left',
  hasTransparentBackground = false,
  showCentralCatalogueInfo = true,
  showDeleteCTA = false,
  showProductStatus = false,
  openModal,
  closeModal,
  onCategoryAddSuccess,
  categories,
  top,
}: IProductDrawer): React.ReactElement => {
  const [product, setProduct] = useState<IPaymentPagesProduct>(
    productData ? productData : { ...emptyProduct, id: String(Date.now()) },
  );

  const [errors, setErrors] = useState({ ...emptyProductErrors });
  const isEdit = !!productData;
  const [isLoading, setLoading] = useState(false);
  // const [progress, setProgress] = useState(0);
  const navigate = useNavigate();

  const handleProductChange = (e) => {
    setProduct((prevState) => {
      if (decimalFields.indexOf(e.name) > -1) {
        // validate if text contains only numbers
        if (!numberWith2Digits.test(e.value)) {
          return prevState;
        }
        const priceNumber = e.value.split('.')[0];
        if (priceNumber.length > 9) return prevState;
      }
      if (numericFields.indexOf(e.name) > -1) {
        // max char length for stock units is 10
        if (!numericRegex.test(e.value) || e.value.length > 10) {
          return prevState;
        }
      }
      return {
        ...prevState,
        [e.name]: e.value,
      };
    });
    setErrors((prevErrors) => {
      const newErrors = {
        ...prevErrors,
        [e.name]: '',
      };
      // reset discount error when amount input also changes
      if (e.name === 'amount') {
        newErrors.discounted_amount = '';
      }
      return newErrors;
    });
  };

  const onUploadProgress = () => {};
  // const onUploadProgress = (progressEvent) => {
  // setProgress(Math.round((100 * progressEvent.loaded) / progressEvent.total));
  // };

  const handleImageRemove = (index: number) => {
    const newImages = [...product.images];
    newImages.splice(index, 1);
    handleProductChange({ name: 'images', value: newImages });
  };

  const handleDeleteProduct = () => {
    openModal({
      isNew: true,
      component: (
        <ConfirmModal
          onAbort={closeModal}
          onAffirm={() => {
            closeModal();

            deleteProductCatalog(product.id)
              .then(() => {
                showNotification({
                  type: 'success',
                  message: PRODUCT_MESSAGES.DELETE,
                  closeTimeout: 2500,
                });

                if (onDeleteSuccess) onDeleteSuccess(product.id);
              })
              .catch(() => {
                showNotification({
                  type: 'error',
                  message: 'Failed to delete the product. Please try again later.',
                });
              });
          }}
          header={
            <>
              <TrashIcon
                size="large"
                color="feedback.negative.action.icon.primary.default.lowContrast"
              />{' '}
              Delete product
            </>
          }
          message={
            <>
              This product will be deleted from all associated payment pages. You will not be able
              to undo this. Are you sure?
              <br />
              <br />
            </>
          }
          affirmativeLabel="Delete product"
        />
      ),
    });
  };

  const onImageUpload = () => {
    // Use this block for testing in staging, as image upload API is broken
    // const newImages = [...product.images];
    // newImages.push({
    //   original:
    //     'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/bad%20error_l1vvcznukgqcho.jpeg',
    // });
    // handleProductChange({ name: 'images', value: newImages });
    // return;
    // TODO: comment out above block in production.

    // Listen upload local image and save to server
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.click();

    input.onchange = () => {
      const file = input.files && input.files[0];
      if (!file) {
        return;
      }
      const fileSizeMB = file.size / 1024 / 1024;

      if (fileSizeMB > FILE_SIZE_LIMIT) {
        showNotification({
          type: 'error',
          message: `Image too large. Max limit ${FILE_SIZE_LIMIT}MB`,
        });

        return;
      }

      const isImageType = /^image\//.test(file.type);

      if (!isImageType) {
        const errorMessage = 'Select a valid image';

        showNotification({
          type: 'error',
          message: errorMessage,
        });
        return;
      }

      showNotification({
        type: 'success',
        message: 'Uploading image...',
        closeTimeout: 2500,
      });

      uploadStorefrontImage(file, onUploadProgress)
        .then((res) => {
          if (res && res.success) {
            const url = res.data[0];
            const newImages = [...product.images];
            newImages.push({
              original: url,
            });
            handleProductChange({ name: 'images', value: newImages });
          } else {
            const errorMessage = 'Some network error occurred';
            showNotification({
              type: 'error',
              message: errorMessage,
            });
          }
        })
        .catch(({ errors }) => {
          showNotification({
            type: 'error',
            message: errors[0],
          });
        });
    };
  };

  const onSubmit = (): void => {
    // validate
    const { isValid, errors: newErrors } = validateProduct(product);

    if (!isValid) {
      setErrors(newErrors);
      return;
    }
    setLoading(true);
    // convert numeric fields to number before API call
    // call API & then update redux
    if (isEdit) {
      analyticsTrack({
        objectName: 'Save product details',
        actionName: 'Clicked',
        screen: 'Edit product',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
          storeFrontId,
          productId: productData?.id ?? undefined,
          isNewStorefront: Boolean(isCreate),
          screenSource,
        },
      });
      editProductCatalog(productData?.id, generateProductRequest(product))
        .then((res) => {
          if (res.success) {
            showNotification({
              type: 'success',
              message: PRODUCT_MESSAGES.UPDATE,
              closeTimeout: 2500,
            });

            onSuccess(res.data);
          }
        })
        .catch((res) => {
          showNotification({
            type: 'error',
            message: res.errors[0] ? res.errors[0] : 'Something went wrong',
          });
        })
        .finally(() => setLoading(false));
    } else {
      analyticsTrack({
        objectName: 'Add product',
        actionName: 'Clicked',
        screen: 'Add New Product',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
          storeFrontId,
          isNewStorefront: Boolean(isCreate),
          screenSource,
        },
      });
      createProductCatalog(generateProductRequest(product))
        .then((res) => {
          if (res.success) {
            showNotification({
              type: 'success',
              message: PRODUCT_MESSAGES.ADD,
              closeTimeout: 2500,
            });

            onSuccess(res.data);
          }
        })
        .catch((res) => {
          showNotification({
            type: 'error',
            message: res.errors[0] ? res.errors[0] : 'Something went wrong',
          });
        })
        .finally(() => setLoading(false));
    }
  };

  const { product_name, amount, discounted_amount, units, images, category, description, status } =
    product;
  const footerButtons = [
    <Button size="medium" type="button" variant="secondary" key="cancel" onClick={handleClose}>
      Cancel
    </Button>,
    <Button
      size="medium"
      type="button"
      variant="primary"
      key="submit"
      onClick={onSubmit}
      isLoading={isLoading}
      isDisabled={Boolean(!product_name || !amount)}
    >
      {isEdit ? 'Save product details' : 'Add product'}
    </Button>,
  ];

  return (
    <ProductDrawerWrapper
      maskClosable={false}
      onClose={handleClose}
      footerButtons={footerButtons}
      position={drawerPosition}
      hasTransparentBackground={hasTransparentBackground}
      top={top}
    >
      <HeadingContainer>
        <Heading size="large" contrast="low" variant="regular" weight="bold">
          {isEdit ? 'Edit product' : 'Add new product'}
        </Heading>
        {isEdit && (
          <HeadingInfo>
            {showProductStatus && <CatalogStatusLabel status={status} />}
            {showDeleteCTA && (
              <IconButton
                icon={TrashIcon}
                accessibilityLabel="Delete"
                onClick={() => handleDeleteProduct()}
                size="large"
              />
            )}
          </HeadingInfo>
        )}
      </HeadingContainer>
      <ScrollableContent>
        <TextInput
          label="Product name"
          labelPosition="top"
          maxCharacters={100}
          name="product_name"
          necessityIndicator="required"
          onChange={handleProductChange}
          value={product_name}
          placeholder="Add product name"
          showClearButton={false}
          validationState={errors.product_name ? 'error' : 'none'}
          errorText={errors.product_name}
        />
        <PriceInputField>
          <TextInputContainer>
            <TextInput
              label="Price"
              type="number"
              labelPosition="top"
              name="amount"
              prefix="₹"
              necessityIndicator="required"
              onChange={handleProductChange}
              value={amount}
              placeholder="0.00"
              showClearButton={false}
              validationState={errors.amount || errors.discounted_amount ? 'error' : 'none'}
            />
          </TextInputContainer>

          <TextInputContainer>
            <TextInput
              label="Discounted price"
              type="number"
              labelPosition="top"
              name="discounted_amount"
              prefix="₹"
              necessityIndicator="optional"
              onChange={handleProductChange}
              value={discounted_amount}
              placeholder="0.00"
              showClearButton={false}
              validationState={errors.discounted_amount ? 'error' : 'none'}
            />
          </TextInputContainer>

          {!errors.amount && !errors.discounted_amount && (
            <PriceInfo>
              <PricePreview amount={amount} discounted_amount={discounted_amount} />
            </PriceInfo>
          )}
          {errors.amount || errors.discounted_amount ? (
            <PriceInfo>
              <Text
                position="absolute"
                bottom="-24px"
                color="feedback.negative.action.text.primary.active.lowContrast"
                size="small"
              >
                {errors.discounted_amount ?? errors.amount}
              </Text>
            </PriceInfo>
          ) : null}
        </PriceInputField>
        <Box marginTop={'spacing.5'}>
          <TextInput
            label="Quantity in stock"
            labelPosition="top"
            name="units"
            necessityIndicator="optional"
            onChange={handleProductChange}
            value={units}
            placeholder="No. of pieces or units"
            showClearButton={false}
            validationState={errors.units ? 'error' : 'none'}
            errorText={errors.units}
          />
        </Box>
        {images.length === 0 ? (
          <Box marginTop={'spacing.5'}>
            <Text
              weight="bold"
              size="small"
              color="surface.text.subdued.lowContrast"
              marginBottom={'spacing.3'}
            >
              Upload images <StyledItalics style={{}}>(optional)</StyledItalics>
            </Text>
            <ImageSelector onClick={onImageUpload} />
          </Box>
        ) : (
          <Box marginTop={'spacing.5'}>
            <Text
              weight="bold"
              size="small"
              color="surface.text.subdued.lowContrast"
              marginBottom={'spacing.4'}
            >
              Uploaded images
            </Text>
            <ImagesContainer>
              {images.map((item, i) => (
                <ImageButton key={item.original}>
                  <img src={item.original} alt={`image-${i + 1}`} key={i} width={56} height={56} />
                  <IconButton
                    icon={CloseIcon}
                    accessibilityLabel="Close"
                    onClick={() => handleImageRemove(i)}
                  />
                </ImageButton>
              ))}
              {images.length < 5 && <AddImageButton onClick={onImageUpload} />}
            </ImagesContainer>
          </Box>
        )}
        <CategoryDropdown
          categories={categories}
          onChange={handleProductChange}
          value={category}
          name="category"
          hasTransparentBackground={hasTransparentBackground}
          drawerPosition={drawerPosition}
          onCategoryAddSuccess={onCategoryAddSuccess}
          storeFrontId={storeFrontId}
          isCreate={isCreate}
          screenSource={screenSource}
        />
        <TextArea
          label="Description"
          labelPosition="top"
          name="description"
          value={description}
          necessityIndicator="optional"
          onChange={handleProductChange}
          placeholder="Enter Description"
          validationState={errors.description ? 'error' : 'none'}
          errorText={errors.description}
          maxCharacters={1200}
        />
        {showCentralCatalogueInfo ? (
          <Alert
            contrast="low"
            description={
              <>
                This product will be saved to the{' '}
                <Link
                  onClick={() => {
                    navigate(`/paymentpages/products`);
                  }}
                  variant="button"
                  icon={ArrowUpRightIcon}
                  iconPosition="right"
                  size="small"
                >
                  central catalog
                </Link>
                <br /> After this is saved, you can add it to any page
              </>
            }
            intent="information"
            isDismissible={false}
          />
        ) : (
          isEdit && (
            <Alert
              contrast="low"
              description="Changes will be saved across all payment pages that use this product"
              intent="notice"
              isDismissible={false}
            />
          )
        )}
      </ScrollableContent>
    </ProductDrawerWrapper>
  );
};

const mapStateToProps = () => ({
  // storefront: state.paymentPageStorefront,
  // isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ showNotification, closeModal, openModal }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(ProductDrawer);
