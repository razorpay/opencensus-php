import Time from 'rzp/ui/Time';

import DetailRow from 'merchant/components/DetailRow';

export default ({
  webhookUrl = 'https://playmyplay.com/wp-admin/admin-post.php?action=rzp_wc_webhook',
  createdAt = 1532082818,
  status = 'Active',
}) => (
  <div class="list-group details-row-container">
    {/* Webhook URL */}
    <DetailRow label="URL" value={() => <code>{webhookUrl}</code>} />

    {/* Created At */}
    <DetailRow
      label="Created At"
      value={() => <Time value={createdAt} format="lll" />}
    />

    {/* Status for Web hook */}
    <DetailRow label="Status" value={status} />
  </div>
);
