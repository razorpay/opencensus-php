import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { withRouter } from 'common/deprecated/withRouter';

import ProductWrapper from 'common/ui/ProductWrapper';
import ListContainer from 'merchant/containers/ListContainer';
import ShowWhen from 'merchant/components/ShowWhen';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { DocLink } from 'merchant/components/DocsLink';
import TestModeBanner from 'merchant/components/TestModeBanner';
import Spinner from 'common/ui/Spinner';
import ListFilter from 'merchant/components/ListFilter';
import EmptyList from 'merchant/components/EmptyList';
import Pager from 'common/ui/Pager';
import { Field } from 'redux-form';
import List from 'merchant/views/PaymentPages/Products/List';
import ProductDrawer from 'merchant/views/PaymentPages/common/Products/ProductDrawer';
import ManageCategoriesDrawer from 'merchant/views/PaymentPages/Products/ManageCategoriesDrawer';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { populateRPLReduxList } from 'merchant/reducers/invoices/list';
// import track from 'merchant/views/PaymentPages/PaymentPages/List/track';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import {
  fetchProducts,
  fetchCategories,
  updateProductsList,
  deleteProductFromList,
  deleteCategoryFromList,
  updateCategoriesList,
  addInCategoriesList,
} from 'merchant/reducers/paymentPages/products';
import { showNotification } from 'merchant_common/reducers/notifications';
import { transformCatalog } from 'merchant/reducers/paymentPages/transformer';
import { RZPFeatures } from 'merchant/helpers/data';
import { getPaymentPagesTabs } from 'merchant/views/PaymentPages/PaymentPages/utils';

import CategoryIcon from 'assets/payment_pages/categories.svg';

@connect(
  (state) => ({
    ...state.paymentPagesProducts,
    ...state.session,
    paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PP),
  }),
  {
    showNotification,
    populateRPLReduxList,
    handleProductQuickGuide,
    fetchProducts,
    fetchCategories,
    updateProductsList,
    deleteProductFromList,
    deleteCategoryFromList,
    updateCategoriesList,
    addInCategoriesList,
  },
)
@RTracking(() => window.rzpQ.component('Products'))
class Products extends ListContainer {
  constructor(props) {
    super(props);
    this.state = {
      productDrawer: {
        isOpen: false,
        data: null,
      },
      isCategoriesDrawerOpen: false,
    };
    this.tabs = getPaymentPagesTabs(props.user);
  }

  componentDidMount() {
    this.props.fetchCategories();
    analyticsTrack({
      objectName: 'Products page',
      actionName: 'loaded',
      screen: 'Products screen',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }

  // called from parent List container
  fetchEntityList(params) {
    return this.props.fetchProducts(params).catch((err) => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });
  }

  handlePaginate = (params) => {
    this.paginate(params);
  };

  handleAddProduct = () => {
    this.setState({ productDrawer: { data: null, isOpen: true } });
    analyticsTrack({
      objectName: 'Adding a product',
      actionName: 'Clicked',
      screen: 'Products Screen',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        screen_source: 'listing_view',
      },
    });
  };

  handleEditProduct = (product) => {
    const transformedProductData = transformCatalog(product);
    this.setState({ productDrawer: { data: transformedProductData, isOpen: true } });
  };

  handleAddEditProductSuccess = (savedProduct) => {
    const isCreate = !this.state.productDrawer.data;

    if (isCreate) {
      /*
        fetching the products list again with applied filters using parent class's lifecycle method
        ref: https://stackoverflow.com/questions/55051620/how-to-call-inherited-function-in-react-child-component/55052195#55052195
      */
      //eslint-disable-next-line
      super.UNSAFE_componentWillMount();
    } else {
      this.props.updateProductsList(savedProduct);
    }

    this.handleProductDrawerClose();
  };

  handleProductDeleteSuccess = (id) => {
    this.props.deleteProductFromList(id);

    this.handleProductDrawerClose();
  };

  handleProductDrawerClose = () => {
    this.setState({ productDrawer: { isOpen: false, data: null } });
  };

