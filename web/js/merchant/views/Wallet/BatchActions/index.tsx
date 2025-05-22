import React, { Fragment, useEffect } from 'react';
import { connect } from 'react-redux';

import { EmptyComponent as emptyComponent } from 'merchant/components/BatchNew/ListAddons';
import { batchIdLink, totalCount, batchName, status, createdAt } from 'common/ui/item/pair';

import { fetchAllWalletBatches as fetchAll, batchDownload } from 'merchant/reducers/batches';
import { titleCase } from 'common/utils/rzp-utils';
import DataTable from 'common/ui/Table/DataTable';
import { Button, DownloadIcon } from '@razorpay/blade/components';
import { type AxiosResponse } from 'axios';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import {
  isCreateGiftCardBatchEnabled as isGCBatchEnabled,
  isGiftCardTransferEnabled as isGCTransferEnabled,
} from '../../GCMS/shared/utils';

const typesLabelMap = {
  create_wallet_accounts: 'Accounts',
  create_wallet_loads: 'Loads',
  update_gift_cards_expiry: 'GC Expiry',
};

const typeColumn = {
  title: 'Type',
  value: ({ type }) => typesLabelMap[type] || titleCase(type),
};

const batchStatus = {
  ...status,
  value: (item) => (
    <Fragment>
      {status.value(item)}
      {item.status === 'created' && <i className="i i-refresh refresh-batch-btn" />}
    </Fragment>
  ),
};

function batchActions(onDownloadClick) {
  return {
    title: 'Actions',
    value: (item) =>
      item.status === 'processed' && (
        <Button
          icon={DownloadIcon}
          iconPosition="right"
          onClick={() => onDownloadClick(item.id)}
          size="xsmall"
          type="button"
          variant="tertiary"
        >
          Download
        </Button>
      ),
  };
}

const emptyResultsDescription =
  'Create multiple accounts, loads, at once using a batch file. Simply upload a file containing all the information.';

interface FilterParams {
  skip: number;
  count: number;
}

interface ListProps {
  fetchAll: (
    params: FilterParams,
    isCreateGiftCardBatchEnabled: boolean,
    isGiftCardsTransferBatchEnabled: boolean,
  ) => void;
  batchDownload: (id: string) => Promise<AxiosResponse>;
  [x: string]: unknown;
}

export const List = ({ fetchAll, batchDownload, ...rest }: ListProps): JSX.Element => {
  const onDownload = (id) =>
    batchDownload(id)
      .then((res) => (window.location = res.data?.url))
      .catch((err) => console.error(err));

  const splitz = useSplitzService();
  const isCreateGiftCardBatchEnabled = isGCBatchEnabled(splitz);

  const isGiftCardsTransferBatchEnabled = isGCTransferEnabled(splitz);

  useEffect(() => {
    if (fetchAll) {
      fetchAll(
        {
          count: 25,
          skip: 0,
        },
        isCreateGiftCardBatchEnabled,
        isGiftCardsTransferBatchEnabled,
      );
    }
  }, [fetchAll, isCreateGiftCardBatchEnabled, isGiftCardsTransferBatchEnabled]);

  return (
    <div className="content-wrapper">
      <DataTable
        title="Batch Uploads"
        columns={[
          batchIdLink,
          batchName,
          totalCount,
          typeColumn,
          createdAt,
          batchStatus,
          batchActions(onDownload),
        ]}
        EmptyComponent={emptyComponent(null, null, emptyResultsDescription)}
        location={window.location}
        {...rest}
      />
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  ...state.batches,
});

export default connect(mapStateToProps, (dispatch) => ({
  fetchAll: (params, isCreateGiftCardBatchEnabled, isGiftCardsTransferBatchEnabled) =>
    dispatch(fetchAll(params, isCreateGiftCardBatchEnabled, isGiftCardsTransferBatchEnabled)),
  batchDownload: (id) => dispatch(batchDownload(id)),
}))(List);
