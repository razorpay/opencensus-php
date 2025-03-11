/* eslint-disable @typescript-eslint/no-use-before-define */
import React, { useEffect, useState, useRef, useCallback } from 'react';
import { connect } from 'react-redux';
import { Link as ReactRouterLink, useNavigate } from 'react-router-dom';
import lazy from 'merchant/routes/LazyLoader';
import { IBannerImage } from 'merchant/reducers/paymentPages/types';
import { withRouter } from 'common/deprecated/withRouter';
import {
  AddProductBox,
  DescriptionLeftWrapper,
  DescriptionWrapper,
  LeftContentWrapper,
  Iframe,
  PreviewButtons,
  StorefrontLeftWrapper,
  StorefrontRightWrapper,
  StoreFrontWrapper,
  StorefrontHeader,
  StickyFooter,
} from './styled';
import ProductDrawer from 'merchant/views/PaymentPages/common/Products/ProductDrawer';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, getURLQueryParams } from 'common/utils/rzp-utils';
import {
  fetchCategories,
  editStorefront,
  editStorefrontDeepMerge,
  IPaymentPagesProduct,
  removeProduct,
  previewDevice,
  resetStorefront,
  fetchStorefront,
  addProduct,
  editProduct,
  addCategory,
} from 'merchant/reducers/paymentPages/storefront';
import { fetchSupportDetail } from 'merchant/reducers/support_detail';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import debounce from 'common/utils/debounce';
import { bindActionCreators } from 'redux';
import SampleProducts from './SampleProducts';
import ProductSection from './ProductSection';
import StorefrontPageTitle from './PageTitle';
import PageSettings from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Settings/StorefrontSettings';
import ReceiptSettings from 'merchant/views/PaymentPages/PaymentPages/components/Modals/StorefrontPaymentReceipt';
import {
  Link,
  Text,
  Button as BladeButton,
  PlusCircleIcon,
  Heading,
  Box,
  ExternalLinkIcon,
  SettingsIcon,
} from '@razorpay/blade/components';
import {
  emitIframeEvent,
  onStorefrontChange,
  onStorefrontDetailsChange,
  onStorefrontProductChange,
  getAllowedStorefrontDomain,
} from './iframe';
import { AlertState, IHostedPagesMerchant, IStorefrontProps, TripleState } from './types';
import {
  convertToHostedPagesProduct,
  getStorefrontHostedPagesFormat,
  sampleProduct,
} from './utils';
import ConfirmModal from 'merchant/views/PaymentPages/common/ConfirmModal';
import ContactDetails from './ContactDetails';
import {
  generateStorefrontRequest,
  validateStorefront,
} from 'merchant/views/PaymentPages/common/Products/utils';
import {
  createStorefront,
  editStorefront as editStorefrontAPI,
  fetchProductCatalogs,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { ProductsSkeleton } from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/styled';
import SelectProductDrawer from './SelectProductDrawer';
import MobileActionButtons from './MobileActionButtons';
import { PRODUCT_MESSAGES } from 'merchant/views/PaymentPages/common/Products/constants';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';
import ChromeSearchBar from './ChromeSearchBar';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const AddBuisnessDetails = lazy(
  () => import(/* webpackChunkName: 'AddBuisnessDetails' */ './AddBuisnessDetails'),
);

const AddBannerDetails = lazy(
  () => import(/* webpackChunkName: 'AddBannerDetails' */ './AddBannerDetails'),
);

const AddSocialMediaDetails = lazy(
  () => import(/* webpackChunkName: 'AddSocialMediaDetails' */ './AddSocialMediaDetails'),
);

const allowedIframeDomain: string = getAllowedStorefrontDomain();

const StoreFront = ({
  id,
  history,
  location,
  storefront,
  editStorefront,
  editStorefrontDeepMerge,
  user,
  config,
  org,
  globalSupportDetails,
  fetchSupportDetail,
  removeProduct,
  openModal,
  closeModal,
  fetchCategories,
  showNotification,
  previewDevice,
  mode,
  resetStorefront,
  fetchStorefront,
  addProduct,
  editProduct,
  addCategory,
  isMobile,
}: IStorefrontProps): JSX.Element => {
  // Used to manage products skeleton & determining if 0 products exist in central catalog or more (used to determine which drawer opens on button click)
  // we use -1 is loading state, 0 is no products added in central catalog, 1 is products previously exist in catalog
  const [isInitialLoaded, setIsInitialLoaded] = useState<TripleState>(-1);
  // Used to manage the open state of Add product drawer & select product drawer
  // we use -1 for both drawers are closed, 0 for add product drawer is open, 1 for select products drawer is open
  // using one variable as we aren't allowing stacking multiple drawers behavior, so reduced the dependency to 1 variable
  const [isProductModal, setIsProductModal] = useState<TripleState>(-1);
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const [productData, setProductData] = useState<IPaymentPagesProduct | null>(null);
  const isCreate = !id;
  const [deviceHeight, setDeviceHeight] = useState<any>('100%');
  const [isSampleProduct, setIsSampleProduct] = useState(isCreate);
  const isIframeLoadedRef = useRef(false);
  const [isMobilePreview, setIsMobilePreview] = useState(false);
  const [isPageSettingsOpen, setIsPageSettingsOpen] = useState(false);
  const [isReceiptSettingsOpen, setIsReceiptSettingsOpen] = useState(false);
  const [openBuisnessDetailsDrawer, setOpenBuisnessDetailsDrawer] = useState(false);
  const [openAddBannerDrawer, setOpenAddBannerDrawer] = useState(false);
  const [openSocialMediaDrawer, setOpenSocialMediaDrawer] = useState(false);
  const [showAlert, setShowAlert] = useState<AlertState>({
    showBannerAlert: false,
    showSocialHandleAlert: false,
  });
  const navigate = useNavigate();
  const splitzConfig = useSplitzService();
  const { abExperiments } = splitzConfig;

  const isStorefrontV1Enabled = isExperimentEnabled(abExperiments?.['storefront_v1']);
  const isSocialHandlesEnabled = isExperimentEnabled(abExperiments?.['storefront_social_handle']);

  const setIsIframeLoadedRef = (data: boolean) => {
    isIframeLoadedRef.current = data;
  };

  const {
    entity: { products },
  } = storefront;

  const handleBuisnessDetailsClick = (val: boolean) => {
    setOpenBuisnessDetailsDrawer(val);
    track.addBuisnessDetailsArrowClicked({
      storefrontId: id ?? undefined,
      isNewStoreFront: Boolean(isCreate),
    });
  };

  const handleAddBannerClick = (val: boolean) => {
    setOpenAddBannerDrawer(val);
    track.addStoreBannerArrowClicked({
      storefrontId: id ?? undefined,
      isNewStoreFront: Boolean(isCreate),
    });
  };

  const handleAddSocialMediaClick = (val: boolean) => {
    setOpenSocialMediaDrawer(val);
  };

  const getMerchantDetails = useCallback(
    function getMerchantDetail() {
      const merchantData: Partial<IHostedPagesMerchant> = {
        name: user.billing_label || user.name,
        brand_color: config.brand_color || org.merchant_styles?.checkout_theme_color,
        image: user.logo_url,
      };
      merchantData.support_details = {
        support_email: globalSupportDetails.email,
        support_mobile: globalSupportDetails.phone,
      };
      return merchantData;
    },
    [user, org.merchant_styles, globalSupportDetails, config.brand_color],
  );

  const handleIframeEvents = useCallback(
    function iframeEvents(e: MessageEvent): void {
      // TODO: use env variables
      if (e.origin !== allowedIframeDomain) {
        return;
      }
      if (e?.data?.event_type) {
        const merchantData = getMerchantDetails();
        const livePreviewResponse = getStorefrontHostedPagesFormat(
          merchantData,
          isStorefrontV1Enabled,
          storefront.entity.title,
          isCreate
            ? convertToHostedPagesProduct(
                isIframeLoadedRef
                  ? storefront.entity.products.length > 0
                    ? storefront.entity.products
                    : [sampleProduct]
                  : isSampleProduct
                  ? [sampleProduct]
                  : [],
                storefront.allCategories.data,
              )
            : convertToHostedPagesProduct(
                storefront.entity.products,
                storefront.allCategories.data,
              ),
        );
        // we send sampleProduct data to iframe without updating redux (to avoid the need to check for sample product everywhere)
        // handling the remove sample products flow using isSampleProduct flag

        // TODO: Uncomment after implementing allowedDomain
        if (e.origin !== allowedIframeDomain) {
          return;
        }
        const iframeData = e.data;
        // using event_type instead of type, as many libraries use type (e.g. webpack)
        switch (iframeData.event_type) {
          case 'live_preview_loaded':
            setIsIframeLoadedRef(true);
            emitIframeEvent(iframeRef, onStorefrontChange(livePreviewResponse));
            break;
          default:
            break;
        }
      }
    },
    [getMerchantDetails, isCreate, isIframeLoadedRef, storefront, isSampleProduct],
  );

  useEffect(() => {
    // shift the chat widget to avoid overlap with the sticky footer on mobile
    const chatWidgetBottomSpacing = document.documentElement.style.getPropertyValue(
      '--support-padding-bottom',
    );
    document.documentElement.style.setProperty('--support-padding-bottom', '60px');

    const debouncedResizeFunction = debounce(() => calculateLivePreviewDimensions(), 100);
    window.addEventListener('resize', debouncedResizeFunction);
    calculateLivePreviewDimensions();
    fetchInitialData();

    return () => {
      document.documentElement.style.setProperty(
        '--support-padding-bottom',
        chatWidgetBottomSpacing,
      );
      window.removeEventListener('resize', debouncedResizeFunction);
      resetStorefront();
    };
  }, []);

  useEffect(() => {
    window.addEventListener('message', handleIframeEvents);

    return () => {
      window.removeEventListener('message', handleIframeEvents);
    };
  }, [handleIframeEvents]);
  // TODO: try adding dependencies event listner here, to resolve state/redux inconsistency issue

  useEffect(() => {
    const formattedProducts = convertToHostedPagesProduct(
      storefront.entity.products.length > 0
        ? storefront.entity.products
        : isSampleProduct
        ? [sampleProduct]
        : [],
      storefront.allCategories.data,
    );
    if (isIframeLoadedRef) {
      emitIframeEvent(iframeRef, onStorefrontProductChange(formattedProducts));
    }
  }, [storefront.entity.products, isSampleProduct, storefront.allCategories.data]);

  const getCroppedSrc = (bannerImages: IBannerImage[]) => {
    return bannerImages
      .sort((a, b) => a.position - b.position)
      .filter((image) => image.enabled)
      .map((image) => ({
        id: image.id || image.position.toString(),
        url: image.cropped,
      }));
  };

  useEffect(() => {
    if (isIframeLoadedRef) {
      const banner_images = getCroppedSrc(storefront.entity.banner_images);
      emitIframeEvent(
        iframeRef,
        onStorefrontDetailsChange({
          merchant: {
            support_details: {
              support_email: storefront.entity.contactEmail,
              support_mobile: storefront.entity.contactPhone,
            },
          },
          store: {
            title: storefront.entity.title,
            banner_images,
            terms: storefront.entity?.terms,
            settings: {
              base_config: {
                banner_feature_enabled:
                  storefront.entity.settings?.base_config?.banner_feature_enabled,
              },
            },
          },
        }),
      );
    }
  }, [
    storefront.entity.title,
    storefront.entity.contactEmail,
    storefront.entity.contactPhone,
    storefront.entity.banner_images,
    storefront.entity.settings?.base_config?.banner_feature_enabled,
    storefront.entity?.terms,
  ]);

  function getSupportDetails(): Promise<{
    contactPhone: string;
    contactEmail: string;
  }> {
    if (Object.keys(globalSupportDetails).length) {
      // if details already exist, then resolve promise directly
      return new Promise((res) =>
        res({
          contactPhone: globalSupportDetails.phone,
          contactEmail: globalSupportDetails.email,
        }),
      );
    }
    return fetchSupportDetail().then((res) => {
      return {
        contactPhone: res.data.phone,
        contactEmail: res.data.email,
      };
    });
  }

  function fetchInitialData() {
    setIsInitialLoaded(-1);
    // avoid updating the allProducts in redux, as we want to use that primarily in select drawers
    // To identify if data is fetched previously or not
    const { search } = location;
    const queryParams = getURLQueryParams(search);
    // edit & duplicate flow
    if (!isCreate || queryParams['duplicate_id']) {
      const idToFetch = queryParams['duplicate_id'] ? queryParams['duplicate_id'] : id;
      if (idToFetch) {
        const promises = [fetchStorefront(idToFetch), fetchCategories()];
        Promise.all(promises).then(() => {
          setIsInitialLoaded(1);

          if (queryParams['modal'] === 'page') {
            setIsPageSettingsOpen(true);
          } else if (queryParams['modal'] === 'receipt') {
            setIsReceiptSettingsOpen(true);
          }
        });
      }
    } else {
      // create flow
      const promises = [getSupportDetails(), fetchProductCatalogs(10, false), fetchCategories()];
      Promise.allSettled(promises).then(([res1, res2]) => {
        if (res1.status === 'fulfilled') {
          editStorefront('contactPhone', res1.value.contactPhone);
          editStorefront('contactEmail', res1.value.contactEmail);
        }
        // we use -1 is loading state, 0 is no products added in central catalog, 1 is products added in catalog
        if (res2.status === 'fulfilled') {
          setIsInitialLoaded(res2.value.data.items.length >= 1 ? 1 : 0);
        } else {
          showNotification({
            type: 'error',
            message: 'Something went wrong',
          });
          setIsInitialLoaded(0);
        }
      });
    }
  }

  function calculateLivePreviewDimensions() {
    const bodyHeight = document.body.clientHeight;
    const bodyWidth = document.body.clientWidth;

    if (bodyWidth <= 1200) {
      // automatically set device type to mobile as we cannot show desktop preview without issues
      previewDevice(false);
    }
    setDeviceHeight(bodyHeight - 200);
  }

  const setProductModal = (val: TripleState) => {
    if (val === -1) {
      // reset productData while closing modal
      setProductData(null);
    }
    setIsProductModal(val);
  };

  const removeSampleProduct = () => {
    setIsSampleProduct(false);

    showNotification({
      type: 'success',
      message: PRODUCT_MESSAGES.REMOVE,
    });
  };

  const onAddProductClick = () => {
    // if products exist in catalog, open select drawer, else open add product drawer
    setProductModal(isInitialLoaded === 1 ? 1 : 0);

    // Tracking only when it's first product
    if (isInitialLoaded === 0) {
      track.addFirstProductToStorefront({
        storefrontId: id ?? undefined,
        isNewStoreFront: Boolean(isCreate),
      });
    }
  };

  const openEditProductDrawer = (id: string) => {
    const editingProduct = storefront.entity.products.find((item) => item.id === id);
    if (editingProduct) {
      setProductData(editingProduct);
      setProductModal(0);
    }
  };

  const onRemoveProduct = (id) => {
    removeProduct(id);

    showNotification({
      type: 'success',
      message: PRODUCT_MESSAGES.REMOVE,
    });
  };

  const handleClose = () => {
    openModal({
      isNew: true,
      component: (
        <ConfirmModal
          onAbort={closeModal}
          onAffirm={() => {
            history.push('/paymentpages');
            closeModal();
          }}
          header="Go back to Dashboard"
        />
      ),
    });
  };

  const handlePageSettingsSave = (formData) => {
    const payload: {
      expire_by?: number | null;
      settings: {
        payment_success_message?: string;
        payment_success_redirect_url?: string;
      };
      slug?: string | null;
    } = {
      expire_by: null,
      settings: {
        payment_success_message: '',
        payment_success_redirect_url: '',
      },
      slug: null,
    };

    if (formData.payment_success_message) {
      payload.settings.payment_success_message = formData.payment_success_message;
    }

    if (formData.payment_success_redirect_url) {
      payload.settings.payment_success_redirect_url = formData.payment_success_redirect_url;
    }

    if (formData.expire_by) {
      payload.expire_by = Math.round(formData.expire_by / 1000);
    }

    if (formData.slug) {
      payload.slug = formData.slug;
    }

    editStorefrontDeepMerge(payload);
  };

  const handlePluginsAndAddOnsSave = (formData) => {
    const payload = {
      settings: formData,
    };

    editStorefrontDeepMerge(payload);
  };

  const handleReceiptSettingsSave = (formData) => {
    const payload = {
      settings: {
        enable_custom_serial_number: formData.enable_custom_serial_number,
      },
    };

    editStorefrontDeepMerge(payload);
  };

  const openBrandColorSettingsPage = (item: string) => {
    track.customizeStorefrontClicked({
      storefrontId: id ?? undefined,
      isNewStoreFront: Boolean(isCreate),
      customization_items: item,
    });
    window.open('/app/checkout-settings/branding', '_blank');
  };

  const onProductAddSuccess = (savedProductData) => {
    const editingProduct = storefront.entity.products.find(
      (item) => item.id === savedProductData.id,
    );

    if (editingProduct) {
      editProduct({ catalog: savedProductData });
    } else {
      addProduct({ catalog: savedProductData });
    }

    // ensure that subsequent button clicks on add product button open the Select Product drawer
    setIsInitialLoaded(1);
    // close modal
    setProductModal(-1);
  };

  const handleCategoryAddSuccess = (category) => {
    addCategory(category);
    analyticsTrack({
      objectName: 'Add New Category',
      actionName: 'Clicked on Add Category',
      screen: 'Add New Product',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        screen_source: 'store_view',
      },
    });
  };

  const checkForBannerAlert = () => {
    let isBannerEnabled = storefront.entity.settings?.base_config?.banner_feature_enabled;
    if (isBannerEnabled) {
      return storefront?.entity?.banner_images?.length === 0;
    } else {
      return false;
    }
  };

  const checkForSocialHanldesAlert = () => {
    let isSocialHandlesEnabled = storefront.entity.settings?.base_config?.social_handles_enabled;
    if (isSocialHandlesEnabled) {
      return storefront?.entity?.social_handles?.length === 0;
    } else {
      return false;
    }
  };

  const onSubmit = (): any => {
    // adding before validity check, so as to create proper funnel for events
    track.publishPageClicked({
      storefrontId: id ?? undefined,
      isNewStorefront: Boolean(isCreate),
    });

    //Check for banner
    if (isStorefrontV1Enabled && checkForBannerAlert()) {
      setShowAlert((prev) => ({
        ...prev,
        showBannerAlert: true,
      }));
      return;
    }

    if (isSocialHandlesEnabled && checkForSocialHanldesAlert()) {
      setShowAlert((prev) => ({
        ...prev,
        showSocialHandleAlert: true,
      }));
      return;
    }
    // validate
    const { isValid, error } = validateStorefront(storefront);

    if (!isValid) {
      showNotification({
        type: 'error',
        message: error,
      });
      return;
    }

    // generate request & fire api calls
    if (isCreate) {
      // eslint-disable-next-line consistent-return
      return createStorefront(generateStorefrontRequest(storefront, mode))
        .then((res) => {
          if (res.success) {
            showNotification({
              type: 'success',
              message: 'Storefront created successfully',
              closeTimeout: 2500,
            });
            track.publishPageLoadedSucessfully({
              storefrontId: res?.data?.id,
              isNewStorefront: true,
            });

            // history.push(`/paymentpages/storefront/${res.data.id}/success`);
            navigate(`/paymentpages/storefront/${res.data.id}/success`, {
              state: {
                isCreate,
              },
            });
          }
        })
        .catch((res) => {
          showNotification({
            type: 'error',
            message: res.errors[0] ? res.errors[0] : 'Something went wrong',
          });
        });
    }
    // eslint-disable-next-line consistent-return
    return editStorefrontAPI(id, generateStorefrontRequest(storefront, mode))
      .then((res) => {
        if (res.success) {
          showNotification({
            type: 'success',
            message: 'Storefront updated successfully',
            closeTimeout: 2500,
          });
          track.publishPageLoadedSucessfully({
            storefrontId: res?.data?.id,
            isNewStorefront: true,
          });
          // history.push(`/paymentpages/storefront/${res.data.id}/success`);
          navigate(`/paymentpages/storefront/${res.data.id}/success`, {
            state: {
              isCreate,
            },
          });
        }
      })
      .catch((res) => {
        showNotification({
          type: 'error',
          message: res.errors[0] ? res.errors[0] : 'Something went wrong',
        });
      });
  };

  let pageTitle: string | React.ReactElement = 'Create a new storefront page';

  if (id) {
    pageTitle = (
      <>
        Edit storefront <span> - {id}</span>
      </>
    );
  }
  const actionBtns = (
    <React.Fragment>
      {/* <Button.Transparent
        type="button"
        style={{ color: '#fff' }}
        onClick={() => setIsReceiptSettingsOpen(true)}
        className="Button--header"
        // disabled={!isEntityLoaded}
      >
        <i className="i i-receipt" />
        {!isMobile && <span style={{ marginBottom: '4px' }}>Payment Receipts</span>}
      </Button.Transparent> */}
      {!isMobile ? (
        <BladeButton
          variant="secondary"
          color="primary"
          size="medium"
          icon={SettingsIcon}
          iconPosition="left"
          isFullWidth
          onClick={() => {
            setIsPageSettingsOpen(true);
            track.pageSettingsClicked({
              storefrontId: id ?? undefined,
              isNewStorefront: Boolean(isCreate),
            });
          }}
        >
          Page settings
        </BladeButton>
      ) : (
        <BladeButton
          variant="secondary"
          color="primary"
          size="xsmall"
          icon={SettingsIcon}
          onClick={() => {
            setIsPageSettingsOpen(true);
            track.pageSettingsClicked({
              storefrontId: id ?? undefined,
              isNewStorefront: Boolean(isCreate),
            });
          }}
        />
      )}
      {!isMobile ? (
        <BladeButton variant="primary" color="primary" size="medium" onClick={onSubmit} isFullWidth>
          Publish page
        </BladeButton>
      ) : (
        <MobileActionButtons
          onPublish={onSubmit}
          isPreview={isMobilePreview}
          setPreview={setIsMobilePreview}
          isCreate={isCreate}
          storefrontId={id}
        />
      )}
    </React.Fragment>
  );
  return (
    <div>
      {isProductModal === 0 ? (
        <ProductDrawer
          handleClose={setProductModal.bind(null, -1)}
          productData={productData}
          categories={storefront.allCategories.data}
          onCategoryAddSuccess={handleCategoryAddSuccess}
          onSuccess={onProductAddSuccess}
          isCreate={isCreate}
          storeFrontId={id}
          screenSource="store_view"
        />
      ) : (
        isProductModal === 1 && (
          <SelectProductDrawer
            handleClose={setProductModal.bind(null, -1)}
            openAddModal={setProductModal.bind(null, 0)}
            isCreate={isCreate}
          />
        )
      )}
      {isPageSettingsOpen && (
        <PageSettings
          storefrontEntity={storefront.entity}
          onSave={handlePageSettingsSave}
          onPluginsAndAddOnsSave={handlePluginsAndAddOnsSave}
          onClose={() => setIsPageSettingsOpen(false)}
          isMobile={isMobile}
        />
      )}
      {isReceiptSettingsOpen && (
        <ReceiptSettings
          storefrontEntity={storefront.entity}
          onSave={handleReceiptSettingsSave}
          onClose={() => setIsReceiptSettingsOpen(false)}
        />
      )}
      <StorefrontHeader
        title={pageTitle}
        actionBtns={actionBtns}
        handleClose={handleClose}
        isSticky
        isStorefront={true}
        isMobile={isMobile}
      >
        <StoreFrontWrapper>
          {storefront.error ? (
            <div className="page-center">
              Storefront with id <b>{storefront.id}</b> doesn&apos;t exist.
              <br />
              Go to <ReactRouterLink to="/paymentpages/">Payment Pages list</ReactRouterLink>{' '}
            </div>
          ) : (
            <>
              {(!isMobile || (isMobile && !isMobilePreview)) && (
                <StorefrontLeftWrapper>
                  <StorefrontPageTitle />
                  <LeftContentWrapper>
                    {isInitialLoaded === -1 ? (
                      <ProductsSkeleton />
                    ) : products.length === 0 ? (
                      <>
                        <AddProductBox onClick={onAddProductClick}>
                          <i className="i-plus-circle" />
                          <h4>
                            {isInitialLoaded === 0
                              ? 'Add your first product'
                              : 'Add products to this page'}
                          </h4>
                          <p> Showcase the product that you want to sell on this storefront</p>
                        </AddProductBox>
                        {isSampleProduct && (
                          <SampleProducts removeProduct={removeSampleProduct} isMobile={isMobile}>
                            <StickyFooter>
                              <BladeButton
                                iconPosition="left"
                                icon={PlusCircleIcon}
                                onClick={onAddProductClick}
                                size="medium"
                                type="button"
                                variant="secondary"
                              >
                                Add product
                              </BladeButton>
                            </StickyFooter>
                          </SampleProducts>
                        )}
                      </>
                    ) : (
                      <ProductSection
                        data={products}
                        className="product-section"
                        editProduct={openEditProductDrawer}
                        removeProduct={onRemoveProduct}
                        isMobile={isMobile}
                      >
                        <StickyFooter>
                          <BladeButton
                            iconPosition="left"
                            icon={PlusCircleIcon}
                            onClick={onAddProductClick}
                            size="medium"
                            type="button"
                            variant="secondary"
                          >
                            Add product
                          </BladeButton>
                        </StickyFooter>
                      </ProductSection>
                    )}
                    {isStorefrontV1Enabled ? (
                      <SuspenseWithLoader>
                        <Box
                          display="flex"
                          flexDirection="column"
                          gap={isMobile ? 'spacing.0' : 'spacing.5'}
                          marginTop="32px"
                          marginBottom="spacing.8"
                        >
                          <AddBuisnessDetails
                            handleClick={handleBuisnessDetailsClick}
                            openBuisnessDetailsDrawer={openBuisnessDetailsDrawer}
                            isMobile={isMobile}
                          />

                          {isSocialHandlesEnabled && (
                            <AddSocialMediaDetails
                              handleAddSocialMediaClick={handleAddSocialMediaClick}
                              openSocialMediaDrawer={openSocialMediaDrawer}
                              showSocialMedialAlert={showAlert.showSocialHandleAlert}
                              setShowSocialMediaAlert={setShowAlert}
                              storefrontId={id}
                            />
                          )}

                          <AddBannerDetails
                            handleClick={handleAddBannerClick}
                            openAddBannerDrawer={openAddBannerDrawer}
                            showBannerAlert={showAlert.showBannerAlert}
                            setShowBannerAlert={setShowAlert}
                          />
                        </Box>
                      </SuspenseWithLoader>
                    ) : (
                      <>
                        <ContactDetails isLoaded={isInitialLoaded !== -1} />
                        {isSocialHandlesEnabled && (
                          <SuspenseWithLoader>
                            <Box marginTop="32px" marginBottom="spacing.8">
                              <AddSocialMediaDetails
                                handleAddSocialMediaClick={handleAddSocialMediaClick}
                                openSocialMediaDrawer={openSocialMediaDrawer}
                                showSocialMedialAlert={showAlert.showSocialHandleAlert}
                                setShowSocialMediaAlert={setShowAlert}
                                storefrontId={id}
                              />
                            </Box>
                          </SuspenseWithLoader>
                        )}
                      </>
                    )}
                  </LeftContentWrapper>
                </StorefrontLeftWrapper>
              )}
              {(!isMobile || (isMobile && isMobilePreview)) && (
                <StorefrontRightWrapper>
                  <DescriptionWrapper>
                    <DescriptionLeftWrapper>
                      <Heading weight="semibold" size="small" color="surface.text.gray.normal">
                        Preview of your store
                      </Heading>
                      <Box>
                        <Text
                          variant="body"
                          size="small"
                          weight="regular"
                          color="surface.text.gray.normal"
                        >
                          Customise your store with your{' '}
                          <Link
                            size="small"
                            onClick={() => openBrandColorSettingsPage('brand color')}
                            variant="button"
                            icon={ExternalLinkIcon}
                            iconPosition="right"
                          >
                            brand color
                          </Link>{' '}
                          and{' '}
                          <Link
                            onClick={() => openBrandColorSettingsPage('logo')}
                            variant="button"
                            size="small"
                            icon={ExternalLinkIcon}
                            iconPosition="right"
                          >
                            logo
                          </Link>
                        </Text>
                      </Box>
                    </DescriptionLeftWrapper>
                    <PreviewButtons
                      isDesktop={storefront.isDesktopPreview}
                      onClick={(val) => {
                        track.previewStorefrontClicked({
                          storefrontId: id ?? undefined,
                          isNewStoreFront: Boolean(isCreate),
                          preview_type: val ? 'desktop preview' : 'mobile preview',
                        });
                        previewDevice(val);
                      }}
                    />
                  </DescriptionWrapper>
                  {isStorefrontV1Enabled && (
                    <ChromeSearchBar
                      isDesktopPreview={storefront.isDesktopPreview}
                      isMobile={isMobile}
                    />
                  )}
                  <Iframe
                    // src="http://localhost:8888/preview_store"
                    src={`${window.PP_ECOMMERCE_URL}/stores/preview_store`}
                    width={storefront.isDesktopPreview || isMobile ? '100%' : '400'}
                    className={`${storefront.isDesktopPreview ? '' : 'is-mobile'}`}
                    height={deviceHeight}
                    frameBorder="0"
                    ref={iframeRef}
                    loading="lazy"
                    isDesktopPreview={storefront.isDesktopPreview}
                    isMobile={isMobile}
                    // scrolling="no"
                  />
                </StorefrontRightWrapper>
              )}
            </>
          )}
        </StoreFrontWrapper>
      </StorefrontHeader>
    </div>
  );
};

const mapStateToProps = (state) => {
  return {
    storefront: state.paymentPageStorefront,
    isMobile: state.app.isMobileResolution,
    user: state.session.user,
    mode: state.session.mode,
    org: state.session.org,
    config: state.config.config,
    globalSupportDetails: state.supportdetails.merchantSupportDetail.data,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      editStorefront,
      editStorefrontDeepMerge,
      fetchCategories,
      fetchSupportDetail,
      openModal,
      closeModal,
      showNotification,
      removeProduct,
      previewDevice,
      resetStorefront,
      fetchStorefront,
      addProduct,
      editProduct,
      addCategory,
    },
    dispatch,
  );

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(StoreFront));
