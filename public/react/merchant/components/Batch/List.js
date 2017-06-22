import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';
import TetherComponent from 'react-tether';
import BatchListFilter from 'merchant/components/Batch/ListFilter';
import { batchId, totalCount, status } from 'rzp/ui/item/pair';

const Button = ({ onClick, children }) => (
  <button class="btn btn-default btn-xs" onClick={onClick}>{children}</button>
);

function batchActions(mode, viewAll, issueAll) {
  return {
    viewAll,
    issueAll,
    title: 'Actions',
    value: item => (
      <div>
        <Button
          onClick={_ => open(`/${mode}/batches/${item.id}/download`, '_blank')}
        >
          Download
        </Button>
        {item.type === 'payment_link' &&
          <span>
            {viewAll &&
              <Button onClick={_ => viewAll(item)}>view all links</Button>}
            {issueAll &&
              <Button onClick={_ => issueAll(item)}>Issue all links</Button>}
          </span>}
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
