import Button from 'common/new-ui/Button';

export const pincode = { title: 'Zipcode', value: (item) => item.zipcode ?? '-' };

export const shippedOrders = { title: 'Shipped', value: (item) => item.shipped_order ?? '-' };

export const rtoOrders = { title: 'RTO', value: (item) => item.rto_order ?? '-' };

export const rtoPercent = {
  title: 'RTO %',
  value: (item) => (
    <div
      className="rto-percent"
      style={{
        background: `rgba(245, 102, 102, ${
          item.percentage ? (item.percentage / 100).toFixed(2) : 0
        })`,
      }}
    >
      {item?.percentage?.toFixed(1) ?? 0.0}%
    </div>
  ),
};

export const rtoRank = {
  title: 'RTO risk %',
  value: (item) => (
    <div
      className="rto-percent"
      style={{
        background: `rgba(245, 102, 102, ${item.rto_rank ? (item.rto_rank / 100).toFixed(2) : 0})`,
      }}
    >
      {item?.rto_rank?.toFixed(1) ?? 0.0}%
    </div>
  ),
};

export const action = ({ onClick }) => ({
  title: '',
  value: (item) => (
    <Button.Primary
      type="button"
      className="block-btn"
      onClick={() => onClick(item.zipcode, item.is_blocked)}
    >
      {item.is_blocked ? 'Unblock' : 'Block'}
    </Button.Primary>
  ),
});
