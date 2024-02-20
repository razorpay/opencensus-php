import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';

// api imports
import { getCollections } from 'merchant/views/MagicCheckout/CouponEngine/api';

// ui imports
import Input from 'common/new-ui/Input';
import Loader from 'common/ui/Loader';
import {
  ModalHeader,
  AddItemContainer,
  ModalCtaWrapper,
  CollectionWrapper,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import {
  SearchBox,
  SearchInput,
  ProductList,
  ItemWrapper,
} from 'merchant/views/MagicCheckout/CouponEngine/styles/AddProductModal';

// helpers imports
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

interface AddCollectionModalProps {
  closeModal: () => void;
  handleDiscountedItems: (selectedCollection: any) => void;
  showNotification: (notification: any) => void;
}

const AddCollectionModal: React.FC<AddCollectionModalProps> = ({
  closeModal,
  handleDiscountedItems,
  showNotification,
}) => {
  const [apiData, setApiData] = useState<any[]>([]);
  const [formattedDataForRadioButton, setFormattedDataForRadioButton] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [selectedCollections, setSelectedCollections] = useState<any[]>([]);
  const [nextPageCursor, setNextPageCursor] = useState<string>('');
  const [hasMore, setHasMore] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');

  const handleConfirm = () => {
    handleDiscountedItems(selectedCollections);
    closeModal();
  };

  const handleClose = () => {
    setSelectedCollections([]);
    closeModal();
  };

  const fetchCollectionsData = async (resetCursor = false) => {
    const updatedNextPageCursor = resetCursor ? '' : nextPageCursor;

    try {
      setIsLoading(true);
      const { data: collectionList } = await getCollections(15, updatedNextPageCursor, searchTerm);
      const options = collectionList.collections.map(({ title, id }: any) => ({
        label: title,
        value: id,
      }));
      setApiData((prev) => [...prev, ...collectionList.collections]);
      setFormattedDataForRadioButton((prev) => [...prev, ...options]);
      setNextPageCursor(collectionList.cursor);
      setHasMore(collectionList.hasNextPage);
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Something went wrong in fetching collections. Please try again later',
      });
    } finally {
      setIsLoading(false);
    }
  };

  function searchCollection() {
    setApiData([]);
    setFormattedDataForRadioButton([]);
    setHasMore(true);
    fetchCollectionsData(true);
  }

  const handleScroll = (e: React.UIEvent<HTMLDivElement, UIEvent>) => {
    const { scrollTop, clientHeight, scrollHeight } = e.currentTarget;
    if (scrollHeight - scrollTop - 2 <= clientHeight && !isLoading && hasMore) {
      if (hasMore) {
        fetchCollectionsData();
      }
    }
  };

  useEffect(() => {
    fetchCollectionsData();
  }, []);

  useEffect(() => {
    let debounceTimer;

    if (searchTerm) {
      debounceTimer = setTimeout(() => {
        searchCollection();
      }, 1000);
    }
    return () => {
      clearTimeout(debounceTimer);
    };
  }, [searchTerm]);

  const handleVariantCheckboxChange = (selectedCollectionId: string) => {
    const selectedCollection = apiData.find((collection) => collection.id === selectedCollectionId);

    setSelectedCollections((prev) => {
      if (prev.some((collection) => collection.id === selectedCollectionId)) {
        return prev.filter((collection) => collection.id !== selectedCollectionId);
      } else {
        return [...prev, selectedCollection];
      }
    });
  };

  return (
    <div>
      <AddItemContainer>
        <ModalHeader>
          <div className="title">Choose collection</div>
          <div className="exit-cta" onClick={closeModal}>
            <i className="i i-close" />
          </div>
        </ModalHeader>
        <div>
          <SearchBox>
            <SearchInput
              type="text"
              placeholder="Search collections"
              value={searchTerm}
              onChange={(e) => {
                setSearchTerm(e.target.value);
              }}
            />
          </SearchBox>
          <ProductList className="scroll" onScroll={handleScroll}>
            {formattedDataForRadioButton.map((collection: any) => (
              <ItemWrapper key={collection.value}>
                <CollectionWrapper>
                  <Input.Check
                    autoRender
                    checked={selectedCollections.some((item) => item.id === collection.value)}
                    onChange={() => {
                      handleVariantCheckboxChange(collection.value);
                    }}
                  />
                  <div>{collection.label}</div>
                </CollectionWrapper>
                <hr />
              </ItemWrapper>
            ))}
            {isLoading && <Loader />}
          </ProductList>
        </div>
      </AddItemContainer>
      <ModalCtaWrapper>
        <div>{selectedCollections.length} collection</div>
        <div>
          <button className="secondary-cta" onClick={handleClose}>
            Cancel
          </button>
          <button className="primary-cta" onClick={handleConfirm}>
            Confirm
          </button>
        </div>
      </ModalCtaWrapper>
    </div>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(AddCollectionModal);
