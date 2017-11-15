import CustomClipboard from 'rzp/ui/Clipboard/Custom';

export default ({ url, onCopy = () => {} }) => {
  return (
    <span class="CopyLink">
      <span>{url}</span>
      <CustomClipboard value={url} onCopy={onCopy}>
        <button class="btn btn-default btn-xs">copy</button>
      </CustomClipboard>
    </span>
  );
};
