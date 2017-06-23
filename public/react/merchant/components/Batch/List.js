import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';
import TetherComponent from 'react-tether';
import BatchListFilter from 'merchant/components/Batch/ListFilter';
import { batchId, totalCount, status } from 'rzp/ui/item/pair';

function batchActions(mode, viewAll, issueAll) {
  return {
    viewAll,
    issueAll,
    title: 'Actions',
    value: item => (
      <div class="btn-toolbar">
        <a
          class="btn btn-xs btn-default"
          href={`/${mode}/batches/${item.id}/download`}
          target="_blank"
        >
          Download
        </a>
        {
          do {
            if (item.type === 'payment_link') {
              if (viewAll) {
                <button
                  class="btn btn-default btn-xs"
                  onClick={_ => viewAll(item)}
                >
                  view all links
                </button>;
              }

              if (issueAll && item.status === 'processed') {
                <button
                  class="btn btn-default btn-xs"
                  onClick={_ => issueAll(item)}
                >
                  Issue all links
                </button>;
              }
            }
          }
        }
      </div>
    ),
  };
}

export default function BatchList(props) {
  let {
    mode,
    docUrl,
    count,
    skip,
    paginate,
    onSubmit,
    uploadUrl,
    viewAll,
    issueAll,
  } = props;
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
        columns={[
          batchId,
          totalCount,
          status,
          batchActions(mode, viewAll, issueAll),
        ]}
        count={count}
        skip={skip}
        paginate={paginate}
        {...props}
      />
    </div>
  );
}
