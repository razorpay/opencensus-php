import CustomClipboard from 'rzp/ui/Clipboard/Custom';

export default ({ url }) => {
  return (
    <span class="CopyLink">
      <span>{url}</span>
      <CustomClipboard value={url}>
        <button class="btn btn-default btn-xs">copy</button>
      </CustomClipboard>
    </span>
  );
};
