import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';
import TetherComponent from 'react-tether';
import BatchListFilter from 'merchant/components/Batch/ListFilter';
import { batchId, totalCount, status, batchDownload } from 'rzp/ui/item/pair';

export default function BatchList(props) {
  let { mode, docUrl, count, skip, paginate, onSubmit, uploadUrl } = props;
  return (
    <div class="content-wrapper">
      <TetherComponent
        target="tabbed-container > header"
        attachment="top right"
        targetAttachment="top right"
        offset="-8px 0"
      >
        <div />{/* required by react-tether */}
        <div class="btn-toolbar pull-right">
          {docUrl &&
            <a class="btn btn-link" href={docUrl} target="_blank">
              Documentation &nbsp;
              <i class="icon icon-external-link" />
            </a>}

          <Link class="btn btn-primary pull-right" to={uploadUrl}>
            Click here to upload
          </Link>
        </div>
      </TetherComponent>

      <BatchListFilter
        form="batchListFilter"
        count={count}
        onSubmit={onSubmit}
      />
      <DataTable
        title="Batch Uploads"
        columns={[batchId, totalCount, status, batchDownload(mode)]}
        count={count}
        skip={skip}
        paginate={paginate}
        {...props}
      />
    </div>
  );
}
