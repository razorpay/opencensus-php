import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { downloadFromUFH } from 'merchant/utils/downloadFile';
import Spinner from 'common/ui/Spinner';
import { showNotification } from 'merchant_common/reducers/notifications';

import LogItem from './Item';

@RTracking(() => window.rzpQ.component('LogList'))
@connect(null, { showNotification })
export default class LogList extends React.PureComponent {
  onDownloadClick = ({ target }) => {
    const { fileId, consumerId } = target.dataset;
    const accountId =
      this.props.currentMerchantId !== consumerId
        ? consumerId.replace('acc_')
        : undefined;
    return downloadFromUFH(fileId, accountId)
      .then(response => {
        this.props.tracking.trackEvent(
          window.rzpQ.reporting().success('reporting.download_file', {
            report_file_id: fileId,
            report_consumer: consumerId,
          })
        );
        return response;
      })
      .catch(({ errors }) => {
        return this.props.showNotification({
          type: 'error',
          message: (errors || [])[0],
        });
      });
  };

  render() {
    const { pending, items, allConfigs, ...props } = this.props;
    return (
      <div class="LogList">
        {pending && items.length < 1 ? (
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
                config={
                  allConfigs.find(({ id }) => id === item.config_id) || {}
                }
                onDownloadClick={this.onDownloadClick}
                pollLog={props.pollLog}
                {...item}
              />
            ))}
          </>
        )}
        {items.length > 1 && (
          <div className="LoadMore">
            <LoadMoreAction
              onLoadMoreClick={props.onLoadMoreClick}
              pending={pending}
              allFetched={props.allFetched}
            />
          </div>
        )}
      </div>
    );
  }
}

function LoadMoreAction({ onLoadMoreClick, pending, allFetched }) {
  if (pending) return <em>Loading more requests...</em>;

  if (allFetched) return <em>Nothing more to load</em>;

  return (
    <button onClick={onLoadMoreClick} class="btn btn-link">
      Load More
    </button>
  );
}
