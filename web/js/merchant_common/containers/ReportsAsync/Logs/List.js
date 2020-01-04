import { downloadFromUFH } from 'merchant/utils/downloadFile';

import LogItem from './Item';

export default function LogList(props) {
  const { items, config } = props;

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
      {items.map(item => (
        <LogItem
          key={item.id}
          configName={config.name}
          configTemplate={config.template}
          onDownloadClick={onDownloadClick}
          {...item}
        />
      ))}
    </div>
  );
}
