import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { CheckIcon, ChevronDownIcon, Spinner } from '@razorpay/blade/components';
import { IPaymentPagesCategory } from 'merchant/reducers/paymentPages/storefront';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import CategoryDrawer from 'merchant/views/PaymentPages/common/Products/CategoryDrawer';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { bindActionCreators } from 'redux';
import {
  CategoryDropdownWrapper,
  Label,
  Optional,
  DropdownWrapper,
  ActiveCategory,
  CategoryList,
  CategoryItemsWrapper,
  CategoryItem,
  AddCategory,
} from './styled';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

interface ICategoryDropdown {
  storeFrontId: string | undefined;
  isCreate: boolean;
  screenSource: 'listing_view' | 'store_view';
  onChange: ({ name, value }) => void;
  categories: IPaymentPagesCategory[];
  value?: string;
  name: string;
  closeModal: () => void;
  openModal: (data: any) => void;
  loading: boolean;
  drawerPosition?: 'left' | 'right';
  hasTransparentBackground?: boolean;
  onCategoryAddSuccess: (data) => void;
  top: string;
}

const emptyCategoryName = 'Select Category';
const emptyCategory = {
  id: '',
  name: emptyCategoryName,
  alias: 'empty_category',
  created_at: '',
};

const CategoryDropdown = ({
  isCreate,
  storeFrontId,
  screenSource,
  onChange,
  categories,
  value,
  name,
  closeModal,
  openModal,
  loading,
  drawerPosition,
  hasTransparentBackground,
  onCategoryAddSuccess,
  top,
}: ICategoryDropdown): React.ReactElement => {
  const [isOpen, setIsOpen] = useState(false);
  const [allCategoriesData, setAllCategoryData] = useState([emptyCategory, ...categories]);
  const handleChange = (e: { id: string | null }) => {
    onChange({ name, value: e.id });
  };

  useEffect(() => {
    setAllCategoryData([emptyCategory, ...categories]);
  }, [categories]);

  const openAddCategoryModal = () => {
    analyticsTrack({
      objectName: 'Add New Category',
      actionName: 'Clicked',
      screen: 'Add New Product',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        storefrontId: storeFrontId,
        isNewStorefront: Boolean(isCreate),
        screenSource,
      },
    });
    openModal({
      isNew: true,
      component: (
        <CategoryDrawer
          handleClose={closeModal}
          onChange={onChange}
          name={name}
          drawerPosition={drawerPosition}
          hasTransparentBackground={hasTransparentBackground}
          onSuccess={onCategoryAddSuccess}
          allCategories={categories}
          top={top}
          isCreate={isCreate}
          storeFrontId={storeFrontId}
          screenSource={screenSource}
        />
      ),
    });
  };

  const activeCategoryName = value
    ? allCategoriesData.find((cat) => cat.id === value)?.name
    : emptyCategoryName;

  return (
    <CategoryDropdownWrapper>
      <Label>
        Category <Optional>(optional)</Optional>
      </Label>
      {loading && (
        <ActiveCategory center>
          <Spinner size="medium" accessibilityLabel="loader" />
        </ActiveCategory>
      )}
      {allCategoriesData.length > 0 ? (
        <DropdownWrapper>
          <Dropdown
            closeOnClick={true}
            onShow={() => setIsOpen(true)}
            onHide={() => setIsOpen(false)}
          >
            <DropdownTrigger>
              <ActiveCategory>
                {activeCategoryName}
                <ChevronDownIcon size="medium" color="surface.text.subtle.lowContrast" />
              </ActiveCategory>
            </DropdownTrigger>
            <DropdownContent>
              {isOpen && (
                <CategoryList>
                  <CategoryItemsWrapper>
                    {allCategoriesData.map((cat) => {
                      const isActive = cat.id === value;
                      return (
                        <CategoryItem
                          key={cat.name}
                          onClick={() => handleChange(cat)}
                          isActive={isActive}
                        >
                          {cat.name}
                          {isActive && (
                            <CheckIcon size="medium" color="action.icon.secondary.default" />
                          )}
                        </CategoryItem>
                      );
                    })}
                  </CategoryItemsWrapper>
                  <CategoryItem onClick={openAddCategoryModal} isAddButton>
                    + Create new category
                  </CategoryItem>
                </CategoryList>
              )}
            </DropdownContent>
          </Dropdown>
        </DropdownWrapper>
      ) : (
        <AddCategory onClick={openAddCategoryModal} type="button">
          + Add a new category
        </AddCategory>
      )}
    </CategoryDropdownWrapper>
  );
};

const mapStateToProps = (state) => ({
  loading: state.paymentPageStorefront.allCategories.loading,
});

const mapDispatchToProps = (dispatch) => bindActionCreators({ closeModal, openModal }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(CategoryDropdown);
