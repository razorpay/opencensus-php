import { downloadFromUFH } from 'merchant/utils/downloadFile';
import Spinner from 'common/ui/Spinner';

import LogItem from './Item';

export default function LogList(props) {
  const { loading, items, allConfigs } = props;

  const onDownloadClick = ({ target }) => {
    const { fileId, consumerId } = target.dataset;
    const accountId =
      props.currentMerchantId !== consumerId
        ? consumerId.replace('acc_')
        : undefined;
    return downloadFromUFH(fileId, accountId);
  };

  return (
    <div class="LogList">
      {loading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <>
          <div class="LogList__header">
            <strong>Recent Reports</strong>
          </div>
          {items.map(item => (
            <LogItem
              key={item.id}
              config={allConfigs.find(({ id }) => id === item.config_id) || {}}
              onDownloadClick={onDownloadClick}
              pollLog={props.pollLog}
              {...item}
            />
          ))}
        </>
      )}
      {!loading &&
        5 <= items.length &&
        items.length < 10 && (
          <div class="LoadMore">
            <button onClick={props.onLoadMoreClick} class="btn btn-link">
              Load More
            </button>
          </div>
        )}
    </div>
  );
}
