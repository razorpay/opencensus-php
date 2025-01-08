import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
function _track() {
  let lumberjackTrack = () => {};
  function sendToLumberjack(event, data) {
    lumberjackTrack(
      window.rzpQ.paymentPages().interaction(`pp.${event}`, {
        data,
      }),
    );
  }
  function sendToSegment(
    objectName,
    actionName,
    screen = 'list payment page',
    properties,
    section,
    toCleverTap = false,
  ) {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      toCleverTap,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { isStorefrontPage: true }),
        ...properties,
        section,
      },
    });
  }
  const product_page = 'Storefront Page';
  return {
    load: () => {
      sendToLumberjack('list.load');
      sendToSegment('intial', 'load', 'list payment page', {}, 'list payment page');
    },

    searchCount: (event) => {
      sendToLumberjack('list.count_enter', { value: event.target.value });
      sendToSegment('search with count', 'input', 'list payment page', {
        value: event.target.value,
      });
    },

    createPaymentPage: () => {
      sendToLumberjack('create.click_create');
      sendToSegment(
        'create page',
        'clicked',
        'Select page of your choice',
        {},
        'list payment page',
      );
      selfServeTrackInitiate({
        selfServeAction: 'Create Payment Page',
        page: 'Paymentpage',
        screen: 'Payment Page',
      });
      triggerHotjarRecording('PP_Creation');
    },

    searchStatus: (event) => {
      sendToLumberjack('list.status_click', { value: event.target.value || 'all' });
      sendToSegment('search with status', 'click', 'list payment page', {
        value: event.target.value || 'all',
      });
    },
    searchTitle: (event) => {
      sendToLumberjack('list.title_enter', { value: event.target.value });
      sendToSegment('search with title', 'input', 'list payment page', {
        value: event.target.value,
      });
    },
    search: (params) => {
      sendToLumberjack('search', params);
      sendToSegment('search ', 'click', 'list payment page', params);
    },
    searchClear: () => {
      sendToLumberjack('search.clear');
      sendToSegment('clear search filters', 'click');
    },

    paginate: (type, data) => {
      sendToLumberjack(`browse.${type}`, data);
      sendToSegment(`browse ${type}`, 'click', 'list payment page', data);
    },
    takeTour: () => {
      sendToLumberjack('list.take_tour');
      sendToSegment('take tour', 'clicked');
    },
    viewDoc: () => {
      sendToLumberjack('list.view_documentation');
      sendToSegment('view documentation', 'clicked');
    },
    copyUrl: () => {
      sendToLumberjack('list.copy_url');
      sendToSegment('copy url', 'clicked');
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },

    storefrontCheckboxClicked: () => {
      sendToSegment(
        'storfront page checkbox',
        'clicked',
        'list payment page',
        {
          product_page,
        },
        'list Payment Pages',
      );
    },

    selectTemplatePageLoaded: () => {
      sendToSegment(
        'Page',
        'loaded',
        'Select page of your choice',
        {},
        'Select page of your choice',
      );
    },

    selectStorefrontPage: (extraProperties) => {
      sendToSegment(
        'Storefront page',
        'clicked',
        'Create storefront page',
        {
          ...extraProperties,
          product_page,
        },
        'Select page of your choice',
      );
    },

    addFirstProductToStorefront: (extraProperties) => {
      sendToSegment(
        'add your first product',
        'clicked',
        'Add New Product Modal',
        {
          ...extraProperties,
          product_page,
        },
        'Create Storefront Page',
      );
    },

    addProductsModalLoaded: (extraProperties) => {
      sendToSegment(
        'add products',
        'loaded',
        'Existing Product Modal',
        {
          ...extraProperties,
          product_page,
        },
        'Create Storefront Page',
      );
    },

    checkboxClickExistingProducts: (extraProperties) => {
      sendToSegment(
        'checkbox',
        'clicked',
        'Existing Product Modal',
        {
          ...extraProperties,
          product_page,
        },
        'Existing Product Modal',
      );
    },

    addNewProductClicked: (extraProperties) => {
      sendToSegment(
        'add new product',
        'clicked',
        'Add New Product Modal',
        {
          ...extraProperties,
          product_page,
        },
        'Existing Product Modal',
      );
    },

    handleAddNewProductEntered: (extraProperties, objectName) => {
      sendToSegment(
        objectName,
        'entered',
        'Add New Product Modal',
        {
          ...extraProperties,
          product_page,
        },
        'Add New Product Modal',
      );
    },

    handleAddNewCategoryClicked: (extraProperties) => {
      sendToSegment(
        'add new category',
        'clicked',
        'Add New Category Modal',
        {
          ...extraProperties,
          product_page,
        },
        'Add New Product Modal',
      );
    },

    handleAddNewCategoryLoaded: (extraProperties) => {
      sendToSegment(
        'new catgeory modal',
        'loaded',
        'Add New Category Modal',
        {
          ...extraProperties,
          product_page,
        },
        'Add New Category Modal',
      );
    },

    handleAddNewCategoryEntered: (extraProperties) => {
      sendToSegment(
        'new category',
        'entered',
        'Add New Category Modal',
        {
          ...extraProperties,
          product_page,
        },
        'Add New Category Modal',
      );
    },

    addCategoryClicked: (extraProperties) => {
      sendToSegment(
        'add category',
        'clicked',
        'Add New Product Modal',
        {
          ...extraProperties,
          product_page,
        },
        'Add New Category Modal',
      );
    },

    addExistingProductsClicked: (extraProperties) => {
      sendToSegment(
        'add existing products',
        'clicked',
        'Create Storefront Pages',
        {
          ...extraProperties,
          product_page,
        },
        'Existing Product Modal',
      );
    },

    previewStorefrontClicked: (extraProperties) => {
      sendToSegment(
        'preview',
        'clicked',
        'Create Storefront Pages',
        {
          ...extraProperties,
          product_page,
        },
        'Create Storefront Pages',
      );
    },

    customizeStorefrontClicked: (extraProperties) => {
      sendToSegment(
        'custom page',
        'clicked',
        'Create Storefront Pages',
        {
          ...extraProperties,
          product_page,
        },
        'Create Storefront Pages',
      );
    },

    publishPageClicked: (extraProperties) => {
      sendToSegment(
        'publish page',
        'clicked',
        'Create Storefront Pages',
        {
          ...extraProperties,
          product_page,
        },
        'Create Storefront Pages',
      );
    },

    pageSettingsClicked: (extraProperties) => {
      sendToSegment(
        'add page setting',
        'clicked',
        'Create Storefront Pages',
        {
          ...extraProperties,
          product_page,
        },
        'Create Storefront Pages',
      );
    },

    publishPageLoadedSucessfully: (extraProperties) => {
      sendToSegment(
        'published page successfully',
        'loaded',
        'Published Page',
        {
          ...extraProperties,
          product_page,
        },
        'Published Page',
      );
    },

    editStorefrontPage: (extraProperties) => {
      sendToSegment(
        'edit page',
        'clicked',
        'Edit storefront page',
        {
          ...extraProperties,
          product_page,
        },
        'Published Page',
      );
    },

    pageSettingClickedOnPublishedPage: (extraProperties) => {
      sendToSegment(
        'Page setting',
        'clicked',
        'Published Page',
        {
          ...extraProperties,
          product_page,
        },
        'Published Page',
      );
    },

    successPageCopyUrl: (extraProperties) => {
      sendToSegment(
        'copy page url',
        'clicked',
        'Published Page',
        { ...extraProperties, product_page },
        'Published Page',
      );
    },

    publishedPagePreview: (extraProperties) => {
      sendToSegment(
        'view published page',
        'clicked',
        'Published Page',
        { ...extraProperties, product_page },
        'Published Page',
      );
    },

    tourPageRendered: () => {
      sendToSegment('Tour page', 'rendered', 'Payment Page Tour', {}, 'Payment Page Tour');
    },

    readMoreClickedOnTourPage: () => {
      sendToSegment('read more', 'clicked', 'Payment Page Tour', {}, 'Payment Page Tour');
    },

    getStartedClickedOnTourPage: () => {
      sendToSegment('get started', 'clicked', 'Payment Page Tour', {}, 'Payment Page Tour');
    },

    skipClickOnTourPage: () => {
      sendToSegment('skip section', 'clicked', 'Payment Page Tour', {}, 'Payment Page Tour');
    },
  };
}

export default _track();
