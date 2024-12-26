import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

// UI imports
import Loader from 'common/ui/Loader';
import SearchItem from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/SearchItem';
import {
  CtaContainer,
  AddItemContainer,
  ProductList,
  ModalHeader,
  SearchBox,
  SearchInput,
} from 'merchant/views/MagicCheckout/CouponEngine/styles/AddProductModal';
import { Alert } from '@razorpay/blade/components';

// api imports
import { getProducts } from 'merchant/views/MagicCheckout/CouponEngine/api';

// helpers imports
import { closeModal } from 'merchant_common/reducers/modals';

import { ITEM_SELECTION_RESTRICTIONS } from 'merchant/views/MagicCheckout/CouponEngine/constants';

import { SelectedProduct } from '../common/SearchItem/types';

interface AddProductsProps {
  closeModal: () => void;
  handleDiscountedItems: (selectedProducts: any) => void;
  dashboardView: string;
  widgetName: string;
  couponName: string;
  discountedItems: Array<SelectedProduct>;
}

const AddProducts: React.FC<AddProductsProps> = ({
  closeModal,
  handleDiscountedItems,
  dashboardView,
  widgetName,
  couponName,
  discountedItems,
}) => {
  const [isLoading, setIsLoading] = useState(false);
  const [data, setData] = useState<any[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const isFreebieCoupon = couponName === 'freebie_item';

  const prefillSavedSelections = () => {
    const selectedProducts = {};
    discountedItems?.forEach((discountedItem) => {
      selectedProducts[discountedItem?.product_id] = discountedItem;
    });
    return selectedProducts;
  };

  const [selectedProducts, setSelectedProducts] = useState<{ [key: string]: SelectedProduct }>(
    prefillSavedSelections(),
  );

  const maxSelectableProductVariants =
    ITEM_SELECTION_RESTRICTIONS[couponName]?.[widgetName]?.products;

  const shouldEnforceVariantSelectionRestriction =
    !isNaN(maxSelectableProductVariants) &&
    Object.values(selectedProducts)?.reduce((totalVariantsCount, currentProduct) => {
      return totalVariantsCount + (currentProduct?.variants?.length ?? 0);
    }, 0) >= maxSelectableProductVariants;

  const fetchProductsData = async () => {
    try {
      setIsLoading(true);
      const limit = 5;
      const offset = (page - 1) * limit;
      const response = await getProducts(limit, offset, searchTerm, dashboardView);
      const products = response.data.products;
      setData((prev) => [...prev, ...products]);
      setHasMore(response.data.products.length > 0 && response.data.products.length === limit);
    } catch (error) {
      setHasMore(false);
    } finally {
      setIsLoading(false);
    }
  };

  const handleReset = () => {
    closeModal();
  };

  const handleSubmit = () => {
    handleDiscountedItems(selectedProducts);
    closeModal();
  };

  const handleScroll = (e: React.UIEvent<HTMLDivElement, UIEvent>) => {
    const { scrollTop, clientHeight, scrollHeight } = e.currentTarget;

    if (scrollHeight - scrollTop - 2 <= clientHeight && !isLoading && hasMore) {
      setPage((prevPage) => prevPage + 1);
    }
  };

  useEffect(() => {
    setData([]);
    setPage(1);
  }, [searchTerm]);

  useEffect(() => {
    const debounceTimer = setTimeout(() => {
      fetchProductsData();
    }, 1000);

    return () => {
      clearTimeout(debounceTimer);
    };
  }, [searchTerm, page]);

  return (
    <div>
      <AddItemContainer>
        <ModalHeader>
          <div className="title">Add products</div>
          <div className="exit-cta" onClick={() => closeModal()}>
            <i className="i i-close" />
          </div>
        </ModalHeader>
        {maxSelectableProductVariants ? (
          <Alert
            description={`Maximum ${maxSelectableProductVariants} variant selections allowed`}
            isDismissible={false}
            color="information"
            marginY="spacing.2"
          />
        ) : null}
        <SearchBox>
          <SearchInput
            type="text"
            placeholder="Search products"
            value={searchTerm}
            onChange={(e) => {
              setSearchTerm(e.target.value);
            }}
          />
        </SearchBox>

        <ProductList className="scroll" onScroll={handleScroll}>
          {data.map((item) => {
            return (
              <SearchItem
                product={item}
                key={item.id}
                selectedProducts={selectedProducts}
                setSelectedProducts={setSelectedProducts}
                shouldDisableProductCheckbox={!!(maxSelectableProductVariants && isFreebieCoupon)}
                shouldDisableVariantCheckbox={shouldEnforceVariantSelectionRestriction}
                isFreebieCoupon={isFreebieCoupon}
              />
            );
          })}

          {isLoading && <Loader />}
        </ProductList>
      </AddItemContainer>
      <CtaContainer>
        <div>
          Adding {Object.keys(selectedProducts).length} products |{' '}
          {Object.values(selectedProducts).reduce(
            (acc, product) => acc + (product ? product.variants.length : 0),
            0,
          )}{' '}
          variants
        </div>
        <div>
          <button className="secondary-cta" onClick={handleReset}>
            Cancel
          </button>
          <button className="primary-cta" onClick={handleSubmit}>
            Confirm
          </button>
        </div>
      </CtaContainer>
    </div>
  );
};

const mapStateToProps = (state) => ({
  dashboardView: state.magicCheckout.dashboard_view,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(AddProducts);