  handleCategoryAddSuccess = (category) => {
    this.props.addInCategoriesList(category);

    analyticsTrack({
      objectName: 'Add New Category',
      actionName: 'Clicked on Add Category',
      screen: 'Products Screen',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        screen_source: 'listing_view',
      },
    });
  };

  toggleManageCategories = (isCategoriesDrawerOpen) => {
    this.setState({ isCategoriesDrawerOpen });
    analyticsTrack({
      objectName: 'Manage categories',
      actionName: 'Clicked',
      screen: 'Products Screen',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  handleCategoryDeleteSuccess = (id) => {
    this.props.deleteCategoryFromList(id);

    // fetching the products list again to show data with updated categories
    //eslint-disable-next-line
    super.UNSAFE_componentWillMount();
  };

  handleCategoryEditSuccess = (category) => {
    this.props.updateCategoriesList(category);

    // fetching the products list again to show data with updated categories
    //eslint-disable-next-line
    super.UNSAFE_componentWillMount();
  };

  onSearchAnalytics = (params) => {
    const { title, count, status } = params;

    analyticsTrack({
      objectName: 'Search',
      actionName: 'Clicked',
      screen: 'Products screen',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        title,
        status,
        count,
      },
    });
  };

  render() {
    const { user, products, categories } = this.props;

    const isRoleAllowedEdit = user.isAllowedEdit('payment_pages');
    let content;

    if (products.loading) {
      content = (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else if (isRoleAllowedEdit && !products.items.length) {
      content = <EmptyComponent />;
    } else {
      content = (
        <React.Fragment>
          <List
            loading={products.loading}
            products={products.items}
            handleEditProduct={this.handleEditProduct}
          />
          {!products.loading && !!products.items.length && (
            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={products.items.length}
              onClick={this.handlePaginate}
            />
          )}
        </React.Fragment>
      );
    }

    return (
      <ProductWrapper
        tabsData={this.tabs}
        extra={
          <>
            <ShowWhen additionalCondition={() => !user.isOrgAxis}>
              <TakeATourButton feature={RZPFeatures.PP} /* onClick={track.takeTour} */ />
            </ShowWhen>

            <ShowWhen additionalCondition={() => user.isOrgAllowedFunctionality('external_links')}>
              <DocLink
                class="btn btn-link"
                href="https://razorpay.com/docs/payment-pages/"
                target="_blank"
                // onClick={track.viewDoc}
              >
                Documentation&nbsp;
                <i class="i i-external-link" />
              </DocLink>
            </ShowWhen>

            {isRoleAllowedEdit && (
              <>
                <a class="btn-link" onClick={() => this.toggleManageCategories(true)}>
                  <img src={CategoryIcon} alt="category icon" style={{ marginRight: '5px' }} />
                  Manage Categories
                </a>
                <span class="cta-container m-l">
                  <span class="btn btn-primary" onClick={this.handleAddProduct}>
                    <i class="i i-plus" />
                    <span>Add a new product</span>
                  </span>
                </span>
              </>
            )}
          </>
        }
      >
        <content>
          <div class="content-wrapper">
            <TestModeBanner />
            <ListFilter
              form="PaymentPagesPaymentListFilter"
              count={this.state.count}
              onSearchAnalytics={this.onSearchAnalytics}
              onClearAnalytics={this.onClearAnalytics}
            >
              <div class="form-group list-filter-item">
                <label>Product name</label>
                <Field
                  name="product_name"
                  component="input"
                  class="form-control input-sm"
                  // onBlur={track.searchTitle}
                />
              </div>

              {/* Backend does not support this as of now , hence commented this out */}
              {/* <div class="form-group list-filter-item">
                <label>Category</label>
                <Field
                  name="category"
                  component="select"
                  class="form-control input-sm"
                  // onChange={track.searchStatus}
                >
                  <option value="">All</option>

                  {categories.items.map((category) => {
                    return (
                      <option key={category.id} value={category.id}>
                        {category.name}
                      </option>
                    );
                  })}
                </Field>
              </div> */}

              <div class="form-group list-filter-item">
                <label>Status</label>
                <Field name="status" component="select" class="form-control input-sm">
                  <option value="">All</option>
                  <option value="in_stock,unlimited">Available</option>
                  <option value="out_of_stock">Out of Stock</option>
                </Field>
              </div>
            </ListFilter>
            {content}
          </div>
        </content>
        {this.state.productDrawer.isOpen && (
          <ProductDrawer
            drawerPosition="right"
            hasTransparentBackground={true}
            onSuccess={this.handleAddEditProductSuccess}
            onDeleteSuccess={this.handleProductDeleteSuccess}
            handleClose={this.handleProductDrawerClose}
            productData={this.state.productDrawer.data}
            showCentralCatalogueInfo={false}
            showDeleteCTA={true}
            showProductStatus={true}
            categories={categories?.items}
            onCategoryAddSuccess={this.handleCategoryAddSuccess}
            top="55px"
            screenSource="listing_view"
          />
        )}
        {this.state.isCategoriesDrawerOpen && (
          <ManageCategoriesDrawer
            handleClose={() => this.toggleManageCategories(false)}
            categories={categories?.items}
            onDeleteSuccess={this.handleCategoryDeleteSuccess}
            onEditSuccess={this.handleCategoryEditSuccess}
          />
        )}
      </ProductWrapper>
    );
  }
}

const EmptyComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no products yet!!</div>
        <div>Start creating products now.</div>
      </React.Fragment>
    }
  />
);

export default withRouter(Products);
