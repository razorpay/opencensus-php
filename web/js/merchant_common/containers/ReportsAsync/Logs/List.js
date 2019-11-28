import { downloadFromUFH } from 'merchant/utils/downloadFile';

import LogItem from './Item';

export default function LogList(props) {
  const { items, config } = props;

  const onDownloadClick = ({ target }) => {
    const fileId = target.dataset.fileId;
    downloadFromUFH(fileId);
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
