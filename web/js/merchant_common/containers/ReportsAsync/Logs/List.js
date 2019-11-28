import LogItem from './Item';

export default function LogList(props) {
  const { items, config } = props;
  return (
    <div class="LogList">
      {items.map(item => (
        <LogItem
          key={item.id}
          configName={config.name}
          configTemplate={config.template}
          {...item}
        />
      ))}
    </div>
  );
}
