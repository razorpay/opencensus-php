import React, { useState, useEffect } from 'react';
import isEmpty from 'lodash/isEmpty';
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
  CollectionsList,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

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
  const [selectedCollection, setSelectedCollection] = useState<any>({});
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  const handleConfirm = () => {
    handleDiscountedItems(selectedCollection);
    closeModal();
  };

  const handleClose = () => {
    setSelectedCollection({});
    closeModal();
  };

  const handleRadioButtonChange = (selectedCollectionId: any) => {
    const selectedCollection = apiData.find((collection) => collection.id === selectedCollectionId);
    setSelectedCollection(selectedCollection);
  };

  const handleScroll = (e: React.UIEvent<HTMLDivElement, UIEvent>) => {
    const { scrollTop, clientHeight, scrollHeight } = e.currentTarget;
    if (scrollHeight - scrollTop - 2 <= clientHeight && !isLoading && hasMore) {
      setPage((prevPage) => prevPage + 1);
    }
  };

  const fetchCollectionsData = async () => {
    try {
      setIsLoading(true);
      const limit = 15;
      const offset = (page - 1) * limit;
      const res = await getCollections(limit, offset);
      const options = res.data.collections.map(({ title, id }: any) => ({
        label: title,
        value: id,
      }));
      setApiData((prev) => [...prev, ...res.data.collections]);
      setFormattedDataForRadioButton((prev) => [...prev, ...options]);
      setHasMore(res.data.collections.length > 0 && res.data.collections.length === limit);
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Something went wrong in fetching collections. Please try again later',
      });
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchCollectionsData();
  }, [page]);

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
          <CollectionsList className="scroll" onScroll={handleScroll}>
            <div className="form-input">
              <Input.Radio
                autoRender
                name="collections"
                onChange={(e) => {
                  handleRadioButtonChange(e.target.value);
                }}
                options={formattedDataForRadioButton}
                defaultValue={selectedCollection}
              />
              {isLoading && <Loader />}
            </div>
          </CollectionsList>
        </div>
      </AddItemContainer>
      <ModalCtaWrapper>
        <div>{isEmpty(selectedCollection) ? 0 : 1} collection</div>
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
