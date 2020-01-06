import { downloadFromUFH } from 'merchant/utils/downloadFile';

import LogItem from './Item';

export default function LogList(props) {
  const { loading, items, allConfigs, configsLoading } = props;

  const onDownloadClick = ({ target }) => {
    const { fileId, consumerId } = target.dataset;
    const accountId =
      props.currentMerchantId !== consumerId
        ? consumerId.replace('acc_')
        : undefined;
    downloadFromUFH(fileId, accountId);
  };

  return (
    <div class="LogList">
      {loading || configsLoading ? (
        <p>Loading...</p>
      ) : (
        items.map(item => (
          <LogItem
            key={item.id}
            config={allConfigs.find(({ id }) => id === item.config_id) || {}}
            onDownloadClick={onDownloadClick}
            {...item}
          />
        ))
      )}
    </div>
  );
}
