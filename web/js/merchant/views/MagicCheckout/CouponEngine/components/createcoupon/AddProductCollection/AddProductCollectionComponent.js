import React, { useContext } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import lazy from 'merchant/routes/LazyLoader';

import Input from 'common/new-ui/Input';
import DataTable from 'common/ui/Table/DataTable';
import {
  collectionName,
  productCount,
  collectionAction,
  TableWrapper,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/SelectedDetailsTableElements';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';
import {
  Image,
  ProductName,
} from 'merchant/views/MagicCheckout/CouponEngine/styles/AddProductModal';
import {
  FormGroup,
  DottedButtonWrapper,
  DottedButton,
  RemoveIcon,
  AddCollectionsCta,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import { openModal } from 'merchant_common/reducers/modals';
import { validateDiscountItems } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponFormValidators';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const DiscountedItemModal = lazy(() =>
  import(
    /* webpackChunkName: 'MagicCouponEngineDiscountedItemModal' */ 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/DiscountedItemModal'
  ),
);

const AddCollectionProductComponent = ({ openModal, stateObject = 'discountDetails' }) => {
  const { widgetsData, setWidgetsData, errorStates, setErrorStates } = useContext(ModalContext);

  const handleDiscountedItems = (data) => {
    let selectedItemsList = [];

    if (widgetsData[stateObject].discountApplicableTo === 'products') {
      selectedItemsList = Object.values(data);
    } else {
      selectedItemsList = data;
    }
    const newData =
      widgetsData[stateObject].discountApplicableTo === 'products'
        ? Object.values(data).reduce(
            (accumulator, currentObject) => accumulator.concat(currentObject.variants),
            [],
          )
        : data.map((collection) => collection.id);

    const updatedDiscountedItemsList = [
      ...widgetsData[stateObject].discountedItemsList,
      ...newData,
    ];

    setWidgetsData({
      ...widgetsData,
      [stateObject]: {
        ...widgetsData[stateObject],
        discountedItemsList: updatedDiscountedItemsList,
        discountedItemsDisplayList: [
          ...widgetsData[stateObject].discountedItemsDisplayList,
          ...selectedItemsList,
        ],
      },
    });

    validateDiscountItems({
      setErrorStates,
      value: updatedDiscountedItemsList,
      fieldName: stateObject,
      subFieldName: 'discountedItemsList',
    });
  };

  const openAddItemsModal = () => {
    openModal({
      size: 'large',
      className: 'create-coupon-modal',
      component: (
        <SuspenseWithLoader type="center">
          <DiscountedItemModal
            modalType={widgetsData[stateObject].discountApplicableTo}
            handleDiscountedItems={handleDiscountedItems}
          />
        </SuspenseWithLoader>
      ),
    });
  };

  return (
    <FormGroup>
      <div className="form-label">Applies to</div>
      <div className="form-input max-width-100">
        <div className="mb-12">
          <Input.Radio
            autoRender
            key={widgetsData[stateObject].discountApplicableTo}
            options={[
              {
                label: 'Products',
                value: 'products',
              },
              {
                label: 'Collections',
                value: 'collections',
              },
            ]}
            defaultValue={widgetsData[stateObject].discountApplicableTo}
            onChange={(e) => {
              setWidgetsData({
                ...widgetsData,
                [stateObject]: {
                  ...widgetsData[stateObject],
                  discountedItemsList: [],
                  discountApplicableTo: e.target.value,
                  discountedItemsDisplayList: [],
                },
              });
            }}
          />
        </div>
        <DottedButtonWrapper>
          {/** Intentioally added this check because of the data coming in case of shopify from synced coupons */}
          {widgetsData[stateObject].discountedItemsDisplayList.length > 0 &&
          widgetsData[stateObject].discountedItemsDisplayList[0] !== '' ? (
            <div>
              {widgetsData[stateObject].discountApplicableTo === 'products' ? (
                <div>
                  {widgetsData[stateObject].discountedItemsDisplayList.map(
                    ({ product_id, product_name, product_image_url, variants }) => (
                      <div
                        className="display-flex align-center justify-space-between"
                        style={{ borderBottom: '1px solid #EBECED', padding: '12px 0' }}
                        key={product_id}
                      >
                        <div className="display-flex gap--12 align-center">
                          <Image src={product_image_url} alt={product_name} />
                          <ProductName>
                            <strong>
                              {product_name} -{' '}
                              {variants.length > 1
                                ? `(${variants.length} variants)`
                                : '(1 variant)'}
                            </strong>
                          </ProductName>
                        </div>
                        <div>
                          <RemoveIcon
                            className="i i-close"
                            onClick={() => {
                              const updatedDiscountedItemsDisplayList = widgetsData[
                                stateObject
                              ].discountedItemsDisplayList.filter((item) => {
                                return item.product_id !== product_id;
                              });
                              const discountedItemsList = [].concat(
                                ...updatedDiscountedItemsDisplayList.map(
                                  (product) => product.variants,
                                ),
                              );
                              setWidgetsData({
                                ...widgetsData,
                                [stateObject]: {
                                  ...widgetsData[stateObject],
                                  discountedItemsList,
                                  discountedItemsDisplayList: updatedDiscountedItemsDisplayList,
                                },
                              });
                            }}
                          />
                        </div>
                      </div>
                    ),
                  )}
                  <div
                    style={{
                      color: '#0B70E7',
                      fontSize: '14px',
                      cursor: 'pointer',
                      fontWeight: 600,
                      padding: '16px 0 0',
                      width: 'fit-content',
                    }}
                    onClick={openAddItemsModal}
                  >
                    <i className="i i-plus" style={{ marginRight: '4px' }} /> Add Products
                  </div>
                </div>
              ) : (
                <div>
                  <TableWrapper>
                    <DataTable
                      noStripe={true}
                      items={widgetsData[stateObject].discountedItemsDisplayList}
                      columns={[
                        collectionName,
                        productCount,
                        collectionAction(widgetsData, setWidgetsData, stateObject),
                      ]}
                    />
                  </TableWrapper>
                  <AddCollectionsCta onClick={openAddItemsModal}>
                    <i className="i i-plus" /> Add Collections
                  </AddCollectionsCta>
                </div>
              )}
            </div>
          ) : (
            <DottedButton className="w-350" onClick={openAddItemsModal}>
              + Add{' '}
              {widgetsData[stateObject].discountApplicableTo === 'products'
                ? 'Products'
                : 'Collections'}
            </DottedButton>
          )}
        </DottedButtonWrapper>
        {errorStates[stateObject] && (
          <p className="error-message">{errorStates[stateObject].discountedItemsList}</p>
        )}
      </div>
    </FormGroup>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(AddCollectionProductComponent);
