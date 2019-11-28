import LogItem from './Item';

export default function LogList(props) {
  const { logs } = props;
  return (
    <div class="LogList">
      {logs.map(log => <LogItem key={log.id} {...log} />)}
    </div>
  );
}
