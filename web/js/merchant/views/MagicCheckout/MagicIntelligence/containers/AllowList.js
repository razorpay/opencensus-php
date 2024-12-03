import { useCallback, useReducer, useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import List, {
  EmptyComponent as emptyComponent,
} from 'merchant/views/MagicCheckout/MagicIntelligence/components/List';
import * as ModalActions from 'merchant_common/reducers/modals';
import {
  createAllowlist,
  validateAllowlist,
  deleteAllowlist,
  fetchAllowlist,
  uploadAllowlist,
} from 'merchant/reducers/magicCheckout/magicIntelligence/actions';
import { validateAllowlistModalInfo } from 'merchant/views/MagicCheckout/MagicIntelligence/components/Content';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import DataTable from 'common/ui/Table/DataTable';
import {
  type,
  value,
  addedOn,
  addedBy,
  actions,
} from 'merchant/views/MagicCheckout/MagicIntelligence/components/cellItem';
import { showNotification } from 'merchant_common/reducers/notifications';
import { NOTIFICATION_MESSAGES } from 'merchant/views/MagicCheckout/MagicIntelligence/constants';
import {
  openConfirmDeleteModal,
  openBatchUploadModal,
} from 'merchant/views/MagicCheckout/MagicIntelligence/modal_util';

const gaEvents = setGaTrack('Dashboard - Magic Checkout - BU - bulk_whitelist_upsert');
const SAMPLE_ALLOWLIST_UPLOAD_FILE =
  'https://cdn.razorpay.com/static/assets/magic-checkout/sample_allowlist_upload.csv';

const initialState = {
  attributeType: '',
  attributeValue: '',
  count: 25,
};

const reducer = (state, action) => {
  switch (action.type) {
    case 'SET_TYPE':
      return { ...state, attributeType: action.value };
    case 'SET_COUNT':
      return { ...state, count: action.value };
    case 'SET_VALUE':
      return { ...state, attributeValue: action.value };
    default:
      return state;
  }
};

const AllowList = (props) => {
  const ctaText = 'Add to Allowlist';
  const title = 'Add items to Allowlist';
  const batchType = 'one_cc_cod_eligibility_attribute_whitelist_upsert';
  const successText = 'Allowlist upload initiated';
  const modalSource = 'Allowlist';
  const list = ['email', 'phone'];

  const {
    fetchAll,
    createBatch,
    validateBatch,
    openModal,
    closeModal,
    uploadAllowlist,
    deleteAllowlist,
    showNotification,
  } = props;

  const skip = useRef(0);
  const searchClicked = useRef(false);

  const [state, dispatch] = useReducer(reducer, initialState);

  const { attributeType, attributeValue, count } = state;

  const setCount = (value) => {
    dispatch({ type: 'SET_COUNT', value });
  };

  const setType = (value) => {
    dispatch({ type: 'SET_TYPE', value });
  };

  const setValue = (value) => {
    dispatch({ type: 'SET_VALUE', value });
  };

  const resetHandler = useCallback(() => {
    searchClicked.current = false;
    skip.current = 0;
    setType('');
    setValue('');
    setCount(25);
  }, [searchClicked, skip, setType, setValue, setCount]);

  useEffect(() => {
    if (!fetchAll) return;

    fetchAll({
      count: 25,
      skip: 0,
    });
  }, [fetchAll]);

  const deleteItem = (id) => {
    deleteAllowlist(id)
      .then(() => {
        showNotification({
          type: 'success',
          message: NOTIFICATION_MESSAGES.DELETE_SUCCESSFUL,
        });
        closeModal();
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: NOTIFICATION_MESSAGES.DELETE_ERROR,
        });
        closeModal();
      });
  };

  const onModalClose = () => {
    gaEvents?.trackUploadBatch?.('Close');
    closeModal();
  };

  const closeSuccessModal = () => {
    showNotification({
      type: 'success',
      message: 'Refresh page after 2 minutes to check uploaded Allowlist.',
      closeTimeout: 10000,
    });
  };

  const paginate = (params) => {
    const filters = {
      ...params,
    };

    setCount(params.count);
    skip.current = params.skip;

    return fetchAll({
      ...filters,
      attribute_type: attributeType,
      attribute_value: attributeValue,
    });
  };

  const onUploadClick = useCallback(() => {
    openBatchUploadModal(
      createBatch,
      validateBatch,
      openModal,
      onModalClose,
      uploadAllowlist,
      fetchAll,
      resetHandler,
      closeSuccessModal,
      gaEvents,
      SAMPLE_ALLOWLIST_UPLOAD_FILE,
      validateAllowlistModalInfo,
      ctaText,
      title,
      batchType,
      successText,
      list,
      modalSource,
    );
  }, [
    createBatch,
    validateBatch,
    openModal,
    onModalClose,
    uploadAllowlist,
    fetchAll,
    resetHandler,
    closeSuccessModal,
  ]);

  const onDeleteClick = useCallback(
    (id) => () => {
      openConfirmDeleteModal(openModal, closeModal, id, deleteItem, modalSource);
    },
    [openModal, closeModal, deleteItem],
  );

  return (
    <>
      <List
        ctaText="Allowlist"
        onUploadClick={onUploadClick}
        formName="allowlist"
        list={['email', 'phone']}
        resetHandler={resetHandler}
        count={count}
        attributeType={attributeType}
        attributeValue={attributeValue}
        setAttributeType={setType}
        setAttributeValue={setValue}
        setCount={setCount}
        skip={skip}
        hasNoData={searchClicked}
        {...props}
      />
      <DataTable
        title="Batch Uploads"
        columns={[type, value, addedOn, addedBy, actions({ onDeleteClick })]}
        EmptyComponent={emptyComponent(onUploadClick, 'Allowlist', searchClicked)}
        count={count}
        skip={skip.current}
        paginate={paginate}
        gridTemplateColumns="1fr 2fr 1fr 2fr 0.5fr"
        {...props}
      />
    </>
  );
};

const mapStateToProps = (state) => {
  return { ...state.magicAllowlist };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      fetchAll: fetchAllowlist,
      createBatch: createAllowlist,
      validateBatch: validateAllowlist,
      uploadAllowlist,
      deleteAllowlist,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(AllowList);
