import React, { useEffect, useState, useRef } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';

import { Button, TextInput, DownloadCloudIcon, UploadCloudIcon } from '@razorpay/blade/components';

import { ValidateModalInfo } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/components/ValidateInfoModal';
import { EmptyComponent as emptyComponent } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/components/EmptyComponent';
import AllowlistTable from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/containers/AllowlistTable';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import {
  AllowlistContainer,
  AllowlistWrapper,
  TabHeader,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/Styled';
import ListFilter from 'merchant/components/ListFilter';
import DeleteAllToolbar from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/components/DeleteAllToolbar';
import ConfirmationModal from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import {
  validateAllowlist,
  fetchAllowlist,
  deleteAllowlist,
  downloadAllowlist,
} from 'merchant/reducers/magicCheckout/codEngineAllowlistUpload/actions';
import { showNotification } from 'merchant_common/reducers/notifications';

import {
  ALLOWLIST_DELETE_TEXTS,
  DISPLAY_MESSAGES,
  SAMPLE_FILE_URL,
  FILE_UPDATE_NOTIFICATION_MSG,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/constants';

import {
  CODEngineAllowlistUploadConfigs,
  CODEngineAllowlistUploadProps,
  ReducerState,
  SearchDataType,
  FileUploadResponse,
  PaginationOptions,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist/types';
import { downloadFromUrl } from 'merchant/views/MagicCheckout/common/helpers';
import { useSplitzService } from 'common/splitz';

const CLOSE_URL = '/magic/settings/cod-settings';

const openFileUpload = (validateBatch, openModal) => {
  openModal({
    size: 'large',
    className: 'cod-engine-allowlist-upload-modal',
    component: (
      <BatchUpload
        accept={['csv']}
        closeUrl={CLOSE_URL}
        title="Upload Zipcodes"
        batchType="cod_engine_allowlist_update"
        validateBatch={validateBatch}
        processFile
        displayMsgs={DISPLAY_MESSAGES}
        validateModalInfo={<ValidateModalInfo sampleUrl={SAMPLE_FILE_URL} />}
        maxFileSize={52428800} // 50MB
        batchListClass="cod-engine-allowlist-upload"
        hideCloseBtn
      />
    ),
  });
};

const CODEngineAllowlistUpload = (props: CODEngineAllowlistUploadProps) => {
  const {
    openModal,
    validateBatch,
    codEngineAllowlistUpload,
    fetchList,
    showNotification,
    closeModal,
    deleteList,
    downloadList,
  } = props;
  const { abExperiments } = useSplitzService();
  const isZoneUploadEnabled = abExperiments?.magic_zones_file_upload?.variables?.result === 'on';

  const { error, isLoading, items, total_records }: CODEngineAllowlistUploadConfigs =
    codEngineAllowlistUpload;

  const searchClicked = useRef<boolean>(false);

  const [hasMoreData, setHasMoreData] = useState(false);

  const [searchData, setSearchData] = useState<SearchDataType>({
    zipcode: '',
    count: 25,
    skip: 0,
  });

  useEffect(() => {
    if (fetchList) {
      fetchList({
        count: 25,
        skip: 0,
      });
    }
  }, []);

  useEffect(() => {
    if (!items) {
      return;
    }

    const recordsPresent = items.length + searchData.skip;

    if (recordsPresent < total_records) {
      setHasMoreData(true);
    } else {
      setHasMoreData(false);
    }
  }, [items, searchData.skip]);

  const showAlert = () => {
    showNotification({
      type: 'neutral',
      message: FILE_UPDATE_NOTIFICATION_MSG,
      closeTimeout: 10000,
    });
  };

  const displayFileUploadNotification = (res: FileUploadResponse) => {
    if (res?.data?.failed && res?.data?.count) {
      showNotification({
        type: 'neutral',
        message: `${res.data.count} out of ${
          res.data.count + res.data.failed
        } records processed successfully. Update the ${
          res.data.failed
        } invalid entries by reuploading the file`,
        closeTimeout: 10000,
      });
    } else {
      showNotification({
        type: 'success',
        message: 'File uploaded successfully.',
        closeTimeout: 10000,
      });
    }
  };

  const validate = (file: File, progressTracker: Record<string, any>) =>
    new Promise((resolve, reject) => {
      return validateBatch(file, progressTracker)
        .then((res) => {
          resolve({ data: { file } });

          displayFileUploadNotification(res as FileUploadResponse);

          fetchList({
            skip: 0,
            count: 25,
          }).then(() => {
            closeModal();
          });
        })
        .catch((error) => {
          reject(error);
        });
    });

  const handleUploadClick = () => {
    if (items?.length) {
      showAlert();
    }

    openFileUpload(validate, openModal);
  };

  const handleSubmit = () => {
    searchClicked.current = true;
    const payload = {
      zipcode: searchData.zipcode,
      count: searchData.count,
      skip: searchData.skip,
    };
    fetchList(payload);
  };

  const handleReset = () => {
    searchClicked.current = false;
    setSearchData((prevVal) => ({
      ...prevVal,
      skip: 0,
      count: 25,
      zipcode: '',
    }));
  };

  const paginate = (params: PaginationOptions) => {
    const { skip, count } = params;
    setSearchData((prevVal) => ({
      ...prevVal,
      skip,
      count,
    }));
    const payload = {
      skip,
      count,
    };

    fetchList(payload);
  };

  const handleInputChange = (e) => {
    const { name, value } = e;

    setSearchData((prevVal) => ({
      ...prevVal,
      [name]: value ? parseInt(value, 10) : value,
    }));
  };

  const handleAllowlistDelete = () => {
    deleteList()
      .then(() => {
        fetchList({
          count: 25,
          skip: 0,
        }).then(() => {
          showNotification({
            type: 'success',
            message: 'Allowlist deleted successfully.',
          });
        });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Something went wrong, please try again.',
        });
      })
      .finally(() => {
        closeModal();
      });
  };

  const handleDeleteClick = () => {
    const { header, desc, affirmativeLabel, abortLabel } = ALLOWLIST_DELETE_TEXTS;
    openModal({
      size: 'small',
      className: 'remove-configs-confirmation-modal',
      component: (
        <SuspenseWithLoader type="center">
          <ConfirmationModal
            header={header}
            desc={desc}
            affirmativeLabel={affirmativeLabel}
            abortLabel={abortLabel}
            onAffirm={handleAllowlistDelete}
          />
        </SuspenseWithLoader>
      ),
    });
  };

  const handleDownloadList = () => {
    downloadList()
      .then((res) => {
        const file_link = res.data.file_link;
        downloadFromUrl(showNotification, file_link);
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err?.errors?.[0] || 'Something went wrong while downloading file',
        });
      });
  };

  return (
    <AllowlistWrapper>
      <AllowlistContainer>
        <TabHeader>
          <span className="heading">Zipcode upload</span>
          <span className="pull-right upload-cta" data-testid="upload-order-history-cta">
            {isZoneUploadEnabled && items?.length ? (
              <Button
                type="button"
                variant="secondary"
                color="default"
                onClick={handleDownloadList}
                size="medium"
                iconPosition="left"
                icon={DownloadCloudIcon}
                marginRight="spacing.2"
              >
                Download Zipcodes
              </Button>
            ) : null}
            <Button
              type="button"
              variant="primary"
              color="default"
              onClick={handleUploadClick}
              size="medium"
              iconPosition="left"
              icon={UploadCloudIcon}
            >
              Upload Zipcodes
            </Button>
          </span>
          <p className="sub-text">
            Utilize Zipcode list to effortlessly specify preferred zipcodes for offering Cash on
            Delivery, tailor-made to your cart conditions as configured in the COD settings.
          </p>
        </TabHeader>

        {/* eslint-disable-next-line @typescript-eslint/ban-ts-comment */}
        {/**@ts-ignore*/}
        <ListFilter
          form={`cod-engine-allowlist-form`}
          onSubmit={handleSubmit}
          count={searchData.count}
          resetHandler={handleReset}
        >
          <div className="form-group list-filter-item">
            <TextInput
              name="zipcode"
              onChange={(e) => handleInputChange(e)}
              label="Zipcode"
              type="number"
              placeholder="Enter zipcode"
              value={searchData.zipcode.toString()}
            />
          </div>
          <div className="form-group list-filter-item count">
            <TextInput
              name="count"
              onChange={(e) => handleInputChange(e)}
              label="Count"
              type="number"
              placeholder="Enter count"
              value={searchData.count.toString()}
            />
          </div>
        </ListFilter>
        {items?.length ? <DeleteAllToolbar onDeleteClick={handleDeleteClick} /> : null}
        <AllowlistTable
          items={items}
          error={error}
          isLoading={isLoading}
          skip={searchData.skip}
          count={searchData.count}
          paginate={paginate}
          EmptyComponent={emptyComponent(handleUploadClick, 'Zipcode list', searchClicked)}
          hasMoreData={hasMoreData}
        />
      </AllowlistContainer>
    </AllowlistWrapper>
  );
};

const mapStateToProps = (state: ReducerState) => ({
  codEngineAllowlistUpload: state.codEngineAllowlistUpload,
});

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification,
      validateBatch: validateAllowlist,
      fetchList: fetchAllowlist,
      deleteList: deleteAllowlist,
      downloadList: downloadAllowlist,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CODEngineAllowlistUpload);
